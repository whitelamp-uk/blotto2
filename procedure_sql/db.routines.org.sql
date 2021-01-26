


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
         ON `tk`.`client_ref`=`p`.`client_ref`
        AND `tk`.`org_id`={{BLOTTO_ORG_ID}}
  GROUP BY `p`.`id`
  HAVING `discrepancy`!=0
  ORDER BY `p`.`started`,`p`.`client_ref`
  ;
END$$



DELIMITER ;


