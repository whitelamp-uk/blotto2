
-- EXTERNAL TICKET SALE IMPORT

/*
If this were an automated procedure, probably check the tickes against the database before anything else
*/


-- drop table
DROP TABLE IF EXISTS `tmp_cash_tickets`
;

-- create table
CREATE TABLE `tmp_cash_tickets` (
  `ticket_number` varchar(255) CHARACTER SET utf8 COLLATE utf8_unicode_ci DEFAULT NULL,
  `mobile` varchar(255) CHARACTER SET utf8 COLLATE utf8_unicode_ci DEFAULT NULL,
  `title` varchar(255) CHARACTER SET utf8 COLLATE utf8_unicode_ci DEFAULT NULL,
  `name_first` varchar(255) CHARACTER SET utf8 COLLATE utf8_unicode_ci DEFAULT NULL,
  `name_last` varchar(255) CHARACTER SET utf8 COLLATE utf8_unicode_ci DEFAULT NULL,
  `email` varchar(255) CHARACTER SET utf8 COLLATE utf8_unicode_ci DEFAULT NULL,
  `telephone` varchar(255) CHARACTER SET utf8 COLLATE utf8_unicode_ci DEFAULT NULL,
  `address_1` varchar(255) CHARACTER SET utf8 COLLATE utf8_unicode_ci DEFAULT NULL,
  `address_2` varchar(255) CHARACTER SET utf8 COLLATE utf8_unicode_ci DEFAULT NULL,
  `address_3` varchar(255) CHARACTER SET utf8 COLLATE utf8_unicode_ci DEFAULT NULL,
  `town` varchar(255) CHARACTER SET utf8 COLLATE utf8_unicode_ci DEFAULT NULL,
  `county` varchar(255) CHARACTER SET utf8 COLLATE utf8_unicode_ci DEFAULT NULL,
  `postcode` varchar(255) CHARACTER SET utf8 COLLATE utf8_unicode_ci DEFAULT NULL,
  `dob` varchar(255) CHARACTER SET utf8 COLLATE utf8_unicode_ci DEFAULT NULL,
  `eml` varchar(255) CHARACTER SET utf8 COLLATE utf8_unicode_ci DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci
;

-- load data
LOAD DATA LOCAL INFILE '/home/mark/dbh_cash.csv'
INTO TABLE `tmp_cash_tickets`
FIELDS TERMINATED BY ',' ENCLOSED BY '"'
LINES TERMINATED BY '\n'
;

-- trim everything
UPDATE `tmp_cash_tickets`
SET
  `ticket_number`=TRIM(`ticket_number`)
 ,`mobile`=TRIM(`mobile`)
 ,`title`=TRIM(`title`)
 ,`name_first`=TRIM(`name_first`)
 ,`name_last`=TRIM(`name_last`)
 ,`email`=TRIM(`email`)
 ,`telephone`=TRIM(`telephone`)
 ,`address_1`=TRIM(`address_1`)
 ,`address_2`=TRIM(`address_2`)
 ,`address_3`=TRIM(`address_3`)
 ,`town`=TRIM(`town`)
 ,`county`=TRIM(`county`)
 ,`postcode`=TRIM(`postcode`)
 ,`dob`=TRIM(`dob`)
 ,`eml`=TRIM(`eml`)
;

-- convert date
UPDATE `tmp_cash_tickets`
SET
  `dob`=CONCAT(
    SUBSTR(`dob`,7,4)
   ,'-'
   ,SUBSTR(`dob`,4,2)
   ,'-'
   ,SUBSTR(`dob`,1,2)
  )
  WHERE `dob` REGEXP '^[0-9]{2}-[0-9]{2}-[0-9]{4}$'
;
-- index the table, add rejection and supporter_id fields
ALTER TABLE `tmp_cash_tickets`
  ADD `id` int(11) unsigned NOT NULL AUTO_INCREMENT FIRST
 ,ADD `rejected` tinyint(1) unsigned DEFAULT 0 AFTER `id`
 ,ADD `supporter_id` int(11) unsigned DEFAULT NULL AFTER `rejected`
 ,ADD PRIMARY KEY(`id`)
;

-- rejections
UPDATE `tmp_cash_tickets`
SET `rejected`=1
WHERE `ticket_number` NOT REGEXP '^[0-9]+$'
   OR (LENGTH(`dob`)>0 AND DATE(`dob`)!=`dob`)
;

-- left pad ticket numbers
UPDATE `tmp_cash_tickets`
SET
  `ticket_number`=LPAD(`ticket_number`,6,0)
 ,`dob`=CONCAT(
    SUBSTR(`dob`,7,4)
   ,'-'
   ,SUBSTR(`dob`,4,2)
   ,'-'
   ,SUBSTR(`dob`,1,2)
  )
  WHERE `rejected`=0
;

-- supporter records based on first ticket
INSERT INTO `blotto_supporter` (
  `created`
 ,`approved`
 ,`projected_first_draw_close`
 ,`projected_chances`
 ,`canvas_code`
 ,`canvas_agent_ref`
 ,`canvas_ref`
 ,`client_ref`
)
SELECT
  CURDATE()
 ,CURDATE()
 ,'2024-07-26'
 ,COUNT(`ticket_number`)
 ,'EX'
 ,'1214'
 ,1*MIN(`ticket_number`)
 ,CONCAT('EX',MIN(`id`)) -- EXternal supporters
FROM `tmp_cash_tickets`
WHERE `rejected`=0
GROUP BY CONCAT(`email`,'-',`mobile`)
;


-- Set the supporter ID for the first ticket
UPDATE `tmp_cash_tickets` AS `t`
JOIN `blotto_supporter` AS `s`
  ON `s`.`client_ref`=CONCAT('EX',`t`.`id`)
 SET `t`.`supporter_id`=`s`.`id`
WHERE `t`.`supporter_id` IS NULL
;

-- Set the supporter IDs for other tickets related by mobile
UPDATE `tmp_cash_tickets` AS `t1`
JOIN `tmp_cash_tickets` AS `t2`
  ON `t1`.`mobile`!=''
 AND `t2`.`mobile`=`t1`.`mobile`
 AND `t2`.`supporter_id` IS NULL
 SET `t2`.`supporter_id`=`t1`.`supporter_id`
WHERE `t1`.`rejected`=0
  AND `t1`.`supporter_id` IS NOT NULL
;

-- Set the supporter IDs for other tickets related by email
UPDATE `tmp_cash_tickets` AS `t1`
JOIN `tmp_cash_tickets` AS `t2`
  ON `t1`.`email`!=''
 AND `t2`.`email`=`t1`.`email`
 AND `t2`.`supporter_id` IS NULL
 SET `t2`.`supporter_id`=`t1`.`supporter_id`
WHERE `t1`.`rejected`=0
  AND `t1`.`supporter_id` IS NOT NULL
;

--Set deterministic supporter client_ref for all EX supporters
UPDATE `blotto_supporter`
SET `client_ref`=CONCAT('EX',`canvas_agent_ref`,'_',`id`)
WHERE `canvas_code`='EX'
;

-- Add missing players
INSERT INTO `blotto_player` (
  `started`
 ,`supporter_id`
 ,`client_ref`
 ,`first_draw_close`
 ,`letter_batch_ref`
 ,`letter_status`
 ,`chances`
)
SELECT
  CURDATE()
 ,`s`.`id`
 ,`s`.`client_ref`
 ,'2024-07-26'
 ,'EX-aborted'
 ,'received'
 ,COUNT(`t`.`ticket_number`)
FROM `tmp_cash_tickets` AS `t`
JOIN `blotto_supporter` AS `s`
  ON `s`.`id`=`t`.`supporter_id`
LEFT JOIN `blotto_player` AS `p`
       ON `p`.`supporter_id`=`s`.`id`
WHERE `t`.`rejected`=0
  AND `p`.`id` IS NULL
GROUP BY `s`.`id`
;

-- Add missing contacts
INSERT INTO `blotto_contact` (
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
 ,`postcode`
 ,`dob`
 ,`yob`
 ,`p1` -- blotto_config.blotto_org.pref_nr_email
)
SELECT
  `t`.`supporter_id`
 ,'SYSTEM'
 ,`t`.`title`
 ,`t`.`name_first`
 ,`t`.`name_last`
 ,`t`.`email`
 ,`t`.`mobile`
 ,`t`.`telephone`
 ,`t`.`address_1`
 ,`t`.`address_2`
 ,`t`.`address_3`
 ,`t`.`town`
 ,`t`.`postcode`
 ,`t`.`dob`
 ,YEAR(`t`.`dob`)
 ,IF(
    `t`.`eml`=''
   ,''
   ,IF(
      LOWER(`t`.`eml`) IN ('0','n','no')
     ,'N'
     ,'Y'
    )
  )
FROM `tmp_cash_tickets` AS `t`
JOIN `blotto_supporter` AS `s`
  ON `s`.`id`=`t`.`supporter_id`
LEFT JOIN `blotto_contact` AS `c`
       ON `c`.`supporter_id`=`s`.`id`
WHERE `t`.`rejected`=0
  AND `c`.`id` IS NULL
GROUP BY `s`.`id`
;



-- add tickets as external "mandates"
INSERT INTO `blotto_external`
(
  `ticket_number`
 ,`client_ref`
 ,`draw_closed`
)
SELECT
  `t`.`ticket_number`
 ,`s`.`client_ref`
 ,'2024-07-26'
FROM `tmp_cash_tickets` AS `t`
JOIN `blotto_supporter` AS `s`
  ON `s`.`id`=`t`.`supporter_id`
WHERE `t`.`rejected`=0
;


-- ensure that the ticket table has the right references
UPDATE `blotto_external` AS `ext`
JOIN `crucible_ticket_zaffo`.`blotto_ticket` AS `tk`
  ON `tk`.`org_id`=1
 AND `tk`.`number`=`ext`.`ticket_number`
SET `tk`.`client_ref`=`ext`.`client_ref`
;

-- add the entries directly
INSERT IGNORE INTO `blotto_entry` ( -- draw_closed,ticket_number is unique
  `draw_closed`
 ,`ticket_number`
 ,`client_ref`
)
SELECT
  null
 ,`ticket_number`
 ,`client_ref`
FROM `blotto_external`
;

