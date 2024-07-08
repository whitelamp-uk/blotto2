
CREATE TABLE blotto_update_2024070813 LIKE blotto_update
;
INSERT INTO blotto_update_2024070813
  SELECT * FROM blotto_update
;


drop table if exists blotto_update_repair
;
CREATE TABLE blotto_update_repair LIKE blotto_update_2024070813
;
INSERT INTO blotto_update_repair
  SELECT * FROM blotto_update_2024070813
  WHERE updated<'2024-06-26'
;

TRUNCATE blotto_update
;
INSERT INTO blotto_update (updated,milestone,milestone_date,supporter_id,player_id,contact_id)
  SELECT  updated,milestone,milestone_date,supporter_id,player_id,contact_id FROM blotto_update_repair
;


-- FIRST COLLECTION GHOSTS

create or replace view blotto_update_1st_collect_multi as
-- first_collected, per player no more than one
  SELECT
    'first_collection-multiple' AS `error_type`
   ,COUNT(`u`.`milestone`) AS `quantity`
   ,GROUP_CONCAT(`u`.`milestone_date` ORDER BY `milestone_date` SEPARATOR ' ') AS `milestone_dates`
   ,MAX(`u`.`milestone_date`) AS `latest_milestone_date`
   ,`u`.`supporter_id`
   ,`u`.`player_id`
   ,`p`.`client_ref`
   ,`ps`.`players`
  FROM `blotto_update` AS `u`
  JOIN `blotto_player` AS `p`
    ON `p`.`id`=`u`.`player_id`
  JOIN (
    SELECT
      `plyr`.`supporter_id`
     ,GROUP_CONCAT(`plyr`.`client_ref` SEPARATOR ' ') AS `players`
    FROM `blotto_player` AS `plyr`
    GROUP BY `supporter_id`
  ) AS `ps`
    ON `ps`.`supporter_id`=`u`.`supporter_id`
  WHERE `u`.`milestone` IN ('first_collection')
  GROUP BY `u`.`player_id`
  HAVING `quantity`>1
  ;


create or replace view blotto_update_1st_collect_ghost as
select u.*
from blotto_update as u
join blotto_update_1st_collect_multi as fc
  on fc.player_id=u.player_id
-- keep the latest one
 and u.milestone_date!=fc.latest_milestone_date
where u.milestone='first_collection'
order by player_id,milestone_date
;


select count(id) from blotto_update
;
delete blotto_update from blotto_update
join blotto_update_1st_collect_ghost as fcg
  on fcg.player_id=blotto_update.player_id
 and fcg.milestone_date=blotto_update.milestone_date
where blotto_update.milestone='first_collection'
;
select count(id) from blotto_update
;


-- FALSE FIRST COLLECTIONS

create or replace view blotto_update_1st_collect_false as
-- first_collected, per player must have collection
  SELECT
    'first_collection-multiple' AS `error_type`
   ,`u`.`milestone_date`
   ,`u`.`supporter_id`
   ,`u`.`player_id`
   ,`p`.`client_ref`
  FROM `blotto_update` AS `u`
  JOIN `blotto_player` AS `p`
    ON `p`.`id`=`u`.`player_id`
  LEFT JOIN `blotto_build_collection` AS `c`
    ON `c`.`ClientRef`=`p`.`client_ref`
  WHERE `u`.`milestone` IN ('first_collection')
    AND `c`.`ClientRef` IS NULL
  GROUP BY `u`.`player_id`
  ;

select count(id) from blotto_update
;
delete blotto_update from blotto_update
join blotto_update_1st_collect_false as fcf
  on fcf.player_id=blotto_update.player_id
 and fcf.milestone_date=blotto_update.milestone_date
where blotto_update.milestone='first_collection'
;
select count(id) from blotto_update
;




-- OLD MILESTONES REPORTED AFTER FIXES
create or replace view blotto_update_cancels_old as
SELECT *
FROM `blotto_update_2024070813`
WHERE `updated` = '2024-07-08' AND `milestone` = 'cancellation'
ORDER BY `milestone_date`
;


create or replace view blotto_update_cancels_supporter as
SELECT
  u.supporter_id
 ,SUM(u.milestone='cancellation') AS `cancels`
 ,SUM(u.milestone='reinstatement') AS `reinstates`
FROM blotto_update_2024070813 as u
join blotto_update_cancels_old as o
  on o.supporter_id=u.supporter_id
