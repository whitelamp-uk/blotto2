

USE `{{BLOTTO_MAKE_DB}}`
;

DELIMITER $$
DROP PROCEDURE IF EXISTS `drawsRBE`$$
CREATE PROCEDURE `drawsRBE` (
)
BEGIN
  DROP TABLE IF EXISTS `Draws`
  ;
  CREATE TABLE `Draws` AS
    SELECT
      `e`.`draw_closed`
     ,`e`.`client_ref`
     ,`e`.`ticket_number`
     ,'' AS `title`
     ,'' AS `name_first`
     ,'' AS `name_last`
     ,'' AS `email`
     ,'' AS `mobile`
     ,'' AS `telephone`
     ,'' AS `address_1`
     ,'' AS `address_2`
     ,'' AS `address_3`
     ,'' AS `town`
     ,'' AS `county`
     ,'' AS `postcode`
     ,'' AS `payment_provider`
     ,'' AS `payment_ref_no`
     ,'' AS `payment_name`
     ,'' AS `payment_sortcode`
     ,'' AS `payment_account`
     ,'' AS `payment_frequency`
     ,'' AS `payment_amount`
     ,'' AS `mandate_created`
     ,'' AS `mandate_startdate`
    FROM `blotto_entry` AS `e`
    GROUP BY `e`.`draw_closed`,`e`.`ticket_number`
    ORDER BY `e`.`draw_closed`,`e`.`client_ref`,`e`.`ticket_number`
  ;
  ALTER TABLE `Draws`
  ADD PRIMARY KEY (`draw_closed`,`ticket_number`)
  ;
  ALTER TABLE `Draws`
  ADD KEY `draw_closed` (`draw_closed`)
  ;
  ALTER TABLE `Draws`
  ADD KEY `client_ref` (`client_ref`)
  ;
  ALTER TABLE `Draws`
  ADD KEY `ticket_number` (`ticket_number`)
  ;
END$$


DELIMITER $$
DROP PROCEDURE IF EXISTS `drawsSummariseRBE`$$
CREATE PROCEDURE `drawsSummariseRBE` (
)
BEGIN
  DROP TABLE IF EXISTS `Draws_Summary`
  ;
  CREATE TABLE `Draws_Summary` AS
    SELECT
      `e`.`draw_closed`
     ,'rbe' AS `ccc`
     ,COUNT(DISTINCT(`e`.`client_ref`)) AS `supporters_entered`
     ,COUNT(`e`.`id`) AS `tickets_entered`
    FROM `blotto_entry` AS `e`
    WHERE `e`.`draw_closed`<CURDATE()
    GROUP BY `draw_closed`,`ccc`
    ORDER BY `draw_closed`,`ccc`
  ;
  ALTER TABLE `Draws_Summary`
  ADD PRIMARY KEY (`draw_closed`,`ccc`)
  ;
  DROP TABLE IF EXISTS `Draws_Supersummary`
  ;
  CREATE TABLE `Draws_Supersummary` AS
    SELECT
      `draw_closed`
     ,SUM(`supporters_entered`) AS `supporters_entered`
     ,SUM(`tickets_entered`) AS `tickets_entered`
    FROM `Draws_Summary`
    GROUP BY `draw_closed`
    ORDER BY `draw_closed`
  ;
  ALTER TABLE `Draws_Supersummary`
  ADD PRIMARY KEY (`draw_closed`)
  ;
END$$


DELIMITER $$
DROP PROCEDURE IF EXISTS `zaffoSuperdrawEntries`$$
CREATE PROCEDURE `zaffoSuperdrawEntries` (
    IN      month char(7)
   ,IN      draw_date date
)
BEGIN
  CREATE TABLE IF NOT EXISTS `ZaffoSuperdraw` (
    `entry_urn` bigint(20) unsigned NOT NULL,
    `draw_date` date DEFAULT NULL,
    `player_urn` varchar(64) CHARACTER SET ascii DEFAULT NULL,
    `ticket_number` char(8) CHARACTER SET ascii DEFAULT NULL,
    PRIMARY KEY (`entry_urn`),
    UNIQUE KEY `draw_date_ticket_number` (`draw_date`,`ticket_number`),
    KEY `draw_date` (`draw_date`),
    KEY `player_urn` (`player_urn`),
    KEY `ticket_number` (`ticket_number`)
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8
  ;
  INSERT IGNORE INTO `ZaffoSuperdraw`
    SELECT
      CONVERT(
        CONCAT(DATE_FORMAT(draw_date,'%Y%m%d'),`ticket`.`ticket_number`)
       ,unsigned integer
      ) AS `entry_urn`
     ,draw_date AS `draw_date`
     ,CONCAT(UPPER('{{BLOTTO_ORG_USER}}'),'-',`player`.`client_ref`) AS `player_urn`
     ,CONCAT('T-',`ticket`.`ticket_number`) AS `ticket_number`
    FROM (
      SELECT
        COUNT(DISTINCT `draw_closed`) AS `draws_in_month`
      FROM `blotto_entry`
      WHERE SUBSTR(`draw_closed`,1,7)=month
    ) AS `draw`
    JOIN (
      SELECT
        `client_ref`
       ,COUNT(DISTINCT `draw_closed`) AS `entered_in_month`
      FROM `blotto_entry`
      WHERE SUBSTR(`draw_closed`,1,7)=month
      GROUP BY `client_ref`
    ) AS `player`
      ON `player`.`entered_in_month`=`draw`.`draws_in_month`
    JOIN (
      SELECT
        `client_ref`
       ,`ticket_number`
      FROM `blotto_entry`
      WHERE SUBSTR(`draw_closed`,1,7)=month
    ) AS `ticket`
      ON `ticket`.`client_ref`=`player`.`client_ref`
  ;
END$$


