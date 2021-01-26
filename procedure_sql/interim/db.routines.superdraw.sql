

USE `{{BLOTTO_MAKE_DB}}`
;


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


