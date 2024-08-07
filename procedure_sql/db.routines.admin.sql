

USE `{{BLOTTO_CONFIG_DB}}`
;



DELIMITER $$
DROP PROCEDURE IF EXISTS `allWinsForWise`$$
CREATE PROCEDURE `allWinsForWise`(IN `draw_closed` date)
BEGIN
  DROP TABLE IF EXISTS `tmpWinsForWise`;
  CREATE TABLE `tmpWinsForWise` (
    `name` varchar(255)
   ,`recipientEmail` varchar(255)
   ,`paymentReference` varchar(64)
   ,`receiverType` varchar(6)
   ,`amountCurrency` varchar(6)
   ,`amount` int(11) unsigned
   ,`sourceCurrency` varchar(3)
   ,`targetCurrency` varchar(3)
   ,`sortCode` char(16)
   ,`accountNumber` char(16)
  );
  INSERT INTO `tmpWinsForWise`
    SELECT 
      `w`.`name`
     ,`w`.`recipientEmail`
     ,CONCAT(`o`.`winnings_payment_ref`,SUBSTR(draw_closed,9,2),'/',SUBSTR(draw_closed,6,2))
     ,`w`.`receiverType`
     ,`w`.`amountCurrency`
     ,`w`.`amount`
     ,`w`.`sourceCurrency`
     ,`w`.`targetCurrency`
     ,`w`.`sortCode`
     ,`w`.`accountNumber`
    FROM `crucible2_bwh`.`WinsForWise` AS `w`
      JOIN `blotto_config`.`blotto_org` as `o`
        ON `o`.`org_code` = 'BWH'
    WHERE `w`.`draw_closed` = draw_closed
  UNION ALL
    SELECT 
      `w`.`name`
     ,`w`.`recipientEmail`
     ,CONCAT(`o`.`winnings_payment_ref`,SUBSTR(draw_closed,9,2),'/',SUBSTR(draw_closed,6,2))
     ,`w`.`receiverType`
     ,`w`.`amountCurrency`
     ,`w`.`amount`
     ,`w`.`sourceCurrency`
     ,`w`.`targetCurrency`
     ,`w`.`sortCode`
     ,`w`.`accountNumber`
    FROM `crucible2_dbh`.`WinsForWise` AS `w`
      JOIN `blotto_config`.`blotto_org` as `o`
        ON `o`.`org_code` = 'DBH'
    WHERE `w`.`draw_closed` = draw_closed
  UNION ALL
    SELECT 
      `w`.`name`
     ,`w`.`recipientEmail`
     ,CONCAT(`o`.`winnings_payment_ref`,SUBSTR(draw_closed,9,2),'/',SUBSTR(draw_closed,6,2))
     ,`w`.`receiverType`
     ,`w`.`amountCurrency`
     ,`w`.`amount`
     ,`w`.`sourceCurrency`
     ,`w`.`targetCurrency`
     ,`w`.`sortCode`
     ,`w`.`accountNumber`
    FROM `crucible2_whh`.`WinsForWise` AS `w`
      JOIN `blotto_config`.`blotto_org` as `o`
        ON `o`.`org_code` = 'WHH'
    WHERE `w`.`draw_closed` = draw_closed
    ;
  SELECT * FROM `tmpWinsForWise`
  ;
END$$

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
   ,ROUND(AVG(`r`.`cancellations_total`),2) AS `cancellations_percent_avg`
  FROM (
    SELECT
      `month_nr`
     ,`cancellations_total`
    FROM `blotto_retention`
    GROUP BY `month_nr`
  ) AS `r`
  WHERE `r`.`month_nr`>=fromMonthNr
    AND `r`.`month_nr`<=thruMonthNr
  ;
  SELECT
    fromMonthNr as `From month`
   ,thruMonthNr as `thru month`
   ,`months_retained`
   ,ROUND(AVG(`cancellations_normalised`),2) AS `cancellations_percent_avg`
  FROM `blotto_retention`
  WHERE `month_nr`>=fromMonthNr
    AND `month_nr`<=thruMonthNr
  GROUP BY `months_retained`
  ;
