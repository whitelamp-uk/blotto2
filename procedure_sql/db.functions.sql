

USE `{{BLOTTO_MAKE_DB}}`
;



-- Obsolete function
DELIMITER $$
DROP FUNCTION IF EXISTS `accountChances2Tickets`$$


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
  SET cd = DATE_ADD(cd,INTERVAL {{BLOTTO_CANCEL_RULE}})
  ;
  -- Allow for BACS jitter
  SET cd = DATE_ADD(cd,INTERVAL 7 DAY)
  ;
  RETURN cd
  ;
END$$


DELIMITER $$
DROP FUNCTION IF EXISTS `dateSensible2Silly`$$
CREATE FUNCTION `dateSensible2Silly` (
  d char(10) character set ascii
) RETURNS char(10) character set ascii DETERMINISTIC
BEGIN
  IF d NOT LIKE '____-__-__' AND d NOT LIKE '__/__/____' THEN
    RETURN null
    ;
  END IF
  ;
  IF d LIKE '__/__/____' THEN
    RETURN d
    ;
  END IF
  ;
  RETURN CONCAT_WS('/',SUBSTR(d,9,2),SUBSTR(d,6,2),SUBSTR(d,1,4))
  ;
END$$


DELIMITER $$
DROP FUNCTION IF EXISTS `dateSilly2Sensible`$$
CREATE FUNCTION `dateSilly2Sensible` (
  d char(10) character set ascii
) RETURNS char(10) character set ascii DETERMINISTIC
BEGIN
  IF d NOT LIKE '____-__-__' AND d NOT LIKE '__/__/____' THEN
    RETURN null
    ;
  END IF
  ;
  IF d LIKE '____-__-__' THEN
    RETURN d
    ;
  END IF
  ;
  RETURN CONCAT_WS('-',SUBSTR(d,7,4),SUBSTR(d,4,2),SUBSTR(d,1,2))
  ;
END$$


DELIMITER $$
DROP FUNCTION IF EXISTS `dayOne`$$
CREATE FUNCTION `dayOne`(
) RETURNS date DETERMINISTIC
BEGIN
  /*
  funny things can happen in early-days lottoland
  things might start off in not quite the right
  order with questionable data referencing eg
   - migrating existing players
   - back log of early imports
  to offer reporting a common long-term-stable "start
  date" here we give a deterministic answer
  */
  SET @dt = CURDATE();
  SELECT
    IFNULL(MIN(`created`),@dt) INTO @s1
  FROM `blotto_supporter`
  ;
  SELECT
    IFNULL(MIN(`StartDate`),@dt) INTO @m1
  FROM `blotto_build_mandate`
  ;
  SELECT
    IFNULL(MIN(`DateDue`),@dt) INTO @c1
  FROM `blotto_build_collection`
  ;
  SELECT
    IFNULL(MIN(`draw_closed`),@dt) INTO @e1
  FROM `blotto_entry`
  ;
  -- ooh a new trick to get min value from "array" of primitives
  SET @d1 = LEAST(@s1,@m1,@c1,@e1)
  ;
  IF @d1<@dt THEN
    RETURN @d1
    ;
  END IF
  ;
  RETURN null
  ;
END$$


DELIMITER $$
DROP FUNCTION IF EXISTS `digitsOnly`$$
CREATE FUNCTION `digitsOnly`(
  `str` varchar(255)
) RETURNS varchar(255) CHARSET utf8 DETERMINISTIC
BEGIN
  DECLARE i, len SMALLINT DEFAULT 1;
  DECLARE ret CHAR(32) DEFAULT '';
  DECLARE c CHAR(1);
  IF str IS NULL
  THEN 
    RETURN "";
  END IF;
  SET len=CHAR_LENGTH(str);
  REPEAT
    BEGIN
      SET c=MID(str,i,1);
      IF c BETWEEN '0' AND '9' THEN 
        SET ret=CONCAT(ret,c);
      END IF;
      SET i=i+1;
    END;
  UNTIL i>len END REPEAT;
  RETURN ret;
END$$


DELIMITER $$
DROP FUNCTION IF EXISTS `feeRate`$$
CREATE FUNCTION `feeRate`(
  feeName varchar(64) character set ascii
 ,feeDate date
) RETURNS int(11) unsigned DETERMINISTIC
BEGIN
  SET @rate = 0
  ;
  SELECT
    GROUP_CONCAT(`rate` ORDER BY `starts` DESC LIMIT 1) INTO @rate
  FROM `blotto_fee`
  WHERE `fee`=feeName
    AND `starts`<=feeDate
  ;
  RETURN IFNULL(@rate,0)
  ;
END$$


DELIMITER $$
DROP FUNCTION IF EXISTS `dp`$$
CREATE FUNCTION `dp`(
  floatValue float
 ,DP int(11)
) RETURNS varchar(255) CHARACTER SET ascii DETERMINISTIC
BEGIN
  RETURN REPLACE(FORMAT(floatValue,DP),',','');
END$$


DELIMITER $$
DROP FUNCTION IF EXISTS `sfRound`$$
CREATE FUNCTION `sfRound`(
  floatValue float
 ,SF int(11)
) RETURNS varchar(255) CHARACTER SET ascii DETERMINISTIC
BEGIN
  DECLARE DP int(11);
  IF SF<1 THEN
    RETURN NULL;
  END IF;
  SET DP = LOG10(abs(floatValue));
  IF DP IS NULL THEN
    -- Float is zero
    RETURN '0';
  END IF;
  RETURN CONCAT('',ROUND(floatValue,SF-DP-1));
END$$


DELIMITER $$
DROP FUNCTION IF EXISTS `weekCommencingDate`$$
CREATE FUNCTION `weekCommencingDate`(
  day date
) RETURNS date DETERMINISTIC
BEGIN
  /*
  lottery reporting week is Sat-Fri
  */
  RETURN DATE_SUB(day,INTERVAL DAYOFWEEK(day)%7 DAY)
  ;
END$$


DELIMITER $$
DROP FUNCTION IF EXISTS `weekUnix`$$
CREATE FUNCTION `weekUnix`(
  day date
) RETURNS date DETERMINISTIC
BEGIN
  /*
  lottery reporting week is Sat-Fri
  unix week 0 Sat 1969-12-27 - Fri 1970-01-02
  */
  RETURN CEILING((UNIX_TIMESTAMP('1970-01-03')-5*24*60*60)/(7*24*60*60))
  ;
END$$




DELIMITER ;

