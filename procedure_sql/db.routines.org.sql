


USE `{{BLOTTO_MAKE_DB}}`
;



DELIMITER $$
DROP PROCEDURE IF EXISTS `help`$$
CREATE PROCEDURE `help` (
)
BEGIN
  SELECT
    CONCAT(`id`,'. ',`comments`) AS `Help`
  FROM `{{BLOTTO_CONFIG_DB}}`.`blotto_help`
  ORDER BY `id`
  ;
END$$


DELIMITER $$
DROP PROCEDURE IF EXISTS `mandatesHavingNoSupporter`$$
CREATE PROCEDURE `mandatesHavingNoSupporter` (
)
BEGIN
  SELECT
    COUNT(`c`.`id`) AS `collections`
   ,MIN(`c`.`DateDue`) AS `first_collected`
   ,MAX(`c`.`DateDue`) AS `last_collected`
   ,`m`.*
  FROM `blotto_build_mandate` AS `m`
  JOIN (
    SELECT
      `DDRefNo`
     ,MAX(`Created`) AS `LastCreated`
    FROM `blotto_build_mandate`
    WHERE 1
    GROUP BY `DDRefNo`
  ) AS `ml`
    ON `ml`.`DDRefNo`=`m`.`DDRefNo`
   AND `ml`.`LastCreated`=`m`.`Created`
  JOIN (
    SELECT
      `DDRefNo`
     ,`Created`
     ,MIN(`Status` IN ('DELETED','CANCELLED','FAILED')) AS `IsDead`
    FROM `blotto_build_mandate`
    GROUP BY `DDRefNo`,`Created`
  ) AS `ms`
    ON `ms`.`DDRefNo`=`m`.`DDRefNo`
   AND `ms`.`Created`=`m`.`Created`
   AND `ms`.`IsDead`=(`m`.`Status` IN ('DELETED','CANCELLED','FAILED'))
  LEFT JOIN `blotto_build_collection` AS `c`
         ON `c`.`DDRefNo`=`m`.`DDRefNo`
  LEFT JOIN (
    SELECT
      `is`.*
     ,`ip`.`client_ref` AS `player_client_ref`
    FROM `blotto_player` AS `ip`
    JOIN `blotto_supporter` AS `is`
      ON `is`.`id`=`ip`.`supporter_id`
  )      AS `s`
         ON `s`.`player_client_ref`=`m`.`ClientRef`
  WHERE `s`.`id` IS NULL
  GROUP BY `m`.`DDRefNo`
  ORDER BY `m`.`Created`
  ;
END$$


DELIMITER $$
DROP PROCEDURE IF EXISTS `retention`$$
CREATE PROCEDURE `retention` (
)
BEGIN
  DELETE FROM `{{BLOTTO_CONFIG_DB}}`.`blotto_retention`
  WHERE `org`=UPPER('{{BLOTTO_ORG_USER}}')
  ;
  INSERT INTO `{{BLOTTO_CONFIG_DB}}`.`blotto_retention`
  SELECT
    UPPER('{{BLOTTO_ORG_USER}}') AS `org`
   ,`r`.`growth`
   ,`r`.`active_supporters`
   ,`r`.`month`
   ,null AS `month_nr`
   ,`r`.`months_retained`
   ,`r`.`cancellations`
   ,`r`.`cancellations_normalised`
   ,ROUND(SUM(`r`.`cancellations_normalised`) OVER (PARTITION BY `r`.`month`),2) AS `cancellations_total`
  FROM (
    SELECT
      `candidates`.`signed_month` AS `month`
     ,`candidates`.`growth`
     ,`candidates`.`active_supporters`
     ,IFNULL(`attrition`.`months_retained`,0) AS `months_retained`
     ,IFNULL(`attrition`.`shrinkage`,0) AS `cancellations`
     ,IFNULL(ROUND(100*`attrition`.`shrinkage`/`candidates`.`active_supporters`,3),0) AS `cancellations_normalised`
    FROM (
      SELECT
        `s1`.`signed_month`
       ,COUNT(`s1`.`supporter_id`)-SUM(`s1`.`cancelled_month`=`s1`.`signed_month`) AS `growth`
       ,SUM(COUNT(`s1`.`supporter_id`)-SUM(`s1`.`cancelled_month`=`s1`.`signed_month`)) OVER (ORDER BY `s1`.`signed_month`) AS `active_supporters`
      FROM (
        SELECT
          `supporter_id`
         ,SUBSTR(`signed`,1,7) AS `signed_month`
         ,SUBSTR(`cancelled`,1,7) AS `cancelled_month`
        FROM `Supporters`
        GROUP BY `supporter_id`
      ) AS `s1`
      GROUP BY `signed_month`
    ) AS `candidates`
    LEFT JOIN (
      SELECT
        `s2`.`cancelled_month`
       ,COUNT(`s2`.`supporter_id`) AS `shrinkage`
       ,TIMESTAMPDIFF(MONTH,CONCAT(`s2`.`signed_month`,'-01'),CONCAT(`s2`.`cancelled_month`,'-01')) AS `months_retained`
      FROM (
        SELECT
          `supporter_id`
         ,SUBSTR(`signed`,1,7) AS `signed_month`
         ,SUBSTR(`cancelled`,1,7) AS `cancelled_month`
        FROM `Supporters`
        WHERE `cancelled` IS NOT NULL
          AND `cancelled`!=''
          AND `cancelled`!='0000-00-00'
        GROUP BY `supporter_id`
      ) AS `s2`
      GROUP BY `cancelled_month`,`months_retained`
    ) AS `attrition`
      ON `attrition`.`cancelled_month`=`candidates`.`signed_month`
    GROUP BY `month`,`months_retained`
  ) AS `r`
  ORDER BY `month`,`months_retained`
  ;
  UPDATE `{{BLOTTO_CONFIG_DB}}`.`blotto_retention` AS `r`
  JOIN (
    SELECT
      IFNULL(CONCAT(MIN(`month`),'-01'),CURDATE()) AS `start`
    FROM `{{BLOTTO_CONFIG_DB}}`.`blotto_retention`
    WHERE `org`=UPPER('{{BLOTTO_ORG_USER}}')
  ) AS `m`
  SET
    `month_nr`=(TIMESTAMPDIFF(MONTH,`m`.`start`,DATE(CONCAT(`r`.`month`,'-01')))+1)
  WHERE `org`=UPPER('{{BLOTTO_ORG_USER}}')
  ;
  SELECT
    *
  FROM `{{BLOTTO_CONFIG_DB}}`.`blotto_retention`
  WHERE `org`=UPPER('{{BLOTTO_ORG_USER}}')
  ORDER BY `month`,`months_retained`
  ;
