

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
  IF Freq='Single' THEN
    -- Cancel date is always in the future (never treated as cancelled)
    RETURN DATE_ADD(CURDATE(),INTERVAL 1 YEAR)
    ;
  END IF
  ;
  -- cd = late by BLOTTO_CANCEL_RULE plus processing
  SET cd = DATE_ADD(DATE_ADD(d,INTERVAL 3 DAY),INTERVAL {{BLOTTO_CANCEL_RULE}});
  IF Freq='Annually' THEN
    -- Add another year
    RETURN DATE_ADD(cd,INTERVAL 1 YEAR)
    ;
  END IF
  ;
  IF Freq='Six Monthly' THEN
    -- Add another six months
    RETURN DATE_ADD(cd,INTERVAL 6 MONTH)
    ;
  END IF
  ;
  IF Freq='Quarterly' THEN
    -- Add another 3 months
    RETURN DATE_ADD(cd,INTERVAL 3 MONTH)
    ;
  END IF
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




DELIMITER ;