group by u.supporter_id
-- blotto_update_cancels_old where (a) re-instated and (b) more cancels than reinstates
having `reinstates`>0 and `cancels`-`reinstates`>0
order by u.supporter_id
;

create or replace view blotto_update_cr_remove as
select
  s.supporter_id
 ,uc.id as last_cancelled_update_id
 ,c.last_cancelled
 ,ur.id as last_reinstated_update_id
 ,r.last_reinstated
from blotto_update_cancels_supporter as s
-- blotto_update_cancels_supporter where cancel and reinstate rows are the last ones
join (
  select
    supporter_id
   ,MAX(milestone_date) AS last_cancelled
  from blotto_update_2024070813
  where milestone='cancellation'
  group by supporter_id
) as c
  on c.supporter_id=s.supporter_id
join (
  select
    supporter_id
   ,MAX(milestone_date) AS last_reinstated
  from blotto_update_2024070813
  where milestone='reinstatement'
  group by supporter_id
) as r
  on r.supporter_id=c.supporter_id
join blotto_update_2024070813 AS uc
  on uc.supporter_id=s.supporter_id
 and uc.milestone='cancellation'
 and uc.milestone_date=c.last_cancelled
join blotto_update_2024070813 AS ur
  on ur.supporter_id=s.supporter_id
 and ur.milestone='reinstatement'
 and ur.milestone_date=r.last_reinstated
having last_cancelled<'2024-06-01'
   and last_reinstated<'2024-06-01'
;


select count(id) from blotto_update
;
delete blotto_update from blotto_update
join blotto_update_cr_remove as cr
  on cr.supporter_id=blotto_update.supporter_id
 and cr.last_cancelled=blotto_update.milestone_date
where blotto_update.milestone='cancellation'
;
select count(id) from blotto_update
;
delete blotto_update from blotto_update
join blotto_update_cr_remove as cr
  on cr.supporter_id=blotto_update.supporter_id
 and cr.last_reinstated=blotto_update.milestone_date
where blotto_update.milestone='reinstatement'
;
select count(id) from blotto_update
;


-- contiguous IDs
drop table if exists blotto_update_repair
;
CREATE TABLE blotto_update_repair LIKE blotto_update
;
INSERT INTO blotto_update_repair
  SELECT * FROM blotto_update
;

TRUNCATE blotto_update
;
INSERT INTO blotto_update (updated,milestone,milestone_date,supporter_id,player_id,contact_id)
  SELECT  updated,milestone,milestone_date,supporter_id,player_id,contact_id FROM blotto_update_repair
;



-- milestone_date repair code from updates()
  UPDATE `blotto_update` AS `u`
  -- restrict to the latest cancellation row for each player
  JOIN (
    SELECT
      `c`.`supporter_id`
     ,MAX(`c`.`id`) AS `latest_id`
     ,`r`.`latest_id` AS `reinstate_latest_id`
    FROM `blotto_update` AS `c`
    LEFT JOIN (
      -- reinstatement of the latest cancellation
      SELECT
        `supporter_id`
       ,MAX(`id`) AS `latest_id`
      FROM `blotto_update`
      WHERE `milestone`='reinstatement'
      GROUP BY `supporter_id`
    ) AS `r`
      ON `r`.`supporter_id`=`c`.`supporter_id`
     AND `r`.`latest_id`>`c`.`id`
    WHERE `milestone`='cancellation'
    GROUP BY `supporter_id`
    -- not reinstated after the latest cancellation
    HAVING `reinstate_latest_id` IS NULL
        OR `reinstate_latest_id`<`latest_id`
  ) AS `us`
    ON `us`.`latest_id`=`u`.`id`
  -- get contemporary values for player cancelled_date
  JOIN (
    SELECT
      `p`.`supporter_id`
     ,`cancelled`.`cancelled_date`
    FROM `blotto_player` AS `p`
    JOIN (
      SELECT
        `client_ref`
       ,`cancelled_date`
      FROM `Cancellations`
      GROUP BY `client_ref`
    ) AS `cancelled`
      ON `cancelled`.`client_ref`=`p`.`client_ref`
    GROUP BY `p`.`supporter_id`
  ) AS `currently`
    ON `currently`.`supporter_id`=`u`.`supporter_id`
  -- set the milestone_date to the contemporary cancel date
  SET `u`.`milestone_date`=`currently`.`cancelled_date`
  WHERE `u`.`milestone`='cancellation'
  ;

