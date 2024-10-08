

DELIMITER $$
DROP FUNCTION IF EXISTS `cancelDate`$$
CREATE FUNCTION `cancelDate` (
  d date
 ,freq char(16) character set ascii
) RETURNS date DETERMINISTIC
BEGIN
  DECLARE cd date;
  IF (freq='0' OR freq='Single') THEN
    -- Cancel date is always in the future (never treated as cancelled)
    RETURN DATE_ADD(CURDATE(),INTERVAL 1 YEAR)
    ;
  END IF
  ;
  SET cd = d;
  IF (freq='12' OR freq='Annually') THEN
    -- Add another year
    SET cd = DATE_ADD(cd,INTERVAL 1 YEAR)
    ;
  END IF
  ;
  IF (freq='6' OR freq='Six Monthly') THEN
    -- Add another six months
    SET cd = DATE_ADD(cd,INTERVAL 6 MONTH)
    ;
  END IF
  ;
  IF (freq='3' OR freq='Quarterly') THEN
    -- Add another 3 months
    SET cd = DATE_ADD(cd,INTERVAL 3 MONTH)
    ;
  END IF
  ;
  -- Allow for the cancellation interval
  SET cd = DATE_ADD(cd,INTERVAL 1 MONTH)
  ;
  -- Allow for BACS jitter
  SET cd = DATE_ADD(cd,INTERVAL 7 DAY)
  ;
  RETURN cd
  ;
END$$
DELIMITER ;



CALL `cancellationsByRule`()
;


drop table if exists blotto_update_repair
;
CREATE TABLE blotto_update_repair LIKE blotto_update_2024070317
;
INSERT INTO blotto_update_repair
  SELECT * FROM blotto_update_2024070317
  WHERE updated<'2024-06-26'
;

TRUNCATE blotto_update
;
INSERT INTO blotto_update
  SELECT * FROM blotto_update_repair
;



  UPDATE `blotto_update` AS `u`
  -- restrict to the latest cancellation row for each supporter
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






drop table if exists blotto_change_repair
;
CREATE TABLE blotto_change_repair LIKE blotto_change_2024070317
;

INSERT INTO blotto_change_repair
  SELECT * FROM blotto_change_2024070317
  WHERE changed_date<'2024-06-26'
;
TRUNCATE blotto_change
;
INSERT INTO blotto_change
  SELECT * FROM blotto_change_repair
;


-- check repair
create or replace view blotto_update_tmp_cancels as
  select
    datediff(c.cancelled_date,u.milestone_date)!=0 as disparity
   ,c.client_ref
   ,m.StartDate
   ,cs.last_collected
   ,c.cancelled_date
   ,u.*
  from blotto_update as u
  join blotto_player as p
    on p.id=u.player_id
  left join (
    select
      client_ref
     ,cancelled_date
  	from Cancellations
    group by client_ref
  ) as c
    on c.client_ref=p.client_ref
  left join blotto_build_mandate as m
    on m.ClientRef=p.client_ref
  left join (
  	select
  	  ClientRef
     ,MAX(DateDue) AS `last_collected`
  	from blotto_build_collection
  	group by ClientRef
  ) as cs
    on cs.ClientRef=m.ClientRef
  where u.milestone='cancellation'
--    and u.updated='2024-07-04'
--    and u.milestone_date<'2024-06-01'
    and (m.ClientRef is null or m.Freq!='Single')
  order by u.milestone_date,c.client_ref
  ;


-- Tidy up a bit
DROP TABLE `blotto_change_repair`
;
DROP TABLE `blotto_update_repair`
;

