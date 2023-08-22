

USE `{{BLOTTO_CONFIG_DB}}`
;


DELIMITER $$
DROP PROCEDURE IF EXISTS `blottoRetention`$$
CREATE PROCEDURE `blottoRetention` (
  IN    `fromMonthNr` int(11) unsigned
 ,IN    `thruMonthNr` int(11) unsigned
)
BEGIN
  SELECT
    fromMonthNr as `From month`
   ,thruMonthNr as `thru month`
   ,ROUND(AVG(`r`.`cancellations_normalised`),2) AS `cancellations_percent_avg`
  FROM `blotto_retention` AS `r`
  JOIN (
    SELECT
      IFNULL(CONCAT(MIN(`month`),'-01'),CURDATE()) AS `start`
    FROM `blotto_retention`
  ) AS `m`
  WHERE TIMESTAMPDIFF(MONTH,`m`.`start`,DATE(CONCAT(`r`.`month`,'-01')))>=fromMonthNr
    AND TIMESTAMPDIFF(MONTH,`m`.`start`,DATE(CONCAT(`r`.`month`,'-01')))<=thruMonthNr
  ;
  SELECT
    fromMonthNr as `From month`
   ,thruMonthNr as `thru month`
   ,IF(`r`.`months_retained`<0,0,`r`.`months_retained`) AS `retention_months`
   ,ROUND(AVG(`r`.`cancellations_normalised`),2) AS `cancellations_percent_avg`
  FROM `blotto_retention` AS `r`
  JOIN (
    SELECT
      IFNULL(CONCAT(MIN(`month`),'-01'),CURDATE()) AS `start`
    FROM `blotto_retention`
  ) AS `m`
  WHERE TIMESTAMPDIFF(MONTH,`m`.`start`,DATE(CONCAT(`r`.`month`,'-01')))>=fromMonthNr
    AND TIMESTAMPDIFF(MONTH,`m`.`start`,DATE(CONCAT(`r`.`month`,'-01')))<=thruMonthNr
  GROUP BY `retention_months`
  ;
END$$


DELIMITER $$
DROP PROCEDURE IF EXISTS `blottoUser`$$
CREATE PROCEDURE `blottoUser` (
  IN    `orgCode` varchar(64) character set ascii
 ,IN    `userName` varchar(64) character set ascii
 ,IN    `passwd` varchar(255) character set utf8
)
BEGIN
  DECLARE CUSTOM_EXCEPTION CONDITION FOR SQLSTATE '45000'
  ;
  SET @org = ( SELECT `org_code` FROM `blotto_org` WHERE `org_code`=UPPER(orgCode) LIMIT 0,1 )
  ;
  IF @org IS NULL THEN
    SET @msg = CONCAT('Organisation code ',orgCode,' not recognised')
    ;
    SIGNAL CUSTOM_EXCEPTION SET MESSAGE_TEXT = @msg
    ;
  END IF
  ;
  IF userName NOT REGEXP '^[a-z]+$' THEN
    SET @msg = 'User name is restricted to lower case letters'
    ;
    SIGNAL CUSTOM_EXCEPTION SET MESSAGE_TEXT = @msg
    ;
  END IF
  ;
  IF LENGTH(passwd)<8 THEN
    SET @msg = 'Password must be at least 8 characters'
    ;
    SIGNAL CUSTOM_EXCEPTION SET MESSAGE_TEXT = @msg
    ;
  END IF
  ;
  SET @org = LOWER(@org);
  SET @usr = CONCAT(@org,LOWER(userName));
  SELECT CONCAT('CREATE USER IF NOT EXISTS \'',@usr,'\'@\'localhost\';') AS `Statements`
  UNION
  SELECT CONCAT('SET PASSWORD FOR \'',@usr,'\'@\'localhost\'=\'',PASSWORD(passwd),'\';') AS `Statements`
  UNION
  SELECT CONCAT('GRANT \'',@org,'\' TO \'',@usr,'\'@\'localhost\';') AS `Statements`
  UNION
  SELECT CONCAT('SET DEFAULT ROLE \'',@org,'\' FOR \'',@usr,'\'@\'localhost\';') AS `Statements`
  ;
END$$



DELIMITER ;



USE `{{BLOTTO_MAKE_DB}}`
;




DELIMITER $$
DROP PROCEDURE IF EXISTS `babCanvasserPerformance`$$
CREATE PROCEDURE `babCanvasserPerformance` (
)
BEGIN
  SELECT
      `f`.`first_play`
     ,`f`.`badge`
     ,COUNT(IFNULL(`p`.`ppt`,0)) AS `players_total`
     ,SUM(IF(`c`.`ppt`=0,1,0)) AS `players_played_0`
     ,SUM(IF(`c`.`ppt`=1,1,0)) AS `players_played_1`
     ,SUM(IF(`c`.`ppt`=2,1,0)) AS `players_played_2`
     ,ROUND(100*IF(`p`.`ppt` IS NULL,0,IFNULL(COUNT(`c`.`ppt`),0)/COUNT(`p`.`ppt`)),1) AS `cancellation_pct`
     ,SUM(IF(`p`.`ppt`>=3,1,0)) AS `players_played_3_or_more`
     ,ROUND(AVG(IFNULL(`pa`.`ppt`,0)),1) AS `ppt_3_or_more_average`
  FROM (
    SELECT
      MIN(`draw_closed`) AS `first_play`
     ,SUBSTR(`client_ref`,3,4) AS `badge`
     ,`client_ref`
      FROM `blotto_entry`
      GROUP BY `client_ref`
  ) AS `f`
  LEFT JOIN (
    SELECT
      `client_ref`
     ,COUNT(`id`) AS `ppt`
    FROM `blotto_entry`
    GROUP BY `client_ref`
  ) AS `p`
    ON `p`.`client_ref`=`f`.`client_ref`
  LEFT JOIN (
    SELECT
      `client_ref`
     ,COUNT(`id`) AS `ppt`
     ,MIN(`draw_closed`) AS `first_play`
    FROM `blotto_entry` AS `e`
    JOIN `Cancellations` AS `cn`
      ON `cn`.`ClientRef`=`e`.`client_ref`
    GROUP BY `client_ref`
    HAVING `ppt`<3
  ) AS `c`
    ON `c`.`client_ref`=`f`.`client_ref`
  LEFT JOIN (
    SELECT
      `client_ref`
     ,COUNT(`id`) AS `ppt`
    FROM `blotto_entry`
    GROUP BY `client_ref`
    HAVING `ppt`>=3
  ) AS `pa`
    ON `pa`.`client_ref`=`f`.`client_ref`
  WHERE `f`.`client_ref` LIKE 'BB%'
  GROUP BY `f`.`badge`
  ORDER BY `f`.`first_play`
  ;
END$$


DELIMITER ;


