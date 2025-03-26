

USE `{{BLOTTO_CONFIG_DB}}`
;

DELIMITER $$
DROP PROCEDURE IF EXISTS `allWinsForWise2`$$
CREATE PROCEDURE `allWinsForWise2`(IN `draw_closed` date)
BEGIN
    DECLARE done INT DEFAULT 0;
    DECLARE db VARCHAR(255);
    DECLARE crxDBs CURSOR FOR SELECT schema_name FROM information_schema.schemata WHERE schema_name LIKE 'crucible2\____';
    DECLARE CONTINUE HANDLER FOR NOT FOUND SET done = 1;
    SET @q = '';
    OPEN crxDBs;
    REPEAT
        FETCH crxDBs INTO db;
        IF NOT done THEN
          SET @org = UPPER(SUBSTR(db,11,3));
          IF LENGTH(@q) > 0 THEN 
            SET @q = CONCAT(@q, ' UNION ALL ');
          END IF;
          SET @q = CONCAT(@q,
            "SELECT 
              `w`.`name` AS name
             ,`w`.`recipientEmail` AS recipientEmail
             ,CONCAT(`o`.`winnings_payment_ref`,SUBSTR('",draw_closed,"',9,2),\'/\',SUBSTR('",draw_closed,"',6,2)) AS paymentReference
             ,`w`.`receiverType` AS receiverType
             ,`w`.`amountCurrency` AS amountCurrency
             ,`w`.`amount` AS amount
             ,`w`.`sourceCurrency` AS sourceCurrency
             ,`w`.`targetCurrency` AS targetCurrency
             ,`w`.`sortCode` AS sortCode
             ,`w`.`accountNumber` AS accountNumber
             ,`o`.`org_code`
            FROM `",db,"`.`WinsForWise` AS `w`
              JOIN `blotto_config`.`blotto_org` as `o`
                ON `o`.`org_code` = '",@org,"'
            WHERE `w`.`draw_closed` = '",draw_closed,"'
            AND `o`.`winnings_payment_ref` IS NOT NULL
            "
          );
        END IF;
    UNTIL done END REPEAT;
    PREPARE stmt FROM @q;
    EXECUTE stmt;
    DEALLOCATE PREPARE stmt;
    CLOSE crxDBs;
END$$

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

-- what is this for?????
DELIMITER $$
DROP PROCEDURE IF EXISTS `integers`$$
CREATE PROCEDURE `integers` (
  IN      `min` mediumint(6) unsigned
 ,IN      `max` mediumint(6) unsigned
)
labelLeave :
BEGIN
  DROP TABLE IF EXISTS `Integers`
  ;
  IF min<=max THEN
    SET @lo = min
    ;
    SET @hi = max
    ;
  ELSE
    SET @lo = max
    ;
    SET @hi = min
    ;
  END IF
  ;
  CREATE OR REPLACE VIEW `Integer1` AS
    SELECT 0 AS `integer`
    UNION ALL SELECT 1
    UNION ALL SELECT 2
    UNION ALL SELECT 3
    UNION ALL SELECT 4
    UNION ALL SELECT 5
    UNION ALL SELECT 6
    UNION ALL SELECT 7
    UNION ALL SELECT 8
    UNION ALL SELECT 9
    ORDER BY `integer`
  ;
  IF @hi<10 THEN
    CREATE TABLE `Integers` AS
    SELECT * FROM `Integer1`
    WHERE `integer`>=@lo
      AND `integer`<=@hi
    ORDER BY `integer`
    ;
    CALL integersTidy()
    ;
    LEAVE labelLeave
    ;
  END IF
  ;
  CREATE OR REPLACE VIEW `Integer2` AS
    SELECT
      1*CONCAT(`t1`.`integer`,`t2`.`integer`) AS `integer`
    FROM `Integer1` AS `t1`
    JOIN `Integer1` AS `t2`
      ON 1
    ORDER BY `integer`
  ;
  IF @hi<100 THEN
    CREATE TABLE `Integers` AS
    SELECT * FROM `Integer2`
    WHERE `integer`>=@lo
      AND `integer`<=@hi
      ORDER BY `integer`
    ;
    CALL integersTidy()
    ;
    LEAVE labelLeave
    ;
  END IF
  ;
  CREATE OR REPLACE VIEW Integer4 AS
    SELECT
      1*CONCAT(`t1`.`integer`,`t2`.`integer`) AS `integer`
    FROM `Integer2` AS `t1`
    JOIN `Integer2` AS `t2`
      ON 1
    ORDER BY `integer`
  ;
  IF @hi<10000 THEN
    CREATE TABLE `Integers` AS
    SELECT * FROM `Integer4`
    WHERE `integer`>=@lo
      AND `integer`<=@hi
      ORDER BY `integer`
    ;
    CALL integersTidy()
    ;
    LEAVE labelLeave
    ;
  END IF
  ;
  CREATE OR REPLACE VIEW Integer6 AS
    SELECT
      1*CONCAT(`t1`.`integer`,`t2`.`integer`) AS `integer`
    FROM `Integer2` AS `t1`
    JOIN `Integer4` AS `t2`
      ON 1
    ORDER BY `integer`
  ;
  CREATE TABLE `Integers` AS
  SELECT * FROM `Integer4`
  WHERE `integer`>=@lo
    AND `integer`<=@hi
    ORDER BY `integer`
  ;
  CALL integersTidy()
  ;
END labelLeave$$


DELIMITER $$
DROP PROCEDURE IF EXISTS `integersTidy`$$
CREATE PROCEDURE `integersTidy` (
)
BEGIN
  DROP VIEW IF EXISTS `Integer1`
  ;
  DROP VIEW IF EXISTS `Integer2`
  ;
  DROP VIEW IF EXISTS `Integer4`
  ;
  DROP VIEW IF EXISTS `Integer6`
  ;
END$$

DELIMITER $$
DROP PROCEDURE IF EXISTS `newSupporters`$$
CREATE PROCEDURE `newSupporters`()
BEGIN
    DECLARE done INT DEFAULT 0;
    DECLARE db VARCHAR(255);
    DECLARE crxDBs CURSOR FOR SELECT schema_name FROM information_schema.schemata WHERE schema_name LIKE 'crucible2\____';
    DECLARE CONTINUE HANDLER FOR NOT FOUND SET done = 1;

    OPEN crxDBs;
    REPEAT
        FETCH crxDBs INTO db;
        IF NOT done THEN
          SELECT db;
          SET @q = CONCAT('SELECT dayname(substr( `inserted`,1,10)) as day,substr( `inserted`,1,10) as date, COUNT(*) FROM ', 
            db, '.`blotto_supporter` GROUP BY date ORDER BY date DESC LIMIT 10');
          PREPARE stmt FROM @q;
          EXECUTE stmt;
          DEALLOCATE PREPARE stmt;
        END IF;
    UNTIL done END REPEAT;
    CLOSE crxDBs;
END$$



DELIMITER $$
DROP PROCEDURE IF EXISTS `newSupporters2`$$
CREATE PROCEDURE `newSupporters2`()
BEGIN
    DECLARE done INT DEFAULT 0;
    DECLARE db VARCHAR(255);
    DECLARE crxDBs CURSOR FOR SELECT schema_name FROM information_schema.schemata WHERE schema_name LIKE 'crucible2\____';
    DECLARE CONTINUE HANDLER FOR NOT FOUND SET done = 1;
    SET @q = '';
    OPEN crxDBs;
    REPEAT
        FETCH crxDBs INTO db;
        IF NOT done THEN
          IF LENGTH(@q) > 0 THEN 
            SET @q = CONCAT(@q, ' UNION ALL ');
          END IF;
          SET @q = CONCAT(@q,'SELECT \'', SUBSTR(db,11,3),'\' AS org, dayname(substr( `inserted`,1,10)) as day,substr( `inserted`,1,10) as date, COUNT(*) FROM ', 
            db, '.`blotto_supporter` GROUP BY date ');
        END IF;
    UNTIL done END REPEAT;

    SET @q = CONCAT(@q, ' ORDER BY date DESC, org LIMIT 45');
    PREPARE stmt FROM @q;
    EXECUTE stmt;
    DEALLOCATE PREPARE stmt;
    CLOSE crxDBs;
END$$

DELIMITER $$
DROP PROCEDURE IF EXISTS `queryAll`$$
CREATE PROCEDURE `queryAll`(
IN `qry` TEXT
)
BEGIN
    DECLARE done INT DEFAULT 0;
    DECLARE db VARCHAR(255);
    DECLARE crxDBs CURSOR FOR SELECT schema_name FROM information_schema.schemata WHERE schema_name LIKE 'crucible2\____';
    DECLARE CONTINUE HANDLER FOR NOT FOUND SET done = 1;
    DECLARE CONTINUE HANDLER FOR SQLEXCEPTION BEGIN END;  -- in case query fails because db doesn't contain e.g. rsm_mandate
    OPEN crxDBs;
    REPEAT
        FETCH crxDBs INTO db;
        IF NOT done THEN
          SELECT db;
          SET @q = REGEXP_REPLACE(`qry`, '(?i)FROM ', CONCAT('FROM ',db,'.'));
          SET @q = REGEXP_REPLACE(@q, '(?i)JOIN ', CONCAT('JOIN ',db,'.'));
          SET @q = REGEXP_REPLACE(@q, '(?i)UPDATE ', CONCAT('UPDATE ',db,'.'));
          SET @q = REGEXP_REPLACE(@q, '(?i)INSERT INTO ', CONCAT('INSERT INTO ',db,'.'));
          PREPARE stmt FROM @q;
          EXECUTE stmt;
          DEALLOCATE PREPARE stmt;
        END IF;
    UNTIL done END REPEAT;
    CLOSE crxDBs;
END$$


DELIMITER ;


