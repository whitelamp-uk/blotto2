

-- when you need to change updates() inserts into blotto_update things can go wrong if you do not go carefully.

-- in this example, the first_collection milestone has been choosing the wrong row from blotto_contact due to an SQL bug in updates()

-- the offending insert-ignore statement gets corrected and tested below with a change of table name - IMPORTANT

-- because insert-ignore relies on a unique key that includes the contact ID, row duplication occurs

-- solution: write and execute SQL to repair contact IDs in existing data

-- IMPORTANT: the live Git pull of the insert-ignore should be IMMEDIATELY AFTER doing this update process




/*
-- the problem @ 2025-10-03:
we need to repair first_collection rows that are pointing to the wrong contact IDs
they are wrong because insert-ignore code in updates() had to be changed because the logic was broken
*/

DROP TABLE IF EXISTS `blotto_update_repair_tmp`
;
-- what should have happened
CREATE TABLE `blotto_update_repair_tmp` AS
  SELECT
    `p`.`supporter_id`
   ,`cln`.`DateDue` AS `DateDue`
   ,MAX(`c`.`id`) AS `contact_id`
  FROM (
    SELECT
      `ClientRef`
     ,MIN(`DateDue`) AS `DateDue`
    FROM `blotto_build_collection`
    GROUP BY `Provider`,`RefNo`
  ) AS `cln`
  JOIN `blotto_player` AS `p`
    ON `p`.`client_ref`=`cln`.`ClientRef`
  JOIN `blotto_supporter` AS `s`
    ON `s`.`id`=`p`.`supporter_id`
   AND `s`.`client_ref`=`p`.`client_ref` -- original player
  JOIN `blotto_contact` AS `c`
    ON `c`.`supporter_id`=`s`.`id`
   AND DATE(`c`.`created`)<=`cln`.`DateDue`
  -- await BACS jitter
  WHERE `cln`.`DateDue`<DATE_SUB(CURDATE(),INTERVAL 7 DAY)
  GROUP BY `s`.`id`
;
ALTER TABLE `blotto_update_repair_tmp`
ADD PRIMARY KEY (`supporter_id`)
;

-- check for differences against what actually happened
DROP TABLE IF EXISTS `blotto_update_repair_diff`
;
CREATE TABLE `blotto_update_repair_diff` AS
select
  `t`.*
 ,`u`.`contact_id` AS `was`
from `blotto_update_repair_tmp` as `t`
join `blotto_update` as `u`
  on `u`.`supporter_id`=`t`.`supporter_id`
 and `u`.`milestone`='first_collection'
where `t`.`contact_id`!=`u`.`contact_id`
;
ALTER TABLE `blotto_update_repair_diff`
ADD PRIMARY KEY (`supporter_id`)
;


-- create a test table, mimic blotto_update entirely
DROP TABLE IF EXISTS `blotto_update_repair_test`
;
CREATE TABLE `blotto_update_repair_test` LIKE `blotto_update`
;
insert into `blotto_update_repair_test`
select
  *
from blotto_update
where milestone='first_collection'
;

-- now repair `blotto_update_repair_test`.`contact_id`
update blotto_update_repair_test as u
join blotto_update_repair_diff as d
  on d.supporter_id=u.supporter_id
set
  u.contact_id=d.contact_id
where u.milestone='first_collection'
;

-- the adapted insert-ignore from updates() gets tested here
-- when you copy in, make sure the table name is
-- NO LONGER THE REAL TABLE!!!!
  INSERT IGNORE INTO `blotto_update_repair_test` -- CHANGED!!!
    (`updated`,`milestone`,`milestone_date`,`supporter_id`,`player_id`,`contact_id`)
    SELECT
      CURDATE()
     ,'first_collection'
     ,`cln`.`DateDue`
     ,`s`.`id`
     ,MIN(`p`.`id`)
     ,MAX(`c`.`id`)
    FROM (
      SELECT
        `ClientRef`
       ,MIN(`DateDue`) AS `DateDue`
      FROM `blotto_build_collection`
      -- await BACS jitter
      WHERE `DateDue`<DATE_SUB(CURDATE(),INTERVAL 7 DAY)
      GROUP BY `RefNo`
    ) AS `cln`
    JOIN `blotto_player` AS `p`
      ON `p`.`client_ref`=`cln`.`ClientRef`
    JOIN `blotto_supporter` AS `s`
      ON `s`.`id`=`p`.`supporter_id`
     AND `s`.`client_ref`=`p`.`client_ref` -- original player
    JOIN `blotto_contact` AS `c`
      ON `c`.`supporter_id`=`s`.`id`
     AND DATE(`c`.`created`)<=`cln`.`DateDue`
    GROUP BY `s`.`id`
  ;
