
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
    `s`.`original_client_ref`
   ,`s`.`name`
   ,`s`.`email`
   ,`s`.`address_1`
   ,`s`.`address_2`
   ,`s`.`postcode`
   ,SUBSTR(`s`.`created`,1,7) as `month`
  FROM (
    SELECT
      `original_client_ref`
     ,`ccc`
     ,CONCAT(`name_first`,' ',`name_last`) AS `name`
     ,`email`
     ,`address_1`
     ,`address_2`
     ,`postcode`
     ,`created`
    FROM `Supporters`
    GROUP BY `original_client_ref`
  ) AS `s`
  LEFT JOIN (
    SELECT
      `client_ref`
    FROM `Cancellations`
    GROUP BY `client_ref`
  ) AS `c`
  ON `c`.`client_ref`=`s`.`original_client_ref`
  WHERE (`s`.`ccc`='PBB' OR `s`.`ccc`='CDNT' OR `s`.`ccc`='STRP')
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

CREATE OR REPLACE VIEW `MoniesMonthly` (
  `accrue_date`
 ,`mc_date`
 ,`opening_supporters`
 ,`revenue_gross`
 ,`less_external`
 ,`plus_claims`
 ,`less_paid_out`
 ,`revenue_nett`
 ,`less_rbe_fees`
 ,`less_anl_post`
 ,`less_anl_email`
 ,`less_anl_sms`
 ,`less_email`
 ,`less_admin`
 ,`less_tickets`
 ,`less_insure`
 ,`expenses_nett`
 ,`profit_loss`
 ,`profit_loss_cumulative`
 ,`closing_supporters`
 ,`me_date`
) AS
  SELECT
    DATE_SUB(
      DATE_ADD(
        CAST(CONCAT(SUBSTR(`accrue_date`,1,7),'-01') AS date)
       ,INTERVAL 1 MONTH
      )
     ,INTERVAL 1 DAY
    ) AS `ad`
   ,CAST(CONCAT(SUBSTR(`accrue_date`,1,7),'-01') AS date)
   ,CAST(GROUP_CONCAT(`opening_supporters` ORDER BY `accrue_date` ASC LIMIT 1) AS decimal(8,2))
   ,SUM(`revenue_gross`)
   ,SUM(`less_external`)
   ,SUM(`plus_claims`)
   ,SUM(`less_paid_out`)
   ,SUM(`revenue_nett`)
   ,SUM(`less_rbe_fees`)
   ,SUM(`less_anl_post`)
   ,SUM(`less_anl_email`)
   ,SUM(`less_anl_sms`)
   ,SUM(`less_email`)
   ,SUM(`less_admin`)
   ,SUM(`less_tickets`)
   ,SUM(`less_insure`)
   ,SUM(`expenses_nett`)
   ,SUM(`profit_loss`)
   ,CAST(GROUP_CONCAT(`profit_loss_cumulative` ORDER BY `accrue_date` DESC LIMIT 1) AS decimal(8,2))
   ,CAST(GROUP_CONCAT(`closing_supporters` ORDER BY `accrue_date` DESC LIMIT 1) AS decimal(8,2))
   ,DATE_SUB(
      DATE_ADD(
        CAST(CONCAT(SUBSTR(`accrue_date`,1,7),'-01') AS date)
       ,INTERVAL 1 MONTH
      )
     ,INTERVAL 1 DAY
    )
  FROM `Monies`
  GROUP BY `ad`
  ORDER BY `ad`
;

CREATE OR REPLACE VIEW `MoniesWeekly` (
  `accrue_date`
 ,`wc_date`
 ,`opening_supporters`
 ,`revenue_gross`
 ,`less_external`
 ,`plus_claims`
 ,`less_paid_out`
 ,`revenue_nett`
 ,`less_rbe_fees`
 ,`less_anl_post`
 ,`less_anl_email`
 ,`less_anl_sms`
 ,`less_email`
 ,`less_admin`
 ,`less_tickets`
 ,`less_insure`
 ,`expenses_nett`
 ,`profit_loss`
 ,`profit_loss_cumulative`
 ,`closing_supporters`
 ,`we_date`
) AS
  SELECT
    `we_date`
   ,`wc_date`
   ,CAST(GROUP_CONCAT(`opening_supporters` ORDER BY `accrue_date` ASC LIMIT 1) AS decimal(8,2))
   ,SUM(`revenue_gross`)
   ,SUM(`less_external`)
   ,SUM(`plus_claims`)
   ,SUM(`less_paid_out`)
   ,SUM(`revenue_nett`)
   ,SUM(`less_rbe_fees`)
   ,SUM(`less_anl_post`)
   ,SUM(`less_anl_email`)
   ,SUM(`less_anl_sms`)
   ,SUM(`less_email`)
   ,SUM(`less_admin`)
   ,SUM(`less_tickets`)
   ,SUM(`less_insure`)
   ,SUM(`expenses_nett`)
   ,SUM(`profit_loss`)
   ,CAST(GROUP_CONCAT(`profit_loss_cumulative` ORDER BY `accrue_date` DESC LIMIT 1) AS decimal(8,2))
   ,CAST(GROUP_CONCAT(`closing_supporters` ORDER BY `accrue_date` DESC LIMIT 1) AS decimal(8,2))
   ,`we_date`
  FROM `Monies`
  GROUP BY `wc_date`
  ORDER BY `wc_date`
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
   ,COUNT(DISTINCT `supporter_id`)
   ,COUNT(`current_ticket_number`)
   ,MIN(`created`)
   ,MAX(`created`)
  FROM `Supporters`
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
    CONCAT(YEAR(`supporter_first_payment`),'-',LPAD(WEEK(`supporter_first_payment`),2,'0')) AS `week`
   ,`supporter_first_payment`
   ,'first_collection'
   ,`ccc`
   ,COUNT(DISTINCT `supporter_id`)
   ,COUNT(`current_ticket_number`)
   ,MIN(`created`)
   ,MAX(`created`)
  FROM `Supporters`
  WHERE `supporter_first_payment`!='0000-00-00'
  GROUP BY `supporter_first_payment`,`ccc`
  ORDER BY `supporter_first_payment` DESC,`ccc`
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