END$$


DELIMITER $$
DROP PROCEDURE IF EXISTS `supportersHavingNoMandate`$$
CREATE PROCEDURE `supportersHavingNoMandate` (
)
BEGIN
  SELECT
    `s`.*
  FROM (
    SELECT
      `is`.`id`
     ,`is`.`created`
     ,`is`.`signed`
     ,`is`.`canvas_ref`
     ,`ip`.`client_ref`
     ,`ic`.`title`
     ,`ic`.`name_first`
     ,`ic`.`name_last`
     ,`ic`.`email`
     ,`ic`.`mobile`
     ,`ic`.`telephone`
     ,`ic`.`address_1`
     ,`ic`.`address_2`
     ,`ic`.`address_3`
     ,`ic`.`town`
     ,`ic`.`county`
     ,`ic`.`postcode`
     ,`ic`.`country`
    FROM `blotto_supporter` AS `is`
    JOIN `blotto_player` AS `ip`
      ON `ip`.`supporter_id`=`is`.`id`
    JOIN (
      SELECT
        `supporter_id`
        ,MAX(`created`) AS `latest`
      FROM `blotto_contact`
      GROUP BY `supporter_id`
    ) AS `il`
      ON `il`.`supporter_id`=`is`.`id`
    JOIN `blotto_contact` AS `ic`
      ON `ic`.`supporter_id`=`is`.`id`
     AND `ic`.`created`=`il`.`latest`
  )      AS `s`
  LEFT JOIN (
    SELECT
      `m`.`id`
     ,`m`.`ClientRef`
    FROM `blotto_build_mandate` AS `m`
    JOIN (
      SELECT
        `DDRefNo`
       ,MAX(`Created`) AS `LastCreated`
      FROM `blotto_build_mandate`
      WHERE 1
      GROUP BY `DDRefNo`
    ) AS `ml`
      ON `ml`.`DDRefNo`=`m`.`DDRefNo`
     AND `ml`.`LastCreated`=`m`.`Created`
    JOIN (
      SELECT
        `DDRefNo`
       ,`Created`
       ,MIN(`Status` IN ('DELETED','CANCELLED','FAILED')) AS `IsDead`
      FROM `blotto_build_mandate`
      GROUP BY `DDRefNo`,`Created`
    ) AS `ms`
      ON `ms`.`DDRefNo`=`m`.`DDRefNo`
     AND `ms`.`Created`=`m`.`Created`
     AND `ms`.`IsDead`=(`m`.`Status` IN ('DELETED','CANCELLED','FAILED'))
    GROUP BY `m`.`DDRefNo`
  )      AS `d`
         ON `d`.`ClientRef`=`s`.`client_ref`
  WHERE `d`.`id` IS NULL
  ORDER BY `s`.`id`
  ;
END$$


DELIMITER $$
DROP PROCEDURE IF EXISTS `ticketErrors`$$
CREATE PROCEDURE `ticketErrors` (
)
BEGIN
  SELECT
    `p`.*
   ,COUNT(`tk`.`number`) AS `tickets`
   ,`p`.`chances`-COUNT(`tk`.`number`) AS `discrepancy`
  FROM `blotto_player` AS `p`
  LEFT JOIN `{{BLOTTO_TICKET_DB}}`.`blotto_ticket` AS `tk`
         -- this query is good for EXT tickets too
         ON `tk`.`client_ref`=`p`.`client_ref`
        AND `tk`.`org_id`={{BLOTTO_ORG_ID}}
  GROUP BY `p`.`id`
  HAVING `discrepancy`!=0
  ORDER BY `p`.`started`,`p`.`client_ref`
  ;
END$$



DELIMITER ;


