
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
     ,MAX(`DateDue`) AS `date_due_last`
    FROM `blotto_build_collection`
    GROUP BY `ClientRef`
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
    AND `c`.`DateDue`>DATE_SUB(`csum`.`date_due_last`,INTERVAL 3 MONTH)
  GROUP BY `ClientRef`
  HAVING `missed`>0
  ORDER BY `missed` DESC,`paid` DESC,`ClientRef`
;