-- the repair is sound if no rows are inserted


/*
-- the dangerous bit when happy
update blotto_update as u
join blotto_update_repair_diff as d
  on d.supporter_id=u.supporter_id
set
  u.contact_id=d.contact_id
where u.milestone='first_collection'
;

-- do for every org
-- pull db.routines.sql so the next builds use the new version of updates()
-- NEVER run updates() manually after repairing - the build needs to replace it first
-- NEVER let the next build run until updates() has been pulled live
*/


/*
-- ensure CRM updates push contact_change milestones to the org
-- done by cloning latest blotto_contact records
-- updates() finds them and does the rest
*/

DROP TABLE IF EXISTS `blotto_contact_test`
;
create table blotto_contact_test like blotto_contact
;
-- copy the lot to the test table
insert into blotto_contact_test select * from blotto_contact
;
-- insert the corrections
INSERT INTO `blotto_contact_test` (
  `supporter_id`
 ,`updater`
 ,`title`
 ,`name_first`
 ,`name_last`
 ,`email`
 ,`mobile`
 ,`telephone`
 ,`address_1`
 ,`address_2`
 ,`address_3`
 ,`town`
 ,`county`
 ,`postcode`
 ,`country`
 ,`dob`
 ,`yob`
 ,`p0`
 ,`p1`
 ,`p2`
 ,`p3`
 ,`p4`
 ,`p5`
 ,`p6`
 ,`p7`
 ,`p8`
 ,`p9`
)
SELECT
  `c`.`supporter_id`
 ,CURRENT_USER()
 ,GROUP_CONCAT(`c`.`title` ORDER BY `id` DESC LIMIT 1)
 ,GROUP_CONCAT(`c`.`name_first` ORDER BY `id` DESC LIMIT 1)
 ,GROUP_CONCAT(`c`.`name_last` ORDER BY `id` DESC LIMIT 1)
 ,GROUP_CONCAT(`c`.`email` ORDER BY `id` DESC LIMIT 1)
 ,GROUP_CONCAT(`c`.`mobile` ORDER BY `id` DESC LIMIT 1)
 ,GROUP_CONCAT(`c`.`telephone` ORDER BY `id` DESC LIMIT 1)
 ,GROUP_CONCAT(`c`.`address_1` ORDER BY `id` DESC LIMIT 1)
 ,GROUP_CONCAT(`c`.`address_2` ORDER BY `id` DESC LIMIT 1)
 ,GROUP_CONCAT(`c`.`address_3` ORDER BY `id` DESC LIMIT 1)
 ,GROUP_CONCAT(`c`.`town` ORDER BY `id` DESC LIMIT 1)
 ,GROUP_CONCAT(`c`.`county` ORDER BY `id` DESC LIMIT 1)
 ,GROUP_CONCAT(`c`.`postcode` ORDER BY `id` DESC LIMIT 1)
 ,GROUP_CONCAT(`c`.`country` ORDER BY `id` DESC LIMIT 1)
 ,GROUP_CONCAT(`c`.`dob` ORDER BY `id` DESC LIMIT 1)
 ,GROUP_CONCAT(`c`.`yob` ORDER BY `id` DESC LIMIT 1)
 ,GROUP_CONCAT(`c`.`p0` ORDER BY `id` DESC LIMIT 1)
 ,GROUP_CONCAT(`c`.`p1` ORDER BY `id` DESC LIMIT 1)
 ,GROUP_CONCAT(`c`.`p2` ORDER BY `id` DESC LIMIT 1)
 ,GROUP_CONCAT(`c`.`p3` ORDER BY `id` DESC LIMIT 1)
 ,GROUP_CONCAT(`c`.`p4` ORDER BY `id` DESC LIMIT 1)
 ,GROUP_CONCAT(`c`.`p5` ORDER BY `id` DESC LIMIT 1)
 ,GROUP_CONCAT(`c`.`p6` ORDER BY `id` DESC LIMIT 1)
 ,GROUP_CONCAT(`c`.`p7` ORDER BY `id` DESC LIMIT 1)
 ,GROUP_CONCAT(`c`.`p8` ORDER BY `id` DESC LIMIT 1)
 ,GROUP_CONCAT(`c`.`p9` ORDER BY `id` DESC LIMIT 1)
FROM `blotto_update_repair_diff` AS `d`
JOIN `blotto_contact` AS `c`
  ON `c`.`supporter_id`=`d`.`supporter_id`
GROUP BY `supporter_id`
  ;


/*
-- the dangerous bit when happy

just the same SQL statement except now insert into the real table blotto_contact

the date that updates() next runs, the inserts will get recorded as recent contact_change milestones
*/

