
CREATE OR REPLACE VIEW `AgeVerify` (
  `transaction_id`
 ,`Buyer Full name`
 ,`Buyer Email`
 ,`Buyer Address 1`
 ,`Buyer Address 2`
 ,`Buyer Postcode`
 ,`Month`
) AS
  SELECT
    `client_ref_orig` AS `original_client_ref`
   ,CONCAT(`name_first`,' ',`name_last`) AS `name`
   ,`email`
   ,`address_1`
   ,`address_2`
   ,`postcode`
   ,SUBSTR(`created`,1,7) as `month`
  FROM `UpdatesLatest`
  WHERE `ccc` IN ('PBB','CDNT','STRP')
  ORDER BY `month` DESC,`original_client_ref` 
;

CREATE OR REPLACE VIEW `Hiatus` (
  `cancelled`
 ,`client_ref`
 ,`paid_amount`
 ,`times`
) AS
  SELECT
    IFNULL(`cns`.`cancelled_date`,'Active')
   ,`c1`.`ClientRef`
   ,SUM(`c1`.`PaidAmount`)
   ,COUNT(`c1`.`DateDue`) as `times`
  FROM `blotto_build_collection` as `c1`
  JOIN (
    SELECT
      `ClientRef`
     ,MIN(`DateDue`) AS `first`
    FROM `blotto_build_collection`
    GROUP BY `ClientRef`
  ) AS `cfirst`
    ON `cfirst`.`ClientRef`=`c1`.`ClientRef`
  LEFT JOIN (
    SELECT
      `client_ref`
     ,`cancelled_date`
    FROM `Cancellations`
    GROUP BY `client_ref`
  ) AS `cns`
         ON `cns`.`client_ref`=`c1`.`ClientRef`
  LEFT JOIN `blotto_build_collection` AS `c2`
         ON `c2`.`ClientRef`=`c1`.`ClientRef`
        AND `c2`.`DateDue`=DATE_SUB(`c1`.`DateDue`,INTERVAL 1 MONTH)
  WHERE `c1`.`DateDue`>`cfirst`.`first`
  AND `c2`.`ClientRef` IS NULL
  GROUP BY `ClientRef`
  ORDER BY `times` DESC,`ClientRef`
;

CREATE OR REPLACE VIEW `HiatusOverLimit` (
  `client_ref`
 ,`paid_total`
 ,`paid_last`
 ,`missed_payments`
 ,`over`
) AS
  SELECT
    `c`.`ClientRef`
   ,`csum`.`paid`
   ,`csum`.`date_due_last`
   ,3-COUNT(`c`.`DateDue`) AS `missed`
   ,'3 MONTH'
  FROM `blotto_build_collection` AS `c`
  JOIN (
    SELECT
      `ClientRef`
     ,SUM(`PaidAmount`) AS `paid`
     ,MIN(`DateDue`) AS `date_due_first`
     ,MAX(`DateDue`) AS `date_due_last`
    FROM `blotto_build_collection`
    GROUP BY `ClientRef`
    -- must be enough range to count
    HAVING `date_due_last`>=DATE_ADD(`date_due_first`,INTERVAL 2 MONTH)
  ) AS `csum`
    ON `csum`.`ClientRef`=`c`.`ClientRef`
  LEFT JOIN (
    SELECT
      `client_ref`
    FROM `Cancellations`
    GROUP BY `client_ref`
  ) AS `cns`
         ON `cns`.`client_ref`=`c`.`ClientRef`
  WHERE `cns`.`client_ref` IS NULL
    -- just the collections within the range
    AND `c`.`DateDue`>DATE_SUB(`csum`.`date_due_last`,INTERVAL 3 MONTH)
  GROUP BY `ClientRef`
  HAVING `missed`>0
  ORDER BY `missed` DESC,`paid` DESC,`ClientRef`
;

CREATE OR REPLACE VIEW `Workflow_1_Import` (
  `week_ref`
 ,`milestone`
 ,`ccc`
 ,`supporters`
 ,`tickets`
 ,`first_one_created`
 ,`last_one_created`
) AS
  SELECT
    CONCAT(YEAR(`created`),'-',LPAD(WEEK(`created`),2,'0')) AS `week`
   ,'created'
   ,`ccc`
   ,COUNT(DISTINCT `sort2_supporter_id`)
   ,SUM(`tickets`)
   ,MIN(`created`)
   ,MAX(`created`)
  FROM `UpdatesLatest`
  GROUP BY `week`,`ccc`
  ORDER BY `week` DESC,`ccc`
