

SELECT
  `c`.*
FROM (
  SELECT
    'draw_closed' AS `draw_closed`
   ,'prize_level' AS `prize_level`
   ,'number' AS `number`
  UNION
  SELECT
    `r`.*
  FROM (
    SELECT
      `draw_closed`
     ,`prize_level`
     ,`number`
    FROM `{{BLOTTO_RESULTS_DB}}`.`blotto_result`
    ORDER BY `draw_closed`,`prize_level`,`number`
  ) AS `r`
) AS `c`
INTO OUTFILE '{{BLOTTO_OUTFILE}}'
FIELDS TERMINATED BY ',' ENCLOSED BY '\"'
LINES TERMINATED BY '\n'
;