-- Pick up the recent updates
CALL updates();

create or replace view blotto_update_recent as
  -- What has just got inserted and do any of these have problematic dates?
  select
    u.*
   ,c.cancelled_date
  from blotto_update as u
  join blotto_player as p
    on p.id=u.player_id
  left join (
    select
      client_ref
     ,cancelled_date
    from Cancellations
    group by client_ref
  )      as c
         on c.client_ref=p.client_ref
  where u.milestone IN ('cancellation','reinstatement')
    and u.updated>='2024-06-24'
  order by milestone_date,id
  ;


-- CGH - by inspection these look great
-- SHC - 14 updated manually
-- WHH - by inspection these look great

-- attempt to automate for DBH
create or replace view blotto_update_amend as
  select
    cs.cancelled_date
   ,cs.payments_collected
   ,collect.last_collected
   ,u.*
  from (
    select
      DISTINCT supporter_id
    from blotto_update_recent
    where updated='2024-07-08'
  ) as s
  join blotto_player as p
    on p.supporter_id=s.supporter_id
  join (
    select
      client_ref
     ,cancelled_date
     ,payments_collected
    from Cancellations
    group by client_ref
  ) as cs
    on cs.client_ref=p.client_ref
  join blotto_update as u
    on u.supporter_id=s.supporter_id
  left join (
    select
      ClientRef
     ,MAX(DateDue) as last_collected
    from blotto_build_collection
    group by ClientRef
  ) as collect
    on collect.ClientRef=p.client_ref
  order by supporter_id,milestone_date
  ;


-- search: payments_collected=0
-- all have same pattern:
--  * reinstatement milestone_date is one day after the first cancellation
--  * updated = 2024-06-24 for the second cancellation

update blotto_update as u
join blotto_update_amend as ua
  on ua.milestone=u.milestone
 and ua.supporter_id=u.supporter_id
 and ua.payments_collected=0
join (
  select
    supporter_id
   ,min(milestone_date) as first_cancelled
  from blotto_update
  where milestone='cancellation'
  group by supporter_id
) as c
  on c.supporter_id=ua.supporter_id
set u.milestone_date=date_add(c.first_cancelled,interval 1 day)
where u.milestone='reinstatement'
;


-- search: payments_collected>0 && last_collected=2024-07-01 (latest and now stable)
-- all have same pattern:
--  * the penultimate cancellation milestone_date amended to cancelled_date
--  * the last reinstatement milestone_date amended to cancelled_date + 1 day
--  * last cancellation is deleted

create or replace view blotto_update_test as
  SELECT
    u2.*
  FROM `blotto_update` as u1
  join `blotto_update` as u2
    on u2.supporter_id=u1.supporter_id
  WHERE u1.`milestone_date` LIKE '%-09'
  and u1.milestone='reinstatement'
  order by supporter_id,milestone_date
  ;





update blotto_update as u
join blotto_update_amend as ua
  on ua.milestone=u.milestone
 and ua.supporter_id=u.supporter_id
 and ua.payments_collected>0
 and ua.last_collected='2024-07-01'
join (
  select
    supporter_id
   ,min(milestone_date) as first_cancelled
  from blotto_update
  where milestone='cancellation'
  group by supporter_id
) as c
  on c.supporter_id=ua.supporter_id
set u.milestone_date=date_add(c.first_cancelled,interval 1 day)
where u.milestone='reinstatement'
;





-- For those that were updated, re-index the table
drop table if exists blotto_update_repair
;
CREATE TABLE blotto_update_repair LIKE blotto_update
;
INSERT INTO blotto_update_repair (updated,milestone,milestone_date,supporter_id,player_id,contact_id)
  SELECT updated,milestone,milestone_date,supporter_id,player_id,contact_id FROM blotto_update
  ORDER BY updated,milestone_date,milestone,supporter_id,player_id,contact_id
;

TRUNCATE blotto_update
;
INSERT INTO blotto_update
  SELECT * FROM blotto_update_repair
;





   

-- So for now moving on we need "spread out" updated dates
-- theoretically the system discovers a cancellation on Cancellations.cancelled_date + 1 day
-- (remember Cancellations has BACS jitter built in)
update blotto_update as u
join blotto_update_recent as ur
  on ur.id=u.id
set u.updated=date_add(ur.milestone_date,interval 1 day)

-- Tidy up a bit
DROP TABLE `blotto_change_repair`
;
DROP TABLE `blotto_update_repair`
;