END$$


DELIMITER $$
DROP PROCEDURE IF EXISTS `blottoRetentionPeriodically`$$
CREATE PROCEDURE `blottoRetentionPeriodically` (
)
BEGIN
  SELECT
    ROUND(1+(`month_nr`-(`month_nr`%12))/12) AS `Year nr`
   ,ROUND(AVG(`cancellations_normalised`),2) AS `Avg cancellations %`
  FROM `blotto_retention`
  GROUP BY `Year nr`
  ;
  SELECT
    1+(`month_nr`%12) AS `Month nr`
   ,ROUND(AVG(`cancellations_normalised`),2) AS `Avg cancellations %`
  FROM `blotto_retention`
  GROUP BY `Month nr`
  ;
  SELECT
    ROUND(1+(`month_nr`-(`month_nr`%12))/12) AS `Year nr`
   ,1+(`month_nr`%12) AS `Month nr`
   ,ROUND(AVG(`cancellations_normalised`),2) AS `Avg cancellations %`
  FROM `blotto_retention`
  GROUP BY `Year nr`,`Month nr`
  ;
END$$


DELIMITER $$
DROP PROCEDURE IF EXISTS `blottoRetentionTruncate`$$
CREATE PROCEDURE `blottoRetentionTruncate` (
)
BEGIN
  TRUNCATE `blotto_retention`
  ;
  SELECT 'blotto_retention has been truncated' AS `Done`
  ;
END$$


DELIMITER $$
DROP PROCEDURE IF EXISTS `blottoUser`$$
/*
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
*/


DELIMITER $$
DROP PROCEDURE IF EXISTS `blottoBrandPasswordReset`$$
CREATE PROCEDURE `blottoBrandPasswordReset` (
  IN    `org` char(16) character set ascii
 ,IN    `un` varchar(64) character set ascii
 ,IN    `pw` varchar(255) character set utf8
)
BEGIN
  DECLARE CUSTOM_EXCEPTION CONDITION FOR SQLSTATE '45000'
  ;
  SELECT COUNT(*) INTO @found
  FROM `blotto_user`
  WHERE `org_code`=org
    AND `username`=un
  ;
  IF !@found THEN
    SET @msg = CONCAT('org_code=',org,', username=',un,' not recognised')
    ;
    SIGNAL CUSTOM_EXCEPTION SET MESSAGE_TEXT = @msg
    ;
  END IF
  ;
  CALL `crxPasswordReset`(un,pw)
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
      WHERE `draw_closed` IS NOT NULL
      GROUP BY `client_ref`
  ) AS `f`
  LEFT JOIN (
    SELECT
      `client_ref`
     ,COUNT(`id`) AS `ppt`
    FROM `blotto_entry`
    WHERE `draw_closed` IS NOT NULL
    GROUP BY `client_ref`
  ) AS `p`
    ON `p`.`client_ref`=`f`.`client_ref`
  LEFT JOIN (
    SELECT
      `e`.`client_ref`
     ,COUNT(`e`.`id`) AS `ppt`
     ,MIN(`e`.`draw_closed`) AS `first_play`
    FROM `blotto_entry` AS `e`
    JOIN `Cancellations` AS `cn`
      ON `cn`.`client_ref`=`e`.`client_ref`
    WHERE `e`.`draw_closed` IS NOT NULL
    GROUP BY `client_ref`
    HAVING `ppt`<3
  ) AS `c`
    ON `c`.`client_ref`=`f`.`client_ref`
  LEFT JOIN (
    SELECT
      `client_ref`
     ,COUNT(`id`) AS `ppt`
    FROM `blotto_entry`
    WHERE `draw_closed` IS NOT NULL
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