;

CREATE OR REPLACE VIEW `Workflow_2_DD` (
  `week_ref`
 ,`first_collected_on`
 ,`milestone`
 ,`ccc`
 ,`supporters`
 ,`tickets`
 ,`first_one_created`
 ,`last_one_created`
) AS
  SELECT
    CONCAT(YEAR(`first_collected`),'-',LPAD(WEEK(`first_collected`),2,'0')) AS `week`
   ,`first_collected`
   ,'first_collection'
   ,`ccc`
   ,COUNT(DISTINCT `sort2_supporter_id`)
   ,SUM(`tickets`)
   ,MIN(`created`)
   ,MAX(`created`)
  FROM `UpdatesLatest`
  WHERE `first_collected`!='0000-00-00'
  GROUP BY `first_collected`,`ccc`
  ORDER BY `first_collected` DESC,`ccc`
;

CREATE OR REPLACE VIEW `Workflow_3_DD` (
  `week_ref`
 ,`first_entered_on`
 ,`milestone`
 ,`ccc`
 ,`supporters`
 ,`tickets`
 ,`first_one_created`
 ,`last_one_created`
) AS
  SELECT
    CONCAT(YEAR(`es`.`first_draw_closed`),'-',LPAD(WEEK(`es`.`first_draw_closed`),2,'0')) AS `week`
   ,`es`.`first_draw_closed`
   ,'first_entered'
   ,`s`.`canvas_code`
   ,COUNT(`s`.`id`)
   ,SUM(`es`.`tickets`)
   ,MIN(`s`.`created`)
   ,MAX(`s`.`created`)
  FROM (
    SELECT
      `p`.`supporter_id`
     ,MIN(`draw_closed`) AS `first_draw_closed`
     ,COUNT(`e`.`id`) AS `tickets`
    FROM `blotto_entry` AS `e`
    JOIN `blotto_player` AS `p`
      ON `p`.`client_ref`=`e`.`client_ref`
    WHERE `e`.`draw_closed` IS NOT NULL
    GROUP BY `supporter_id`
  ) AS `es`
  JOIN `blotto_supporter` AS `s`
    ON `s`.`id`=`es`.`supporter_id`
  GROUP BY `first_draw_closed`,`canvas_code`
  ORDER BY `first_draw_closed` DESC,`canvas_code`
;

CREATE OR REPLACE VIEW `Workflow_4_CRM` (
  `week_ref`
 ,`recorded_on`
 ,`milestone`
 ,`ccc`
 ,`supporters`
 ,`tickets`
 ,`first_one_created`
 ,`last_one_created`
) AS
  SELECT
    CONCAT(YEAR(`updated`),'-',LPAD(WEEK(`updated`),2,'0')) AS `week`
   ,`updated`
   ,`milestone`
   ,`ccc`
   ,COUNT(DISTINCT `supporter_id`)
   ,SUM(`tickets`)
   ,MIN(`created`)
   ,MAX(`created`)
  FROM `Updates`
  GROUP BY `updated`,`milestone`,`ccc`
  ORDER BY `updated` DESC,`milestone`,`ccc`
;

CREATE OR REPLACE VIEW `Workflow_5_CCR` (
  `week_ref`
 ,`recorded_on`
 ,`milestone`
 ,`ccc`
 ,`supporters`
 ,`tickets`
 ,`first_supporter_created`
 ,`first_supporter_id`
 ,`last_supporter_created`
 ,`last_supporter_id`
) AS
  SELECT
    CONCAT(YEAR(`c`.`changed_date`),'-',LPAD(WEEK(`c`.`changed_date`),2,'0')) AS `week`
   ,`c`.`changed_date`
   ,`c`.`milestone`
   ,`c`.`ccc`
   ,COUNT(DISTINCT `c`.`supporter_id`)
   ,COUNT(`c`.`id`)
   ,MIN(`c`.`created`)
   ,`range`.`first_id`
   ,MAX(`c`.`created`)
   ,`range`.`last_id`
  FROM `Changes` AS `c`
  JOIN (
    SELECT
      `created`
     ,MIN(`id`) AS `first_id`
     ,MAX(`id`) AS `last_id`
    FROM `blotto_supporter`
    GROUP BY `created`
  ) AS `range`
    ON `range`.`created`=`c`.`created`
  GROUP BY `changed_date`,`milestone`,`ccc`
  ORDER BY `changed_date` DESC,`milestone`,`ccc`
;

