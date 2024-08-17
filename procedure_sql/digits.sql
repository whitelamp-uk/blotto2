
CREATE OR REPLACE VIEW `Digits1` AS
  SELECT 0 AS `digits` UNION ALL
  SELECT 1 UNION ALL
  SELECT 2 UNION ALL
  SELECT 3 UNION ALL
  SELECT 4 UNION ALL
  SELECT 5 UNION ALL
  SELECT 6 UNION ALL
  SELECT 7 UNION ALL
  SELECT 8 UNION ALL
  SELECT 9
  ORDER BY `digits`
;

CREATE OR REPLACE VIEW Integer1 AS
  SELECT
    1*`digits` AS `integer`
  FROM `Digits1`
  ORDER BY `integer`
;

CREATE OR REPLACE VIEW Digits2 AS
  SELECT
    CONCAT(`t1`.`digits`,`t2`.`digits`) AS `digits`
  FROM `Digits1` AS `t1`
  JOIN `Digits1` AS `t2`
    ON 1
  ORDER BY `digits`
;

CREATE OR REPLACE VIEW Integer2 AS
  SELECT
    1*CONCAT(`t1`.`digits`,`t2`.`digits`) AS `integer`
  FROM `Digits1` AS `t1`
  JOIN `Digits1` AS `t2`
    ON 1
  ORDER BY `integer`
;

CREATE OR REPLACE VIEW Digits4 AS
  SELECT
    CONCAT(`t1`.`digits`,`t2`.`digits`) AS `digits`
  FROM `Digits2` AS `t1`
  JOIN `Digits2` AS `t2`
    ON 1
  ORDER BY `digits`
;

CREATE OR REPLACE VIEW Integer4 AS
  SELECT
    1*CONCAT(`t1`.`digits`,`t2`.`digits`) AS `integer`
  FROM `Digits2` AS `t1`
  JOIN `Digits2` AS `t2`
    ON 1
  ORDER BY `integer`
;

CREATE OR REPLACE VIEW Digits6 AS
  SELECT
    CONCAT(`t1`.`digits`,`t2`.`digits`) AS `digits`
  FROM `Digits2` AS `t1`
  JOIN `Digits4` AS `t2`
    ON 1
  ORDER BY `digits`
;

CREATE OR REPLACE VIEW Integer6 AS
  SELECT
    1*CONCAT(`t1`.`digits`,`t2`.`digits`) AS `integer`
  FROM `Digits2` AS `t1`
  JOIN `Digits4` AS `t2`
    ON 1
  ORDER BY `integer`
;

