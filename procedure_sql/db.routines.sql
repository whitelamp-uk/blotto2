-- vague attempt to get cancellation stats by age, canvasser
-- select canvas_agent_ref, count(supporter_id), count(distinct(supporter_id))
-- ,SUM(CASE WHEN cancelled != '' THEN 1 END)
-- ,FLOOR(TIMESTAMPDIFF(YEAR, dob,signed)/5) * 5 as age
-- from Supporters
-- where ccc='BB'
-- group by canvas_agent_ref, age
-- order by canvas_agent_ref, age


USE `{{BLOTTO_TICKET_DB}}`
;


DELIMITER $$
DROP PROCEDURE IF EXISTS `ticketDistribution`$$
CREATE PROCEDURE `ticketDistribution` (
  IN      organisationId INT(11) unsigned
)
BEGIN
  DROP TABLE IF EXISTS `blotto_distribution`
  ;
  CREATE TABLE `blotto_distribution` AS
    SELECT
      SUBSTR(`number`,1,3) as `starts_with`
     ,COUNT(`number`) as `quantity`
    FROM `blotto_ticket`
    WHERE `org_id` IS NOT NULL
      AND (
           `org_id`=organisationId
        OR organisationId IS NULL
      )
    GROUP BY SUBSTR(`number`,1,3)
    ORDER BY `quantity` DESC, `starts_with`
  ;
END$$


-- Moved to organisation database
DELIMITER $$
DROP PROCEDURE IF EXISTS `ticketWins`$$
DROP PROCEDURE IF EXISTS `ticketWinsAfterIssueDate`$$


DELIMITER ;




USE `{{BLOTTO_MAKE_DB}}`
;


DELIMITER $$
DROP PROCEDURE IF EXISTS `activityAll`$$
CREATE PROCEDURE `activityAll` (
)
BEGIN
  SELECT 'Bank';
  CALL activityBank();
  SELECT 'Email';
  CALL activityEmail();
  SELECT 'House';
  CALL activityHouse();
  SELECT 'Mobile';
  CALL activityMobile();
END$$


DELIMITER $$
DROP PROCEDURE IF EXISTS `activityEmail`$$
CREATE PROCEDURE `activityEmail` (
)
BEGIN
    SELECT
      SUBSTR(`created`,1,7) AS `month`
     ,`email` AS `user`
     ,COUNT(DISTINCT `original_client_ref`) AS `signups_in_month`
     ,MIN(`supporter_first_mandate`) AS `first_mandated`
     ,GROUP_CONCAT(DISTINCT CONCAT(`name_first`,' ',`name_last`)) AS `names`
     ,GROUP_CONCAT(DISTINCT `original_client_ref` ORDER BY `original_client_ref`) AS `client_refs`
    FROM `Supporters`
    WHERE `email`!=''
    GROUP BY `month`,`user`
    HAVING `signups_in_month`>1
    ORDER BY `month` DESC,`signups_in_month` DESC,`user`
  ;
END$$


DELIMITER $$
DROP PROCEDURE IF EXISTS `activityBank`$$
CREATE PROCEDURE `activityBank` (
)
BEGIN
    SELECT
      SUBSTR(`s`.`created`,1,7) AS `month`
     ,`m`.`Name`
     ,`m`.`Sortcode`
     ,`m`.`Account`
     ,COUNT(`c`.`RefNo`) AS `collections`
     ,SUM(`c`.`PaidAmount`) AS `collected`
     ,COUNT(DISTINCT `s`.`original_client_ref`) AS `signups_in_month`
     ,MIN(`s`.`supporter_first_mandate`) AS `first_mandated`
     ,GROUP_CONCAT(DISTINCT CONCAT(`s`.`name_first`,' ',`s`.`name_last`)) AS `names`
     ,GROUP_CONCAT(DISTINCT `s`.`original_client_ref` ORDER BY `s`.`original_client_ref`) AS `client_refs`
    FROM `Supporters` AS `s`
    JOIN `blotto_player` AS `p`
      ON `p`.`supporter_id`=`s`.`supporter_id`
    JOIN `blotto_build_mandate` AS `m`
      ON `m`.`ClientRef`=`p`.`client_ref`
    JOIN `blotto_build_collection` AS `c`
      ON `c`.`RefNo`=`m`.`RefNo`
    WHERE `m`.`Account`!=''
    GROUP BY `month`,`Sortcode`,`Account`
    HAVING `signups_in_month`>1
    ORDER BY `month` DESC,`signups_in_month` DESC,`Sortcode`,`Account`
  ;
END$$


DELIMITER $$
DROP PROCEDURE IF EXISTS `activityMobile`$$
CREATE PROCEDURE `activityMobile` (
)
BEGIN
    SELECT
      SUBSTR(`created`,1,7) AS `month`
     ,1*REPLACE(`mobile`,'+','') AS `user`
     ,COUNT(DISTINCT `original_client_ref`) AS `signups_in_month`
     ,MIN(`supporter_first_mandate`) AS `first_mandated`
     ,GROUP_CONCAT(DISTINCT CONCAT(`name_first`,' ',`name_last`)) AS `names`
     ,GROUP_CONCAT(DISTINCT `original_client_ref` ORDER BY `original_client_ref`) AS `client_refs`
    FROM `Supporters`
    WHERE `mobile`!=''
    GROUP BY `month`,`user`
    HAVING `signups_in_month`>1
    ORDER BY `month` DESC,`signups_in_month` DESC,`user`
  ;
END$$


DELIMITER $$
DROP PROCEDURE IF EXISTS `activityHouse`$$
CREATE PROCEDURE `activityHouse` (
)
BEGIN
    SELECT
      SUBSTR(`created`,1,7) AS `month`
     ,CONCAT(`postcode`,' ',`address_1`) AS `user`
     ,COUNT(DISTINCT `original_client_ref`) AS `signups_in_month`
     ,MIN(`supporter_first_mandate`) AS `first_mandated`
     ,GROUP_CONCAT(DISTINCT CONCAT(`name_first`,' ',`name_last`)) AS `names`
     ,GROUP_CONCAT(DISTINCT `original_client_ref` ORDER BY `original_client_ref`) AS `client_refs`
    FROM `Supporters`
    WHERE `postcode`!=''
    GROUP BY `month`,`user`
    HAVING `signups_in_month`>1
    ORDER BY `month` DESC,`signups_in_month` DESC,`user`
  ;
END$$


DELIMITER $$
DROP PROCEDURE IF EXISTS `anls`$$
CREATE PROCEDURE `anls` (
)
BEGIN
  DROP TABLE IF EXISTS `ANLs`
  ;
  CREATE TABLE `ANLs` AS
    SELECT
      MAX(`tk`.`issue_date`) AS `tickets_issued`
     ,`p`.`canvas_code` AS `ccc`
     ,`m`.`ClientRef`
     ,DATE_FORMAT(drawOnOrAfter(`p`.`projected_first_draw_close`),'%a %D %b %Y') AS `projected_first_play`
     ,GROUP_CONCAT(`tk`.`number` SEPARATOR ', ') AS `ticket_numbers`
     ,'' AS `blank`
     ,`p`.`title`
     ,`p`.`name_first`
     ,`p`.`name_last`
     ,`p`.`email`
     ,`p`.`mobile`
     ,`p`.`telephone`
     ,`p`.`address_1`
     ,`p`.`address_2`
     ,`p`.`address_3`
     ,`p`.`town`
     ,`p`.`county`
     ,`p`.`postcode`
     ,`m`.`Provider` AS `Mandate_Provider`
     ,`m`.`RefOrig` AS `Mandate_Ref`
     ,`m`.`Name` AS `Account_Name`
     ,CONCAT('*',SUBSTR(`m`.`Sortcode`,-3)) AS `Account_Sortcode`
     ,CONCAT('*',SUBSTR(`m`.`Account`,-3)) AS `Account_Number`
     ,`m`.`Freq`
     ,`m`.`Amount`
     ,DATE_FORMAT(dateSilly2Sensible(`m`.`Created`),'%d/%m/%Y') AS `Created`
     ,DATE_FORMAT(dateSilly2Sensible(`m`.`StartDate`),'%d/%m/%Y') AS `StartDate`
     ,DATE_FORMAT(`m`.`StartDate`,'%a %D %b %Y') AS `projected_first_collection`
     ,`p`.`letter_batch_ref`
     ,`p`.`letter_status`
    FROM `blotto_build_mandate` AS `m`
    JOIN (
      SELECT
        `is`.`canvas_code`
       ,`is`.`projected_first_draw_close`
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
       ,`ip`.`client_ref`
       ,IFNULL(`ip`.`letter_batch_ref`,'') AS `letter_batch_ref`
       ,IFNULL(`ip`.`letter_status`,'') AS `letter_status`
      FROM `blotto_player` AS `ip`
      JOIN `blotto_supporter` AS `is`
        ON `is`.`id`=`ip`.`supporter_id`
       AND `is`.`mandate_blocked`=0
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
    )      AS `p`
           ON `p`.`client_ref`=`m`.`ClientRef`
    JOIN `{{BLOTTO_TICKET_DB}}`.`blotto_ticket` AS `tk`
      ON `tk`.`client_ref`=`m`.`ClientRef`
      -- this join does not use mandate table so m.Provider=EXT tickets will get included
     AND `tk`.`mandate_provider`=`m`.`Provider`
     AND `tk`.`org_id`={{BLOTTO_ORG_ID}}
    -- One-off payments do not need an ANL
-- TODO: Actually this is more about online payment that one-off payment - or is it?
-- The online/DD condition problem exists in other to-do comments
    WHERE `m`.`Freq`!='Single'
    GROUP BY `m`.`Provider`,`m`.`RefNo`
    ORDER BY `tickets_issued`,`ccc`,`ClientRef`
  ;
  ALTER TABLE `ANLs`
  ADD PRIMARY KEY (`tickets_issued`,`ccc`,`ClientRef`)
  ;
  ALTER TABLE `ANLs`
  ADD KEY `tickets_issued` (`tickets_issued`)
  ;
  ALTER TABLE `ANLs`
  ADD UNIQUE KEY `ClientRef` (`ClientRef`)
  ;
  ALTER TABLE `ANLs`
  ADD UNIQUE KEY `Mandate_Provider_Ref` (`Mandate_Provider`,`Mandate_Ref`)
  ;
  ALTER TABLE `ANLs`
  ADD KEY `ccc` (`ccc`)
  ;
END$$


DELIMITER $$
DROP PROCEDURE IF EXISTS `calculate`$$
CREATE PROCEDURE `calculate` (
  IN    `starts` date
 ,IN    `ends` date
)
BEGIN
  DROP TABLE IF EXISTS `blotto_calculation`;
  CREATE TABLE `blotto_calculation` (
    `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
    `item` varchar(255) NOT NULL,
    `units` char(3) NOT NULL,
    `amount`char(16) NOT NULL,
    `notes` varchar(64) NOT NULL,
    PRIMARY KEY (`id`)
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8
  ;
  SELECT
    IFNULL(MIN(`draw_closed`),'')
  INTO @first
  FROM `blotto_entry`
  WHERE `draw_closed`>=starts
    AND `draw_closed`<=ends
  ;
  SELECT
    IFNULL(MAX(`draw_closed`),'')
  INTO @last
  FROM `blotto_entry`
  WHERE `draw_closed`>=starts
    AND `draw_closed`<=ends
  ;
  SELECT
    IFNULL(COUNT(DISTINCT `draw_closed`),0)
  INTO @weeks
  FROM `blotto_entry`
  WHERE `draw_closed`>=starts
    AND `draw_closed`<=ends
  ;
  SELECT
    IFNULL(SUM(`PaidAmount`),0)
  INTO @collections
  FROM `blotto_build_collection`
  -- await BACS jitter
  WHERE `DateDue`<DATE_SUB(CURDATE(),INTERVAL 7 DAY)
    AND `DateDue`>=starts
    AND `DateDue`<=ends
  ;
  SELECT
    IFNULL(SUM(`p`.`opening_balance`),0)
  INTO @starting
  FROM `blotto_player` AS `p`
  WHERE DATE(`p`.`created`)<=ends
  ;
  SELECT
    IFNULL(SUM(`PaidAmount`),0)
  INTO @collectedAll
  FROM `blotto_build_collection`
  -- await BACS jitter
  WHERE `DateDue`<DATE_SUB(CURDATE(),INTERVAL 7 DAY)
    AND `DateDue`<=ends
  ;
  SELECT
    IFNULL(SUM(`amount`),0)
    INTO @claims
  FROM `{{BLOTTO_CONFIG_DB}}`.`blotto_claim`
  WHERE `org_code`='{{BLOTTO_ORG_USER}}'
    AND `payment_received`>=starts
    AND `payment_received`<=ends
  ;
  SELECT
    IFNULL(COUNT(*),0)
  INTO @playsPeriod
  FROM `blotto_entry`
  WHERE `draw_closed`>=starts
    AND `draw_closed`<=ends
  ;
  -- as 'ends' is usually recent it is quite a bit faster to subtract 'new' from all
  SELECT
    IFNULL(COUNT(*),0)
  INTO @playsAllTime
  FROM `blotto_entry`
  WHERE `draw_closed` IS NOT NULL
  ;
  SELECT
    IFNULL(COUNT(*),0)
  INTO @playsNew
  FROM `blotto_entry`
  WHERE `draw_closed`>ends
  ;
  SELECT
    IFNULL(COUNT(*),0)
  INTO @playsPeriodExternal
  FROM `blotto_external`
  WHERE `draw_closed`>=starts
    AND `draw_closed`<=ends
  ;
  SELECT
    IFNULL(COUNT(*),0)
  INTO @playsAllTimeExternal
  FROM `blotto_external`
  WHERE `draw_closed` IS NOT NULL
  ;
  SELECT
    IFNULL(COUNT(*),0)
  INTO @playsNewExternal
  FROM `blotto_external`
  WHERE `draw_closed`>ends
  ;
  SET @playsEnd             = @playsAllTime - @playsNew
  ;
  SET @playsEndExternal     = @playsAllTimeExternal - @playsNewExternal
  ;
  SET @perplay              = {{BLOTTO_TICKET_PRICE}}
  ;
  SET @playedEndFunded      = @perplay/100 * ( @playsEnd - @playsEndExternal )
  ;
  SET @playedPeriodFunded   = @perplay/100 * ( @playsPeriod - @playsPeriodExternal )
  ;
  SET @balOpen              = ( @starting + @collectedAll - @collections) - ( @playedEndFunded - @playedPeriodFunded )
  ;
  SET @balClose             = @starting + @collectedAll - @playedEndFunded
  ;
  SELECT
    IFNULL(SUM(`w`.`amount`),0)
    INTO @payout
  FROM `blotto_winner` AS `w`
  JOIN `blotto_entry` AS `e`
    ON `e`.`id`=`w`.`entry_id`
  WHERE `e`.`draw_closed`>=starts
    AND `e`.`draw_closed`<=ends
  ;
  SET @nett         = (@playedPeriodFunded - @payout) + @claims
  ;
  SET @reconcile    = ( @balOpen + @collections ) - ( @playedPeriodFunded + @balClose )
  ;
  INSERT INTO `blotto_calculation` ( `item`, `units`, `amount`, `notes` )  VALUES
    ( 'head_summary',       '',     '',                                     'Summary'                           ),
    ( 'amount_per_play',    'GBP',  dp(@perplay/100,2),                     'charge per play'                   ),
    ( 'draw_first',         '',     DATE_FORMAT(@first,'%Y %b %d'),         'first draw'                        ),
    ( 'draw_last',          '',     DATE_FORMAT(@last,'%Y %b %d'),          'last draw'                         ),
    ( 'draws',              '',     dp(@weeks,0),                           'draws completed'                   ),
    ( 'plays_funded',       '',     @playsPeriod - @playsPeriodExternal,    'purchased plays'                   ),
    ( 'plays_external',     '',     @playsPeriodExternal,                   'external plays to reconcile'       ),
    ( 'head_balance',       '',     '',                                     'Balance'                           ),
    ( 'balances_opening',   'GBP',  dp(@balOpen,2),                         '+ player opening balances'         ),
    ( 'payments_opening',   'GBP',  '0.00',                                 '+ opening queued card payments'    ),
    ( 'collected',          'GBP',  dp(@collections,2),                     '+ collected this period'           ),
    ( 'play_value',         'GBP',  dp(0-@playedPeriodFunded,2),            '− plays purchased'                 ),
    ( 'balances_closing',   'GBP',  dp(0-@balClose,2),                      '− player closing balances'         ),
    ( 'payments_closing',   'GBP',  '0.00',                                 '− closing queued card payments'    ),
    ( 'reconciliation',     'GBP',  dp(@reconcile,2),                       '≡ GBP to reconcile'                ),
    ( 'head_return',        '',     '',                                     'Revenue'                           ),
    ( 'revenue',            'GBP',  dp(@playedPeriodFunded,2),              '+ revenue from plays'              ),
    ( 'winnings',           'GBP',  dp(0-@payout,2),                        '− winnings paid out'               ),
    ( 'claims',             'GBP',  dp(@claims,2),                          '+ insurance claimed'               ),
    ( 'nett',               'GBP',  dp(@nett,2),                            '≡ nett return before fees'         )
  ;
  SELECT
    *
  FROM `blotto_calculation`
  ;
  DROP TABLE `blotto_calculation`
  ;
END$$

DELIMITER $$
DROP PROCEDURE IF EXISTS `cancellationsByAge`$$
CREATE PROCEDURE `cancellationsByAge` (
)
BEGIN
  SELECT YEAR(`s`.`signed`) - YEAR(`s`.`dob`) - (DATE_FORMAT(`s`.`signed`, '%m%d') < DATE_FORMAT(`s`.`dob`, '%m%d')) AS `age`
  ,SUM(`s`.`tickets`) AS `tickets`
  ,SUM(IF(`s`.`cancelled`!='',`tickets`,0)) AS `tickets_cancelled`
  ,COUNT(`s`.`current_client_ref`) AS `supporters`
  ,COUNT(`c`.`client_ref`) AS `supporters_cancelled`
  ,ROUND((COUNT(`c`.`client_ref`)/COUNT(`s`.`current_client_ref`)) * 100,0) AS `percent`
  ,SUM(IF(`c`.`payments_collected`<10,1,0)) AS `ccr`
  ,SUM(IF(`c`.`payments_collected`=0,1,0)) AS `ccr0`
  ,SUM(IF(`c`.`payments_collected`=1,1,0)) AS `ccr1`
  ,SUM(IF(`c`.`payments_collected`=2,1,0)) AS `ccr2`
  ,SUM(IF(`c`.`payments_collected`=3,1,0)) AS `ccr3`
  ,SUM(IF(`c`.`payments_collected`=4,1,0)) AS `ccr4`
  ,SUM(IF(`c`.`payments_collected`=5,1,0)) AS `ccr5`
  ,SUM(IF(`c`.`payments_collected`=6,1,0)) AS `ccr6`
  ,SUM(IF(`c`.`payments_collected`=7,1,0)) AS `ccr7`
  ,SUM(IF(`c`.`payments_collected`=8,1,0)) AS `ccr8`
  ,SUM(IF(`c`.`payments_collected`=9,1,0)) AS `ccr9`
  FROM (
    SELECT `current_client_ref`, count(`current_client_ref`) AS `tickets`, `dob`, `signed`, `cancelled`
      FROM `Supporters`
    GROUP BY `current_client_ref`
  )  AS `s`
  LEFT JOIN  (
    SELECT `client_ref`, MAX(`payments_collected`) AS `payments_collected`
      FROM `Cancellations`
    GROUP BY `client_ref`
  ) AS `c`
  ON c.client_ref = `s`.`current_client_ref`
  GROUP BY `age`
  ORDER BY `age`
  ;
END$$


DELIMITER $$
DROP PROCEDURE IF EXISTS `cancellationsByRule`$$
CREATE PROCEDURE `cancellationsByRule` (
)
BEGIN
  DROP TABLE IF EXISTS `Cancellations`
  ;
  CREATE TABLE `Cancellations` AS
    SELECT
      -- Deterministic cancelled date
      -- If no mandate, use supporter created
      -- Cancellation is based on late collection and not on mandate status
      -- (not all APIs support mandate status)
      IF(
        `m`.`Refno` IS NULL
        -- NB cancelDate() allows for BACS jitter
       ,cancelDate(`s`.`created`,'')
       ,IF(
          `c`.`Payments_Collected` IS NULL
          -- If no collections, use mandate start date
         ,cancelDate(`m`.`StartDate`,`m`.`Freq`)
         ,cancelDate(`c`.`Last_Payment`,`m`.`Freq`)
        )
      ) AS `cancelled_date`

      -- TODO this bit is no longer needed but impact of its removal needs assessing
     ,IF(
        `m`.`Refno` IS NULL
       ,cancelDate(`s`.`created`,'')
       ,IF(
          `c`.`Payments_Collected` IS NULL
          -- if no collections, use mandate start date
         ,cancelDate(`m`.`StartDate`,`m`.`Freq`)
         ,cancelDate(`c`.`Last_Payment`,`m`.`Freq`)
        )
      ) AS `cancelled_date_legacy`

     ,`s`.`canvas_code` AS `ccc`
     ,`ip`.`client_ref`
     ,IFNULL(`t`.`number`,'') AS `ticket_number`
     ,`s`.`created` AS `supporter_created`
     ,CONCAT_WS(' ',`ic`.`title`,`ic`.`name_first`,`ic`.`name_last`) AS `supporter_name`
     ,IFNULL(`c`.`Payments_Collected`,0) AS `payments_collected`
     ,IFNULL(`c`.`Amount_Collected`,0.00) AS `amount_collected_(all_tickets)`
     ,IFNULL(`c`.`First_Payment`,'') AS `payment_first`
     ,IFNULL(`c`.`Last_Payment`,'') AS `payment_last`
     ,IFNULL(`m`.`Provider`,'') AS `mandate_provider`
     ,IFNULL(`m`.`RefNo`,'') AS `mandate_reference`
     ,IFNULL(`m`.`RefOrig`,'') AS `mandate_reference_provider`
     ,IFNULL(`m`.`Created`,'') AS `mandate_created`
     ,IFNULL(`m`.`StartDate`,'') AS `mandate_startdate`
     ,IFNULL(`m`.`Freq`,'') AS `mandate_frequency`
     ,IFNULL(`m`.`Amount`,0) AS `mandate_amount`
     ,IFNULL(`m`.`Status`,'MISSING') AS `mandate_status`
     ,IFNULL(`m`.`FailReason`,'') AS `mandate_fail_reason`
    FROM `blotto_supporter` AS `s`
    JOIN (
      SELECT
        `supporter_id`
       ,MAX(`started`) AS `latest`
      FROM `blotto_player`
      GROUP BY `supporter_id`
    ) AS `ipl`
      ON `ipl`.`supporter_id`=`s`.`id`
    JOIN `blotto_player` AS `ip`
      ON `ip`.`supporter_id`=`s`.`id`
     AND `ip`.`started`=`ipl`.`latest`
    JOIN (
      SELECT
        `supporter_id`
        ,MAX(`created`) AS `latest`
      FROM `blotto_contact`
      GROUP BY `supporter_id`
    ) AS `icl`
      ON `icl`.`supporter_id`=`s`.`id`
    JOIN `blotto_contact` AS `ic`
      ON `ic`.`supporter_id`=`s`.`id`
     AND `ic`.`created`=`icl`.`latest`
    LEFT JOIN `blotto_build_mandate` AS `m`
           ON `m`.`ClientRef`=`ip`.`client_ref`
    LEFT JOIN (
      SELECT
        `Provider`
       ,`RefNo`
       ,`ClientRef`
       ,MIN(`DateDue`) AS `First_Payment`
       ,MAX(`DateDue`) AS `Last_Payment`
       ,COUNT(`DateDue`) AS `Payments_Collected`
       ,SUM(`PaidAmount`) AS `Amount_Collected`
      FROM `blotto_build_collection`

      -- TODO probably this restriction does nothing because
      -- cancelDate() AS cancelled_date above now allows for BACS jitter
      -- so MP thinks the (needed) HAVING clause below is all we need

      -- do not be too keen to report collections (BACS jitter)
      WHERE `DateDue`<DATE_SUB(CURDATE(),INTERVAL 7 DAY)

      GROUP BY `Provider`,`RefNo`
    )      AS `c`
           ON `c`.`Provider`=`m`.`Provider`
          AND `c`.`RefNo`=`m`.`RefNo`
    LEFT JOIN `{{BLOTTO_TICKET_DB}}`.`blotto_ticket` AS `t`
           ON `t`.`mandate_provider`=`m`.`Provider`
          AND `t`.`client_ref`=`m`.`ClientRef`
          AND `t`.`org_id`={{BLOTTO_ORG_ID}}
    -- One-off payments are not applicable
    WHERE `m`.`Freq`!='Single' OR `m`.`Freq` IS NULL
      AND `t`.`mandate_provider`!='EXT' -- ignore external tickets (which are notionally single)
-- TODO
--    -- mandates may be missing or periodic
--    WHERE ( `m`.`Freq` IS NULL OR `m`.`Freq`!='Single' )
--    -- supporters must be internal
--      AND `s`.`ccc`!='EX'
    GROUP BY `client_ref`,`ticket_number`
    -- cancelled_date is in the past
    HAVING `cancelled_date`<CURDATE()
    ORDER BY `cancelled_date`,`ccc`,`client_ref`,`supporter_created`,`ticket_number`
  ;
  ALTER TABLE `Cancellations`
  ADD PRIMARY KEY (`cancelled_date`,`ccc`,`client_ref`,`ticket_number`)
  ;
  ALTER TABLE `Cancellations`
  ADD UNIQUE KEY `client_ref_ticket_number` (`client_ref`,`ticket_number`)
  ;
  ALTER TABLE `Cancellations`
  ADD KEY `cancelled_date` (`cancelled_date`)
  ;
  ALTER TABLE `Cancellations`
  ADD KEY `ccc` (`ccc`)
  ;
  ALTER TABLE `Cancellations`
  ADD KEY `client_ref` (`client_ref`)
  ;
  ALTER TABLE `Cancellations`
  ADD KEY `ticket_number` (`ticket_number`)
  ;
  ALTER TABLE `Cancellations`
  ADD KEY `mandate_provider_reference` (`mandate_provider`,`mandate_reference`)
  ;
END$$


DELIMITER $$
DROP PROCEDURE IF EXISTS `changes`$$
CREATE PROCEDURE `changes` (
)
BEGIN
  DROP TABLE IF EXISTS `Changes`
  ;
  CREATE TABLE `Changes` AS
    SELECT
      *
    FROM `blotto_change` AS `ch`
    ORDER BY `ch`.`changed_date`,`ch`.`ccc`,`ch`.`canvas_ref`,`ch`.`chance_number`
  ;
  ALTER TABLE `Changes`
  ADD PRIMARY KEY (`id`)
  ;
  ALTER TABLE `Changes`
  ADD KEY `changed_date` (`changed_date`)
  ;
  ALTER TABLE `Changes`
  ADD KEY `ccc` (`ccc`)
  ;
  ALTER TABLE `Changes`
  ADD KEY `canvas_ref` (`canvas_ref`)
  ;
  ALTER TABLE `Changes`
  ADD KEY `agent_ref` (`canvas_agent_ref`)
  ;
  ALTER TABLE `Changes`
  ADD KEY `client_ref` (`client_ref`)
  ;
  ALTER TABLE `Changes`
  ADD KEY `milestone` (`milestone`)
  ;
  ALTER TABLE `Changes`
  ADD KEY `milestone_date` (`milestone_date`)
  ;
  ALTER TABLE `Changes`
  DROP COLUMN `collected_last`
  ;
  ALTER TABLE `Changes`
  DROP COLUMN `collected_amount`
  ;
END$$


DELIMITER $$
DROP PROCEDURE IF EXISTS `changesSummary`$$
CREATE PROCEDURE `changesSummary` (
  IN      `canvassingCompanyCode` varchar(8) CHARACTER SET 'ascii'
)
BEGIN
  DROP TABLE IF EXISTS `blotto_changes_summary_tmp`
  ;
  CREATE TABLE `blotto_changes_summary_tmp` AS
    SELECT
      `changed_date`
     ,`milestone`
  -- Eventually we will modify Changes to store nr of collections at the milestone rather than now
  -- Until then:
     ,IF(
        `milestone`='created'
       ,0
  -- Eventually we will modify blotto_update and blotto_change to store nr of collections *before* reinstatements
  -- Until then:
       ,IF(
          `milestone`='reinstatement'
         ,IF(`collected_times`=0,`collected_times`,`collected_times`-1)
         ,`collected_times`
        )
      ) AS `payments`
     ,1*(`milestone`='created') AS `import`
     ,1*(`milestone`='cancellation') AS `cancel`
     ,1*(`milestone`='reinstatement') AS `reinstate`
      FROM `Changes`
      WHERE `ccc`=canvassingCompanyCode
        AND `milestone` IN ('created','cancellation','reinstatement')
  ;
  SELECT
    DATE_ADD(`changed_date`,INTERVAL(0-WEEKDAY(`changed_date`)) DAY) AS `wc`
   ,`milestone`
   ,`payments`
   ,SUM(`import`) AS `imports`
   ,SUM(`cancel`) AS `cancellations`
   ,SUM(`reinstate`) AS `reinstatements`
    FROM `blotto_changes_summary_tmp`
    GROUP BY `wc`,`milestone`,`payments`
    ORDER BY `wc`,`milestone`='created' DESC,`milestone`,`payments`
  ;
  DROP TABLE `blotto_changes_summary_tmp`
  ;
END$$


DELIMITER $$
DROP PROCEDURE IF EXISTS `draws`$$
CREATE PROCEDURE `draws` (
)
BEGIN
  DROP TABLE IF EXISTS `Draws`
  ;
  CREATE TABLE `Draws` AS
    SELECT
      `e`.`draw_closed`
     ,`e`.`client_ref`
     ,`e`.`ticket_number`
     ,`s`.`title`
     ,`s`.`name_first`
     ,`s`.`name_last`
     ,`s`.`email`
     ,`s`.`mobile`
     ,`s`.`telephone`
     ,`s`.`address_1`
     ,`s`.`address_2`
     ,`s`.`address_3`
     ,`s`.`town`
     ,`s`.`county`
     ,`s`.`postcode`
     ,`m`.`Provider` AS `payment_provider`
     ,`m`.`RefOrig` AS `payment_ref_no`
     ,`m`.`Name` AS `payment_name`
     ,CONCAT('*',SUBSTR(`m`.`Sortcode`,-3)) AS `payment_sortcode`
     ,CONCAT('*',SUBSTR(`m`.`Account`,-3)) AS `payment_account`
     ,`m`.`Freq` AS `payment_frequency`
     ,`m`.`Amount` AS `payment_amount`
     ,dateSilly2Sensible(`m`.`Created`) AS `mandate_created`
     ,dateSilly2Sensible(`m`.`StartDate`) AS `mandate_startdate`
    FROM `blotto_entry` AS `e`
    LEFT JOIN  `blotto_build_mandate` AS `m`
      ON `m`.`ClientRef`=`e`.`client_ref`
    LEFT JOIN (
      SELECT
        `ip`.`client_ref`
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
           ON `s`.`client_ref`=`m`.`ClientRef`
    WHERE `e`.`draw_closed`>'{{BLOTTO_DRAWS_AFTER}}'
      AND `e`.`draw_closed`<CURDATE()
    GROUP BY `e`.`draw_closed`,`e`.`ticket_number`
    ORDER BY `e`.`draw_closed`,`e`.`client_ref`,`e`.`ticket_number`
  ;
  ALTER TABLE `Draws`
  ADD PRIMARY KEY (`draw_closed`,`ticket_number`)
  ;
  ALTER TABLE `Draws`
  ADD KEY `draw_closed` (`draw_closed`)
  ;
  ALTER TABLE `Draws`
  ADD KEY `client_ref` (`client_ref`)
  ;
  ALTER TABLE `Draws`
  ADD KEY `ticket_number` (`ticket_number`)
  ;
END$$


DELIMITER $$
DROP PROCEDURE IF EXISTS `drawsSummarise`$$
CREATE PROCEDURE `drawsSummarise` (
)
BEGIN
  DROP TABLE IF EXISTS `Draws_Summary`
  ;
  CREATE TABLE `Draws_Summary` AS
    SELECT
      `e`.`draw_closed`
     ,`s`.`canvas_code` AS `ccc`
     ,COUNT(DISTINCT(`e`.`client_ref`)) AS `supporters_entered`
     ,COUNT(`e`.`id`) AS `tickets_entered`
    FROM `blotto_entry` AS `e`
    LEFT JOIN `blotto_player` AS `p`
      ON `p`.`client_ref`=`e`.`client_ref`
    LEFT JOIN `blotto_supporter` AS `s`
      ON `s`.`id`=`p`.`supporter_id`
    WHERE `e`.`draw_closed`<CURDATE()
      AND `e`.`draw_closed` IS NOT NULL -- superfluous but for clarity
    GROUP BY `draw_closed`,`ccc`
    ORDER BY `draw_closed`,`ccc`
  ;
  ALTER TABLE `Draws_Summary`
  ADD PRIMARY KEY (`draw_closed`,`ccc`)
  ;
  DROP TABLE IF EXISTS `Draws_Supersummary`
  ;
  CREATE TABLE `Draws_Supersummary` AS
    SELECT
      `draw_closed`
     ,SUM(`supporters_entered`) AS `supporters_entered`
     ,SUM(`tickets_entered`) AS `tickets_entered`
    FROM `Draws_Summary`
    GROUP BY `draw_closed`
    ORDER BY `draw_closed`
  ;
  ALTER TABLE `Draws_Supersummary`
  ADD PRIMARY KEY (`draw_closed`)
  ;
END$$


DELIMITER $$
DROP PROCEDURE IF EXISTS `externals`$$
CREATE PROCEDURE `externals` (
)
BEGIN
  -- Supporters arising from external tickets
  SET @CostPerPlay = {{BLOTTO_TICKET_PRICE}};
  INSERT INTO `tmp_supporterout`
    SELECT
      `s`.`id`
     ,`s`.`created`
     ,`s`.`canvas_code`
     ,`ext`.`client_ref`
     ,`ext`.`client_ref`
     ,`ext`.`draw_closed`
     ,`ext`.`ticket_number`
     ,'' AS `signed`
     ,`s`.`canvas_agent_ref`
     ,`s`.`canvas_ref`
     ,GROUP_CONCAT(`c`.`title` ORDER BY `c`.`id` DESC LIMIT 1) AS `title`
     ,GROUP_CONCAT(`c`.`name_first` ORDER BY `c`.`id` DESC LIMIT 1) AS `name_first`
     ,GROUP_CONCAT(`c`.`name_last` ORDER BY `c`.`id` DESC LIMIT 1) AS `name_last`
     ,IFNULL(GROUP_CONCAT(`c`.`email` ORDER BY `c`.`id` DESC LIMIT 1),'') AS `email`
     ,IFNULL(GROUP_CONCAT(`c`.`mobile` ORDER BY `c`.`id` DESC LIMIT 1),'') AS `mobile`
     ,IFNULL(GROUP_CONCAT(`c`.`telephone` ORDER BY `c`.`id` DESC LIMIT 1),'') AS `telephone`
     ,IFNULL(GROUP_CONCAT(`c`.`address_1` ORDER BY `c`.`id` DESC LIMIT 1),'') AS `address_1`
     ,IFNULL(GROUP_CONCAT(`c`.`address_2` ORDER BY `c`.`id` DESC LIMIT 1),'') AS `address_2`
     ,IFNULL(GROUP_CONCAT(`c`.`address_3` ORDER BY `c`.`id` DESC LIMIT 1),'') AS `address_3`
     ,IFNULL(GROUP_CONCAT(`c`.`town` ORDER BY `c`.`id` DESC LIMIT 1),'') AS `town`
     ,IFNULL(GROUP_CONCAT(`c`.`county` ORDER BY `c`.`id` DESC LIMIT 1),'') AS `county`
     ,IFNULL(GROUP_CONCAT(`c`.`postcode` ORDER BY `c`.`id` DESC LIMIT 1),'') AS `postcode`
     ,IFNULL(GROUP_CONCAT(`c`.`dob` ORDER BY `c`.`id` DESC LIMIT 1),'') AS `dob`
     ,GROUP_CONCAT(`c`.`p0` ORDER BY `c`.`id` DESC LIMIT 1) AS `p0`
     ,GROUP_CONCAT(`c`.`p1` ORDER BY `c`.`id` DESC LIMIT 1) AS `p1`
     ,GROUP_CONCAT(`c`.`p2` ORDER BY `c`.`id` DESC LIMIT 1) AS `p2`
     ,GROUP_CONCAT(`c`.`p3` ORDER BY `c`.`id` DESC LIMIT 1) AS `p3`
     ,GROUP_CONCAT(`c`.`p4` ORDER BY `c`.`id` DESC LIMIT 1) AS `p4`
     ,GROUP_CONCAT(`c`.`p5` ORDER BY `c`.`id` DESC LIMIT 1) AS `p5`
     ,GROUP_CONCAT(`c`.`p6` ORDER BY `c`.`id` DESC LIMIT 1) AS `p6`
     ,GROUP_CONCAT(`c`.`p7` ORDER BY `c`.`id` DESC LIMIT 1) AS `p7`
     ,GROUP_CONCAT(`c`.`p8` ORDER BY `c`.`id` DESC LIMIT 1) AS `p8`
     ,GROUP_CONCAT(`c`.`p9` ORDER BY `c`.`id` DESC LIMIT 1) AS `p9`
     ,'EXT' AS `Provider`
     ,'' AS `RefNo`
     ,'' AS `Name`
     ,'' AS `FirstPayment`
     ,'' AS `LastCreated`
     ,'' AS `LastUpdated`
     ,'' AS `LastPayment`
     ,'Single' AS `Freq`
     ,CAST(`s`.`projected_chances`*@CostPerPlay/100 AS decimal(10,2)) AS `Amount`
     ,1 AS `PaymentsCollected`
     ,CAST(`s`.`projected_chances`*@CostPerPlay/100 AS decimal(10,2)) AS `AmountCollected`
     ,IF(`ext`.`draw_closed`<CURDATE(),1,0) AS `plays`
     ,CAST(@CostPerPlay/100 AS decimal(10,2))
     ,0.00 AS `balance`
     ,'SINGLE' AS `active`
     ,'EXTERNAL' AS `status`
     ,'' AS `fail_reason`
    FROM `blotto_external` AS `ext`
    JOIN `blotto_supporter` AS `s`
      ON `s`.`client_ref`=`ext`.`client_ref`
    JOIN `blotto_contact` AS `c`
      ON `c`.`supporter_id`=`s`.`id`
    GROUP BY `s`.`id`
  ;
  -- Players arising from external tickets
  INSERT INTO `tmp_player`
    SELECT
      `p`.`id`
     ,`p`.`supporter_id`
     ,`p`.`client_ref`
     ,`p`.`chances`
     ,'' AS `FirstPayment`
     ,'' AS `FirstCreated`
     ,1 AS `PaymentsCollected`
     ,CAST(`p`.`chances`*@CostPerPlay/100 AS decimal(10,2)) AS `AmountCollected`
     ,IF(`ext`.`draw_closed`<CURDATE(),1,0) AS `plays`
     ,IF(
        `ext`.`draw_closed`<CURDATE()
       ,0.00
       ,CAST(`p`.`chances`*@CostPerPlay/100 AS decimal(10,2))
      ) AS `balance`
    FROM `blotto_external` AS `ext`
    JOIN `blotto_player` AS `p`
      ON `p`.`client_ref`=`ext`.`client_ref`
  ;
END$$


DELIMITER $$
DROP PROCEDURE IF EXISTS `insure`$$
CREATE PROCEDURE `insure` (
  IN      futureCloseDate date
)
BEGIN
  INSERT IGNORE INTO `blotto_insurance`
  ( `draw_closed`,`ticket_number`,`org_ref`,`client_ref` )
    SELECT futureCloseDate,`tk`.`number`,UPPER('{{BLOTTO_ORG_USER}}') AS `org_ref`,`tk`.`client_ref`
    FROM `blotto_build_mandate` AS `m`
    LEFT JOIN (
      SELECT
        `Provider`
       ,`RefNo`
       ,SUM(`PaidAmount`) AS `AmountCollected`
      FROM `blotto_build_collection`
      WHERE 1
      GROUP BY `Provider`,`RefNo`
    ) AS `c`
      ON `c`.`Provider`=`m`.`Provider`
     AND `c`.`RefNo`=`m`.`RefNo`
    JOIN `blotto_player` as `p`
      ON `p`.`client_ref`=`m`.`ClientRef`
     AND `p`.`first_draw_close` IS NOT NULL
     AND `p`.`first_draw_close`<=futureCloseDate
    JOIN `{{BLOTTO_TICKET_DB}}`.`blotto_ticket` as `tk`
      ON `tk`.`client_ref`=`m`.`ClientRef`
     AND `tk`.`org_id` = {{BLOTTO_ORG_ID}}
    LEFT JOIN (
      SELECT
        `client_ref`
       ,COUNT(DISTINCT `draw_closed`) AS `plays`
      FROM `blotto_entry`
      WHERE `draw_closed` IS NOT NULL -- external tickets will not be in the results anyway (m.ClientRef is null)
       GROUP BY `client_ref`
    ) AS `e`
      ON `e`.`client_ref`=`p`.`client_ref`
    LEFT JOIN `Cancellations` AS `cancelled`
           ON `cancelled`.`client_ref`=`p`.`client_ref`
    WHERE (
          -- The player is deemed to be active
          `cancelled`.`client_ref` IS NULL
          -- The player has enough right now to play again
          -- Payments as of today - ignore BLOTTO_PAY_DELAY to keep it simple
       OR `p`.`opening_balance`
            + IFNULL(`c`.`AmountCollected`,0)
            - (IFNULL(`e`.`plays`,0)+1)*`p`.`chances`*{{BLOTTO_TICKET_PRICE}}/100
          >= 0
    )
    -- Player is ready to go
      AND `p`.`first_draw_close` IS NOT NULL
    -- Player is ready to go right now
      AND `p`.`first_draw_close`<=futureCloseDate

-- If a player is not cancelled then they should be insured
-- This means insurance (when it closes before the draw)
-- places inclusivity above the cost of a few extra tickets
    -- Player has enough balance to play one more time
--      AND
--              `p`.`opening_balance`
--            + IFNULL(`c`.`AmountCollected`,0)
--            - (IFNULL(`e`.`plays`,0)+1)*`p`.`chances`*{{BLOTTO_TICKET_PRICE}}/100
--          >= 0

    ORDER BY `tk`.`number`
  ;
-- TODO add insurance support for external tickets - insert ignore from blotto_external
-- current solution is you cannot have EXT tickets if you have any insured prizes
  -- Generate `Insurance`
  CALL insureOutput()
  ;
END$$


DELIMITER $$
DROP PROCEDURE IF EXISTS `insureOutput`$$
CREATE PROCEDURE `insureOutput` (
)
BEGIN
  DROP TABLE IF EXISTS `Insurance`
  ;
  CREATE TABLE `Insurance` AS
    SELECT
      CONVERT(CONCAT(DATE_FORMAT(`draw_closed`,'%Y%m%d'),`ticket_number`),unsigned integer) AS `entry_urn`
     ,`draw_closed` AS `draw_close_date`
     ,CONCAT(`org_ref`,'-',`client_ref`) AS `player_urn`
     ,CONCAT('T-',`ticket_number`) AS `ticket_number`
    FROM `blotto_insurance`
    ORDER BY `entry_urn`
  ;
  ALTER TABLE `Insurance`
  ADD PRIMARY KEY (`entry_urn`)
  ;
  ALTER TABLE `Insurance`
  ADD KEY `draw_close_date` (`draw_close_date`)
  ;
  ALTER TABLE `Insurance`
  ADD KEY `player_urn` (`player_urn`)
  ;
  ALTER TABLE `Insurance`
  ADD KEY `ticket_number` (`ticket_number`)
  ;
  DROP TABLE IF EXISTS `Insurance_Summary`
  ;
  CREATE TABLE `Insurance_Summary` AS
    SELECT
      `draw_close_date`
     ,COUNT(DISTINCT `player_urn`) AS `players_insured`
     ,COUNT(`entry_urn`) AS `tickets_insured`
    FROM `Insurance`
    GROUP BY `draw_close_date`
    ORDER BY `draw_close_date`
  ;
  ALTER TABLE `Insurance_Summary`
  ADD PRIMARY KEY (`draw_close_date`)
  ;
  -- Refresh export data for next transfer to insurer
  -- DL: 15.feb.2025 we think this is obsolete
--  CREATE TABLE IF NOT EXISTS `{{BLOTTO_TICKET_DB}}`.`Insurance_Export` (
--    `entry_urn` bigint(21) unsigned NOT NULL,
--    `draw_close_date` date DEFAULT NULL,
--    `player_urn` varchar(69) CHARACTER SET ascii DEFAULT NULL,
--    `ticket_number` varchar(18) CHARACTER SET ascii DEFAULT NULL,
--    PRIMARY KEY (`entry_urn`),
--    KEY `draw_close_date` (`draw_close_date`),
--    KEY `player_urn` (`player_urn`),
--    KEY `ticket_number` (`ticket_number`)
--  ) ENGINE=InnoDB DEFAULT CHARSET=utf8
--  ;
--  DELETE FROM `{{BLOTTO_TICKET_DB}}`.`Insurance_Export`
--  WHERE `player_urn` LIKE CONCAT(UPPER('{{BLOTTO_ORG_USER}}'),'-%')
--  ;
--  SET @latest = ( SELECT MAX(`draw_close_date`) FROM `Insurance` )
--  ;
--  INSERT INTO `{{BLOTTO_TICKET_DB}}`.`Insurance_Export`
--    SELECT
--      *
--    FROM `Insurance`
--    WHERE `draw_close_date`=@latest
--  ;
END$$


DELIMITER $$
DROP PROCEDURE IF EXISTS `insureRBE`$$
CREATE PROCEDURE `insureRBE` (
)
BEGIN
  -- Add all unrecorded tickets in blotto_entry for all entries found
  INSERT IGNORE INTO `blotto_insurance`
  ( `draw_closed`,`ticket_number`,`org_ref`,`client_ref` )
    SELECT `draw_closed`,`ticket_number`,UPPER('{{BLOTTO_ORG_USER}}') AS `org_ref`,`client_ref`
    FROM `blotto_entry`
    WHERE `draw_closed`<CURDATE()
    ORDER BY `ticket_number`
  ;
  CALL insureOutput()
  ;
END$$


DELIMITER $$
DROP PROCEDURE IF EXISTS `journeySummary`$$
CREATE PROCEDURE `journeySummary` (
)
BEGIN
  -- As of 2025-03-20 this has not been used (scripts/chart-0005.php uses similar code)
  SELECT  
  SUM(`tickets`) as tickets
  ,SUM(IF (`status`='importing', `tickets`, 0)) as importing
  ,SUM(IF (`status`='collecting', `tickets`, 0)) as collecting
  ,SUM(IF (`status`='entering', `tickets`, 0)) as entering
  ,SUM(IF (`status`='loading', `tickets`, 0)) as loading
  ,SUM(IF (`status`='entered', `tickets`, 0)) as entered
  ,SUM(IF (`status`='entered' AND `dormancy_date` IS NULL, `tickets`, 0)) as current
  FROM `Journeys`
  ;
  SELECT  
  COUNT(`player_id`) as players
  ,SUM(`status`='importing') as importing
  ,SUM(`status`='collecting') as collecting
  ,SUM(`status`='entering') as entering
  ,SUM(`status`='loading') as loading
  ,SUM(`status`='entered') as entered
  ,SUM(`status`='entered' AND `dormancy_date` IS NULL) as current
  FROM `Journeys`
  ;
END$$


-- DL: at some point, a sequence of IF ELSE statements so that it's easier to grasp then nested IFs.
-- MP: I believe IF() in a select statement like this is an actual function and not condition syntax ...
DELIMITER $$
DROP PROCEDURE IF EXISTS `journeys`$$
CREATE PROCEDURE `journeys` (
)
BEGIN
  DROP TABLE IF EXISTS `Journeys`
  ;
  CREATE TABLE `Journeys` AS
  SELECT
    `p`.`id` AS `player_id`
   ,DATE(`p`.`created`) AS `player_created`
   ,weekCommencingDate(DATE(`p`.`created`)) AS `created_wc`
   ,CAST(CONCAT(SUBSTR(`p`.`created`,1,7),'-01') AS date) AS `created_mc`
   ,IF(
     `e`.`client_ref` IS NULL -- not loaded yet
     ,IF(
       `p`.`first_draw_close` IS NULL
       ,IF(
         `u`.`id` IS NULL
          -- no first collection yet
         ,IF(
            `p`.`letter_status` IS NULL
           ,'importing' -- there is a player but nothing much has happened
           ,IF(
              LENGTH(`ul`.`cancelled`)>0
             ,'failed' -- no collection and probably never will be
             ,'collecting' -- mandate/ANL/first collection is underway
            )
          )
         ,'entering' -- has first collection
        )
       ,'loading' -- a draw close date is set
      )
     ,'entered' -- entered at least once
    ) AS `status`
   ,IF(
      `e`.`last_draw_closed`<DATE_SUB(CURDATE(),INTERVAL {{BLOTTO_CANCEL_RULE}})
      ,`e`.`last_draw_closed`
      ,null
    ) AS `dormancy_date`
   ,1 AS `supporters`
   ,IFNULL(`p`.`chances`,`s`.`projected_chances`) AS `tickets`
   ,`ul`.`cancelled`
  FROM `blotto_player` AS `p`
  JOIN `blotto_supporter` AS `s`
    ON `s`.`id`=`p`.`supporter_id`
   AND `s`.`canvas_ref` NOT IN ('CDNT','EX','STRP')
  JOIN `UpdatesLatest` AS `ul`
    ON `ul`.`sort2_supporter_id`=`s`.`id`
  LEFT JOIN `blotto_update` AS `u`
         ON `u`.`player_id`=`p`.`id`
        AND `u`.`milestone`='first_collection'
  LEFT JOIN (
    SELECT
      `client_ref`
     ,MAX(`draw_closed`) AS `last_draw_closed`
    FROM `blotto_entry`
    GROUP BY `client_ref`
  )      AS `e`
         ON `e`.`client_ref`=`p`.`client_ref`
  ;
  ALTER TABLE `Journeys` ADD COLUMN `player_draw_closed` date AFTER `player_created`
  ;
  UPDATE `Journeys` AS `j`
  JOIN `blotto_player` AS `p`
    ON `p`.`id`=`j`.`player_id`
  SET
    `j`.`player_draw_closed` = `p`.`first_draw_close`
  ;
  ALTER TABLE `Journeys` ADD COLUMN `player_day` int(10) NOT NULL DEFAULT 0 FIRST
  ;
  ALTER TABLE `Journeys` ADD COLUMN `player_week` int(10) NOT NULL DEFAULT 0 FIRST
  ;
  ALTER TABLE `Journeys` ADD COLUMN `player_month` int(10) NOT NULL DEFAULT 0 FIRST
  ;
  UPDATE `Journeys` AS `j`
  JOIN (
    SELECT
      MIN(DATE(`created`)) AS `day_1`
    FROM `blotto_player`
  ) AS `ps`
    ON 1
  SET
    `j`.`player_day` = DATEDIFF(DATE(`j`.`player_created`),`ps`.`day_1`)+1
   ,`j`.`player_week` = CEILING(DATEDIFF(DATE(`j`.`player_created`),`ps`.`day_1`)+1/7)
   ,`j`.`player_month` = TIMESTAMPDIFF(MONTH,CONCAT(SUBSTR(`ps`.`day_1`,1,7),'-01'),CONCAT(SUBSTR(DATE(`j`.`player_created`),1,7),'-01'))+1
  ;
  ALTER TABLE `Journeys` ADD PRIMARY KEY (`player_id`)
  ;
  ALTER TABLE `Journeys` ADD FOREIGN KEY (`player_id`) REFERENCES `blotto_player` (`id`)
  ;
  ALTER TABLE `Journeys` ADD INDEX (`dormancy_date`)
  ;
  -- Monthly summary
  DROP TABLE IF EXISTS `JourneysMonthly`
  ;
  CREATE TABLE `JourneysMonthly` AS
  SELECT
    `players`.`player_month`
   ,`players`.`created_mc` AS `mc`
   ,`players`.`days_to_live_avg`
   ,`players`.`tickets` AS `tickets_playing`
   ,IFNULL(`dormancies`.`tickets`,0) AS `tickets_dormant`
   ,0.0000 AS `dormancy_rate`
  FROM (
    SELECT
      `player_month`
     ,`created_mc`
     ,AVG(DATEDIFF(`player_draw_closed`,`player_created`)) AS `days_to_live_avg`
     ,SUM(`supporters`) AS `supporters` -- not yet cumulative
     ,SUM(`tickets`) AS `tickets` -- not yet cumulative
    FROM `Journeys`
    WHERE `status`='entered'
    GROUP BY `player_month`
  ) AS `players`
  LEFT JOIN (
    SELECT
      DATE(CONCAT(SUBSTR(`dormancy_date`,1,7),'-01')) AS `dormancy_mc`
     ,SUM(`supporters`) AS `supporters`
     ,SUM(`tickets`) AS `tickets`
    FROM `Journeys`
    WHERE `status`='entered'
      AND `dormancy_date` IS NOT NULL
    GROUP BY `dormancy_mc`
  ) AS `dormancies`
    ON `dormancies`.`dormancy_mc`=`players`.`created_mc`
  ;
  -- tickets_playing needs to be cumulative
  SET @cumTickets := 0
  ;
  UPDATE `JourneysMonthly` AS `j1`
  JOIN (
    SELECT
      `player_month`
     ,( @cumTickets := @cumTickets + `tickets_playing` - `tickets_dormant` ) AS `tickets_playing`
    FROM `JourneysMonthly`
  ) AS `j2`
    ON `j2`.`player_month`=`j1`.`player_month`
  SET
    `j1`.`tickets_playing`=`j2`.`tickets_playing`
  ;
  UPDATE `JourneysMonthly`
  SET
    `dormancy_rate`=ROUND(`tickets_dormant`/`tickets_playing`,4)
  ;
  -- recalculate benchmarks (ensure the latest data is available)
  CALL `{{BLOTTO_CONFIG_DB}}`.benchmarks()
  ;
  CREATE OR REPLACE VIEW `JourneysDormancy` AS
  SELECT
    `j`.*
   ,IFNULL(`b`.`dormancy_rate`,0) AS `benchmark_dormancy_rate`
   ,IF(
      `j`.`dormancy_rate`>0 AND `b`.`dormancy_rate`>0
     ,ROUND((1-`j`.`dormancy_rate`)/(1-`b`.`dormancy_rate`),4)
     ,1.00
    ) AS `retention_ratio` -- higher = better
   ,ROUND(IFNULL(`b`.`data_points`,0)*1.0639,4) AS `confidence_ratio` -- disguise the strangely round numbers you get
  FROM `JourneysMonthly` AS `j`
  -- use recalculated benchmarks
  LEFT JOIN `{{BLOTTO_CONFIG_DB}}`.`BenchmarksAggregate` AS `b`
    ON `b`.`player_month`=`j`.`player_month`
  ;
END$$


DELIMITER $$
DROP PROCEDURE IF EXISTS `monies`$$
CREATE PROCEDURE `monies` (
)
BEGIN
  DROP TABLE IF EXISTS `Monies`
  ;
  DROP TABLE IF EXISTS `MoniesTemp`
  ;
  CREATE TABLE `MoniesTemp` (
    `accrue_date` date NOT NULL,
    `wc_date` date NULL,
    `type` char(8) character set ascii,
    `opening_supporters` decimal(10,2) default 0.00,
    `received_players` decimal(10,2) default 0.00,
    `revenue_gross` decimal(10,2) default 0.00,
    `less_external` decimal(10,2) default 0.00,
    `plus_claims` decimal(10,2) default 0.00,
    `less_paid_out` decimal(10,2) default 0.00,
    `revenue_nett` decimal(10,2) default 0.00,
    `fee_rbe` decimal(10,2) default 0.00,
    `fee_anl_post` decimal(10,2) default 0.00,
    `fee_anl_email` decimal(10,2) default 0.00,
    `fee_anl_sms` decimal(10,2) default 0.00,
    `fee_email` decimal(10,2) default 0.00,
    `fee_admin` decimal(10,2) default 0.00,
    `fee_tickets` decimal(10,2) default 0.00,
    `fee_insure` decimal(10,2) default 0.00,
    `fee_winner_post` decimal(10,2) default 0.00,
    `expenses_nett` decimal(10,2) default 0.00,
    `profit_loss` decimal(10,2) default 0.00,
    `profit_loss_cumulative` decimal(10,2) default 0.00,
    `closing_supporters` decimal(10,2) default 0.00,
    `we_date` date NULL,
    PRIMARY KEY (`accrue_date`,`type`)
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8
  ;
  -- received from players
  INSERT INTO `MoniesTemp` (`accrue_date`,`type`,`received_players`,`closing_supporters`)
  SELECT
    `DateDue`
   ,'collect'
   ,SUM(`PaidAmount`)
   ,SUM(`PaidAmount`)
  FROM `blotto_build_collection`
  GROUP BY `DateDue`
  ;
  -- revenue
  INSERT INTO `MoniesTemp` (`accrue_date`,`type`,`revenue_gross`)
  SELECT
      `draw_closed`
     ,'entries'
     ,ROUND(COUNT(`id`)*{{BLOTTO_TICKET_PRICE}}/100,2)
  FROM `blotto_entry`
  GROUP BY `draw_closed`
  ;
  -- external
  INSERT INTO `MoniesTemp` (`accrue_date`,`type`,`less_external`)
  SELECT
      `draw_closed`
     ,'external'
     ,ROUND(COUNT(`ticket_number`)*{{BLOTTO_TICKET_PRICE}}/100,2)
  FROM `blotto_external`
  GROUP BY `draw_closed`
  ;
  -- claimed against winnings insurance
  INSERT INTO `MoniesTemp` (`accrue_date`,`type`,`plus_claims`)
  SELECT
    `payment_received`
   ,'claims'
   ,SUM(IFNULL(`amount`,0))
  FROM `{{BLOTTO_CONFIG_DB}}`.`blotto_claim`
  WHERE `org_code`='{{BLOTTO_ORG_USER}}'
  GROUP BY `payment_received`
  ;
  -- winnings paid out
  INSERT INTO `MoniesTemp` (`accrue_date`,`type`,`less_paid_out`)
  SELECT
    `e`.`draw_closed`
   ,'wnr_post'
   ,SUM(`w`.`amount`)
  FROM `blotto_winner` AS `w`
  JOIN `blotto_entry` AS `e`
    ON `e`.`id`=`w`.`entry_id`
  GROUP BY `draw_closed`
  ;
  -- draw_fee
  INSERT INTO `MoniesTemp` (`accrue_date`,`type`,`fee_email`,`fee_admin`,`fee_tickets`,`fee_insure`)
  SELECT
      `draw_closed`
     ,'draw_fee'
     ,feeRate('email',`draw_closed`)/100
     ,feeRate('admin',`draw_closed`)/100
     ,COUNT(`id`)*feeRate('ticket',`draw_closed`)/100
     ,COUNT(`id`)*feeRate('insure',`draw_closed`)/100
  FROM `blotto_entry`
  GROUP BY `draw_closed`
  ;
  -- winner_fee
  INSERT INTO `MoniesTemp` (`accrue_date`,`type`,`fee_winner_post`)
  SELECT
      `e`.`draw_closed`
     ,'winner_fee'
     ,COUNT(`w`.`id`)*feeRate('winner_post',`e`.`draw_closed`)/100
  FROM `blotto_winner` AS `w`
  JOIN `blotto_entry` AS `e`
    ON `e`.`id`=`w`.`entry_id`
  GROUP BY `e`.`draw_closed`
  ;
  -- anl_fee
  INSERT INTO `MoniesTemp` (`accrue_date`,`type`,`fee_anl_post`,`fee_anl_email`,`fee_anl_sms`)
  SELECT
      `tickets_issued`
     ,'anl_fee'
     ,IFNULL(SUM(`letter_status` NOT  LIKE 'email%' AND `letter_status` NOT LIKE 'sms%')*feeRate('anl_post',`tickets_issued`)/100,0)
     ,IFNULL(SUM(`letter_status`='email_received')*feeRate('anl_email',`tickets_issued`)/100,0)
     ,IFNULL(SUM(`letter_status`='sms_received')*feeRate('anl_sms',`tickets_issued`)/100,0)
  FROM `ANLs`
  GROUP BY `tickets_issued`
  ;
  -- group by accrue_date and aggregate
  CREATE TABLE `Monies` AS
  SELECT
    `accrue_date`
   ,`wc_date`
   ,SUM(`opening_supporters`) AS `opening_supporters`
   ,SUM(`received_players`) AS `received_players`
   ,SUM(`revenue_gross`) AS `revenue_gross`
   ,SUM(`less_external`) AS `less_external`
   ,SUM(`plus_claims`) AS `plus_claims`
   ,SUM(`less_paid_out`) AS `less_paid_out`
   ,SUM(`revenue_nett`) AS `revenue_nett`
   ,SUM(`fee_rbe`) AS `fee_rbe`
   ,SUM(`fee_anl_post`) AS `fee_anl_post`
   ,SUM(`fee_anl_email`) AS `fee_anl_email`
   ,SUM(`fee_anl_sms`) AS `fee_anl_sms`
   ,SUM(`fee_email`) AS `fee_email`
   ,SUM(`fee_admin`) AS `fee_admin`
   ,SUM(`fee_tickets`) AS `fee_tickets`
   ,SUM(`fee_insure`) AS `fee_insure`
   ,SUM(`fee_winner_post`) AS `fee_winner_post`
   ,SUM(`expenses_nett`) AS `expenses_nett`
   ,SUM(`profit_loss`) AS `profit_loss`
   ,SUM(`profit_loss_cumulative`) AS `profit_loss_cumulative`
   ,SUM(`closing_supporters`) AS `closing_supporters`
   ,`we_date`
  FROM `MoniesTemp`
  GROUP BY `accrue_date`
  ;
  -- primary key
  ALTER TABLE `Monies` ADD PRIMARY KEY (`accrue_date`)
  ;
  -- calculate derived non-cumulative columns
  UPDATE `Monies`
  SET
    `wc_date`=weekCommencingDate(`accrue_date`)
   ,`revenue_nett`=`revenue_gross`+`plus_claims`-(`less_external`+`less_paid_out`)
   ,`expenses_nett`=`fee_rbe`+`fee_anl_post`+`fee_anl_email`+`fee_anl_sms`
                   +`fee_email`+`fee_admin`+`fee_tickets`+`fee_insure`+`fee_winner_post`
  ;
  -- another bite
  UPDATE `Monies`
  SET
    `profit_loss`= `revenue_nett`-`expenses_nett`
   ,`profit_loss_cumulative`= `revenue_nett`-`expenses_nett` -- not cumulative yet
   ,`we_date`=DATE_ADD(`wc_date`,INTERVAL 6 DAY)
  ;
  -- calculate cumulative columns
  SET @cumLottery := 0
  ;
  SET @cumSupporter := 0
  ;
  UPDATE `Monies`
  JOIN (
    SELECT
      `accrue_date`
     ,(
        @cumLottery := @cumLottery + `profit_loss_cumulative`
      ) AS `profit_loss_cumulative`
     ,(
        @cumSupporter := @cumSupporter + `closing_supporters` - `revenue_gross`
      ) AS `closing_supporters`
    FROM `Monies`
    ORDER BY `accrue_date`
  ) AS `cumulative`
    ON `cumulative`.`accrue_date`=`Monies`.`accrue_date`
  SET
    `Monies`.`profit_loss_cumulative`=`cumulative`.`profit_loss_cumulative`
   ,`Monies`.`closing_supporters`=`cumulative`.`closing_supporters`
  ;
  -- calculate opening supporter balances
  UPDATE `Monies`
  SET
    `opening_supporters`=`closing_supporters`+`revenue_gross`-`received_players`
  ;
  -- tidy
  DROP TABLE `MoniesTemp`
  ;
  -- monthly view
  CREATE OR REPLACE VIEW `MoniesMonthly` (
    `accrue_date`
   ,`mc_date`
   ,`opening_supporters`
   ,`received_players`
   ,`revenue_gross`
   ,`less_external`
   ,`plus_claims`
   ,`less_paid_out`
   ,`revenue_nett`
   ,`fee_rbe`
   ,`fee_anl_post`
   ,`fee_anl_email`
   ,`fee_anl_sms`
   ,`fee_email`
   ,`fee_admin`
   ,`fee_tickets`
   ,`fee_insure`
   ,`fee_winner_post`
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
     ,CAST(CONCAT(SUBSTR(`accrue_date`,1,7),'-01') AS date) AS `mc`
     ,CAST(GROUP_CONCAT(`opening_supporters` ORDER BY `accrue_date` ASC LIMIT 1) AS decimal(10,2))
     ,SUM(`received_players`)
     ,SUM(`revenue_gross`)
     ,SUM(`less_external`)
     ,SUM(`plus_claims`)
     ,SUM(`less_paid_out`)
     ,SUM(`revenue_nett`)
     ,SUM(`fee_rbe`)
     ,SUM(`fee_anl_post`)
     ,SUM(`fee_anl_email`)
     ,SUM(`fee_anl_sms`)
     ,SUM(`fee_email`)
     ,SUM(`fee_admin`)
     ,SUM(`fee_tickets`)
     ,SUM(`fee_insure`)
     ,SUM(`fee_winner_post`)
     ,SUM(`expenses_nett`)
     ,SUM(`profit_loss`)
     ,CAST(GROUP_CONCAT(`profit_loss_cumulative` ORDER BY `accrue_date` DESC LIMIT 1) AS decimal(10,2))
     ,CAST(GROUP_CONCAT(`closing_supporters` ORDER BY `accrue_date` DESC LIMIT 1) AS decimal(10,2))
      -- seems no way to not repeat oneself in code if one wants to repeat a column 
     ,DATE_SUB(
        DATE_ADD(
          CAST(CONCAT(SUBSTR(`accrue_date`,1,7),'-01') AS date)
         ,INTERVAL 1 MONTH
        )
       ,INTERVAL 1 DAY
      )
    FROM `Monies`
    GROUP BY `ad`
    HAVING `mc`>='{{BLOTTO_WIN_FIRST}}'
    ORDER BY `ad`
  ;
  -- weekly view
  CREATE OR REPLACE VIEW `MoniesWeekly` (
    `accrue_date`
   ,`wc_date`
   ,`opening_supporters`
   ,`received_players`
   ,`revenue_gross`
   ,`less_external`
   ,`plus_claims`
   ,`less_paid_out`
   ,`revenue_nett`
   ,`fee_rbe`
   ,`fee_anl_post`
   ,`fee_anl_email`
   ,`fee_anl_sms`
   ,`fee_email`
   ,`fee_admin`
   ,`fee_tickets`
   ,`fee_insure`
   ,`fee_winner_post`
   ,`expenses_nett`
   ,`profit_loss`
   ,`profit_loss_cumulative`
   ,`closing_supporters`
   ,`we_date`
  ) AS
    SELECT
      `we_date`
     ,`wc_date`
     ,CAST(GROUP_CONCAT(`opening_supporters` ORDER BY `accrue_date` ASC LIMIT 1) AS decimal(10,2))
     ,SUM(`received_players`)
     ,SUM(`revenue_gross`)
     ,SUM(`less_external`)
     ,SUM(`plus_claims`)
     ,SUM(`less_paid_out`)
     ,SUM(`revenue_nett`)
     ,SUM(`fee_rbe`)
     ,SUM(`fee_anl_post`)
     ,SUM(`fee_anl_email`)
     ,SUM(`fee_anl_sms`)
     ,SUM(`fee_email`)
     ,SUM(`fee_admin`)
     ,SUM(`fee_tickets`)
     ,SUM(`fee_insure`)
     ,SUM(`fee_winner_post`)
     ,SUM(`expenses_nett`)
     ,SUM(`profit_loss`)
     ,CAST(GROUP_CONCAT(`profit_loss_cumulative` ORDER BY `accrue_date` DESC LIMIT 1) AS decimal(10,2))
     ,CAST(GROUP_CONCAT(`closing_supporters` ORDER BY `accrue_date` DESC LIMIT 1) AS decimal(10,2))
     ,`we_date`
    FROM `Monies`
    GROUP BY `wc_date`
    HAVING `wc_date`>='{{BLOTTO_WIN_FIRST}}'
    ORDER BY `wc_date`
  ;
END$$


DELIMITER $$
DROP PROCEDURE IF EXISTS `noshows`$$
CREATE PROCEDURE `noshows` (
)
BEGIN
  INSERT IGNORE INTO `{{BLOTTO_CONFIG_DB}}`.`blotto_noshow`
  SELECT
    CONCAT(SUBSTR(`s`.`signed`,1,7),'-01') AS `month_commencing`
   ,'{{BLOTTO_ORG_USER}}' AS `org_code`
   ,SUM(`c`.`ClientRef` IS NULL) AS `noshows`
   ,COUNT(DISTINCT `s`.`id`) AS `candidates`
  FROM `blotto_supporter` AS `s`
  LEFT JOIN `blotto_build_collection` AS `c`
    ON `c`.`ClientRef`=`s`.`client_ref`
  GROUP BY `month_commencing`
  -- ignore recent couple of months
  HAVING SUBSTR(DATE_ADD(`month_commencing`,INTERVAL 2 MONTH),1,7)<SUBSTR(CURDATE(),1,7)
  ;
  CREATE OR REPLACE VIEW `BenchmarkNoShows` AS
    SELECT
      `noshow`.`month_commencing`
     ,`noshow`.`noshows`
     ,`noshow`.`candidates`
     ,ROUND(IF(`noshow`.`candidates`=0,0,100*`noshow`.`noshows`/`noshow`.`candidates`),2) AS `performance`
     ,`benchmark`.`performance` AS `benchmark_performance`
    FROM `{{BLOTTO_CONFIG_DB}}`.`blotto_noshow` AS `noshow`
    JOIN (
      SELECT
        `month_commencing`
       ,ROUND(AVG(IF(`candidates`=0,0,100*`noshows`/`candidates`)),2) AS `performance`
      FROM `{{BLOTTO_CONFIG_DB}}`.`blotto_noshow`
      -- Small numbers skew results badly
      WHERE `candidates`>50
      GROUP BY `month_commencing`
    ) AS `benchmark`
      ON `benchmark`.`month_commencing`=`noshow`.`month_commencing`
    WHERE `noshow`.`org_code`='{{BLOTTO_ORG_USER}}'
    -- Small numbers skew results badly
      AND `noshow`.`candidates`>50
  ;
END$$


DELIMITER $$
DROP PROCEDURE IF EXISTS `supporterRevenue`$$
CREATE PROCEDURE `supporterRevenue` (
  IN      approvedOnOrAfter date
 ,IN      approvedOnOrBefore date
 ,IN      cancelMonths int(4) unsigned
)
BEGIN
  SELECT
    COUNT(DISTINCT `results`.`supporter_id`) AS `supporters`
   ,SUM(`results`.`payments`) AS `payments`
   ,SUM(`results`.`revenue`) AS `revenue`
   ,IF(`results`.`months` IS NULL OR `results`.`months`>cancelMonths,CONCAT('>',cancelMonths),`results`.`months`) AS `months_to cancellation`
  FROM (
    SELECT
      `s`.`id` AS `supporter_id`
     ,IFNULL(`c`.`payments`,0) AS `payments`
     ,IFNULL(`c`.`revenue`,0.00) AS `revenue`
     ,TIMESTAMPDIFF(
        MONTH
       ,IFNULL(
          `c`.`first_collected`
         ,IFNULL(
            `m`.`StartDate`
           ,IFNULL(`p`.`started`,`s`.`created`)
          )
        )
       ,`cl`.`cancelled_date`
      ) AS `months`
    FROM `blotto_supporter` AS `s`
    LEFT JOIN `blotto_player` AS `p`
           ON `p`.`supporter_id`=`s`.`id`
    LEFT JOIN `blotto_build_mandate` AS `m`
           ON `m`.`ClientRef`=`p`.`client_ref`
    LEFT JOIN (
      SELECT
        `ClientRef`
       ,COUNT(`DateDue`) AS `payments`
       ,SUM(`PaidAmount`) AS `revenue`
       ,MIN(`DateDue`) AS `first_collected`
      FROM `blotto_build_collection`
      -- await BACS jitter
      WHERE `DateDue`<DATE_SUB(CURDATE(),INTERVAL 7 DAY)
      GROUP BY `ClientRef`
         ) AS `c`
           ON `c`.`ClientRef`=`p`.`client_ref`
    LEFT JOIN `Cancellations` AS `cl`
           ON `cl`.`client_ref`=`p`.`client_ref`
    WHERE `s`.`approved`>=approvedOnOrAfter
      AND `s`.`approved`<=approvedOnOrBefore
  ) AS `results`
  GROUP BY `months_to cancellation`
  HAVING `months_to cancellation`>=0
  ORDER BY 1*`months_to cancellation`
  ;
END$$


DELIMITER $$
DROP PROCEDURE IF EXISTS `supporters`$$
CREATE PROCEDURE `supporters` (
)
BEGIN
  SET @CostPerPlay = {{BLOTTO_TICKET_PRICE}};
  -- Supporter data
  DROP TABLE IF EXISTS `tmp_supporterout`
  ;
  CREATE TABLE `tmp_supporterout` AS
    SELECT
      `s`.`id`
     ,`s`.`created`
     ,`s`.`canvas_code`
     ,`s`.`original_client_ref`
     ,`s`.`current_client_ref`
     ,`s`.`first_draw_close`
     ,`d`.`ticket_number` AS `current_ticket_number`
     ,IFNULL(`s`.`signed`,'') AS `signed`
     ,`s`.`canvas_agent_ref`
     ,`s`.`canvas_ref`
     ,`s`.`title`
     ,`s`.`name_first`
     ,`s`.`name_last`
     ,IFNULL(`s`.`email`,'') AS `email`
     ,IFNULL(`s`.`mobile`,'') AS `mobile`
     ,IFNULL(`s`.`telephone`,'') AS `telephone`
     ,`s`.`address_1`
     ,IFNULL(`s`.`address_2`,'') AS `address_2`
     ,IFNULL(`s`.`address_3`,'') AS `address_3`
     ,`s`.`town`
     ,`s`.`county`
     ,`s`.`postcode`
     ,IFNULL(`s`.`dob`,'') AS `dob`
     ,`s`.`p0`
     ,`s`.`p1`
     ,`s`.`p2`
     ,`s`.`p3`
     ,`s`.`p4`
     ,`s`.`p5`
     ,`s`.`p6`
     ,`s`.`p7`
     ,`s`.`p8`
     ,`s`.`p9`
     ,`s`.`self_excluded`
     ,`s`.`death_reported`
     ,`s`.`death_by_suicide`
     ,IFNULL(`d`.`Provider`,'') AS `Provider`
     ,IFNULL(`d`.`RefNo`,'') AS `RefNo`
     ,IFNULL(`d`.`Name`,'') AS `Name`
     ,IFNULL(`d`.`FirstPayment`,'') AS `FirstPayment`
     ,IFNULL(`d`.`LastCreated`,'') AS `LastCreated`
     ,IFNULL(`d`.`LastUpdated`,'') AS `LastUpdated`
     ,IFNULL(`d`.`LastPayment`,'') AS `LastPayment`
     ,IFNULL(`d`.`Freq`,'') AS `Freq`
     ,IFNULL(`d`.`Amount`,0) AS `Amount`
     ,IFNULL(`d`.`PaymentsCollected`,0) AS `PaymentsCollected`
     ,IFNULL(`d`.`AmountCollected`,'0.00') `AmountCollected`
     ,IFNULL(`d`.`plays`,0) AS `plays`
     ,IFNULL(`d`.`per_play`,'') AS `per_play`
     ,`s`.`opening_balance` + IFNULL(`d`.`balance`,0) AS `balance`
     ,IFNULL(`d`.`Active`,'') AS `active`
     ,IFNULL(IF(`d`.`Freq`='Single','SINGLE',`d`.`Status`),'') AS `status`
     ,IFNULL(`d`.`FailReason`,'') AS `fail_reason`
    FROM (
      SELECT
        `is`.`id`
       ,`is`.`client_ref` AS `original_client_ref`
       ,`is`.`created`
       ,`is`.`canvas_code`
       ,`is`.`canvas_agent_ref`
       ,`is`.`canvas_ref`
       ,`is`.`self_excluded`
       ,`is`.`death_reported`
       ,`is`.`death_by_suicide`
       ,`ip`.`client_ref` AS `current_client_ref`
       ,`ip`.`first_draw_close`
       ,`ip`.`opening_balance`
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
       ,`is`.`signed`
       ,`ic`.`dob`
       ,`ic`.`p0`
       ,`ic`.`p1`
       ,`ic`.`p2`
       ,`ic`.`p3`
       ,`ic`.`p4`
       ,`ic`.`p5`
       ,`ic`.`p6`
       ,`ic`.`p7`
       ,`ic`.`p8`
       ,`ic`.`p9`
      FROM `blotto_supporter` AS `is`
      JOIN (
        SELECT
          `supporter_id`
          ,MAX(`started`) AS `latest`
        FROM `blotto_player`
        GROUP BY `supporter_id`
      ) AS `ipl`
        ON `ipl`.`supporter_id`=`is`.`id`
      JOIN `blotto_player` AS `ip`
        ON `ip`.`supporter_id`=`is`.`id`
       AND (
           `ip`.`started`=`ipl`.`latest`
       -- Classic SQL gotcha
       -- = operator does not return true for null=null
         OR (
               `ip`.`started` IS NULL
           AND `ipl`.`latest` IS NULL
         )
       )
      JOIN (
        SELECT
          `supporter_id`
          ,MAX(`created`) AS `latest`
        FROM `blotto_contact`
        GROUP BY `supporter_id`
      ) AS `icl`
        ON `icl`.`supporter_id`=`is`.`id`
      JOIN `blotto_contact` AS `ic`
        ON `ic`.`supporter_id`=`is`.`id`
       AND `ic`.`created`=`icl`.`latest`
    )      AS `s`
    LEFT JOIN (
      SELECT
        `m`.`Provider`
       ,`m`.`RefNo`
       ,`m`.`RefOrig`
       ,`m`.`ClientRef`
       ,`m`.`Name`
       ,IFNULL(`tk`.`number`,'') AS `ticket_number`
       ,IFNULL(`cl`.`FirstSuccessfulDate`,'') AS `FirstPayment`
       ,`m`.`LastCreated`
       ,`m`.`Updated` AS `LastUpdated`
       ,IFNULL(`cl`.`LastSuccessfulDate`,'') AS `LastPayment`
       ,`m`.`Freq`
       ,`m`.`Amount`
       ,IFNULL(`cl`.`SuccessfulPayments`,0) AS `PaymentsCollected`
       ,dp(IFNULL(`cl`.`AmountCollected`,0),2) AS `AmountCollected`
       ,IFNULL(`e`.`draw_entries`,0) AS `plays`
       ,dp(@CostPerPlay/100,2) AS `per_play`
       ,dp(IFNULL(`cl`.`AmountCollected`,0)-(@CostPerPlay/100*IFNULL(`e`.`draw_entries`,0)),2) AS `balance`
       ,IF(
          `m`.`Freq`='SINGLE'
         ,'SINGLE'
         ,IF(
            `m`.`Status`='' OR `m`.`Status` IS NULL
           ,'DEAD'
           ,IF(
              `m`.`Status` IN ('DELETED','CANCELLED','FAILED','Inactive')
             ,'DEAD'
             ,'ACTIVE'
            )
          )
        ) AS `Active`
       ,`m`.`Status`
       ,`m`.`FailReason`
      FROM `blotto_build_mandate` as `m`
      LEFT JOIN (
        SELECT
          `Provider`
         ,`RefNo`
         ,`RefOrig`
         ,MIN(`DateDue`) AS `FirstSuccessfulDate`
         ,MAX(`DateDue`) AS `LastSuccessfulDate`
         ,COUNT(`DateDue`) AS `SuccessfulPayments`
         ,SUM(`PaidAmount`) AS `AmountCollected`
        FROM `blotto_build_collection`
        -- ## BACS jitter should be dealt with at the time of import from collection table to build table
        -- in select_collection.sql in the payment API
        -- Await BACS jitter
        -- WHERE `DateDue`<DATE_SUB(CURDATE(),INTERVAL 7 DAY)
        -- Unless cardnet
        -- OR `Provider` = 'CDNT'
        GROUP BY `Provider`,`RefNo`
      ) AS `cl`
        ON `cl`.`Provider`=`m`.`Provider`
       AND `cl`.`RefNo`=`m`.`RefNo`
      LEFT JOIN (
        SELECT
          `client_ref`
        -- Not DISTINCT because we want total entries in all draws
         ,COUNT(`ticket_number`) AS `draw_entries`
        FROM `blotto_entry`
        WHERE `draw_closed` IS NOT NULL -- superfluous but for clarity
        GROUP BY `client_ref`
      )         AS `e`
                ON `e`.`client_ref`=`m`.`ClientRef`
      LEFT JOIN `{{BLOTTO_TICKET_DB}}`.`blotto_ticket` AS `tk`
             ON `tk`.`mandate_provider`=`m`.`Provider`
            AND `tk`.`client_ref`=`m`.`ClientRef`
            AND `tk`.`org_id`={{BLOTTO_ORG_ID}}
      WHERE 1
      GROUP BY IFNULL(`tk`.`number`,`m`.`ClientRef`)
    )      AS `d`
           ON `d`.`ClientRef`=`s`.`current_client_ref`
    GROUP BY `s`.`id`,`d`.`ticket_number`
    ORDER BY `s`.`id`,`d`.`ticket_number`
  ;
  ALTER TABLE `tmp_supporterout`
  ADD PRIMARY KEY (`id`,`current_ticket_number`),
  CHANGE `FirstPayment` `FirstPayment` date NOT NULL AFTER `Name`,
  CHANGE `LastCreated` `LastCreated` date NOT NULL AFTER `FirstPayment`,
  CHANGE `LastUpdated` `LastUpdated` date NOT NULL AFTER `LastCreated`,
  CHANGE `LastPayment` `LastPayment` date NOT NULL AFTER `LastUpdated`,
  CHANGE `AmountCollected` `AmountCollected` decimal(10,2) NOT NULL AFTER `PaymentsCollected`,
  CHANGE `per_play` `per_play` decimal(10,2) NOT NULL AFTER `plays`,
  CHANGE `balance` `balance` decimal(10,2) NOT NULL AFTER `per_play`
  ;
  -- Player data
  DROP TABLE IF EXISTS `tmp_player`
  ;
  CREATE TABLE `tmp_player` AS
    SELECT
      `p`.`id`
     ,`p`.`supporter_id`
     ,`p`.`client_ref`
     ,`d`.`tickets`
     ,IFNULL(`d`.`FirstPayment`,'') AS `FirstPayment`
     ,IFNULL(`d`.`FirstCreated`,'') AS `FirstCreated`
     ,IFNULL(`d`.`PaymentsCollected`,0) AS `PaymentsCollected`
     ,IFNULL(`d`.`AmountCollected`,0.00) AS `AmountCollected`
     ,IFNULL(`d`.`plays`,0) AS `plays`
     ,`p`.`opening_balance` + IFNULL(`d`.`balance`,0) AS `balance`
    FROM `blotto_player` AS `p`
    LEFT JOIN (
      SELECT
        `m`.`Provider`
       ,`m`.`RefNo`
       ,`m`.`RefOrig`
       ,`m`.`ClientRef`
       ,`m`.`Name`
       ,`tn`.`tickets`
       ,IFNULL(`cl`.`FirstSuccessfulDate`,'') AS `FirstPayment`
       ,`m`.`Created` AS `FirstCreated`
       ,IFNULL(`cl`.`SuccessfulPayments`,0) AS `PaymentsCollected`
       ,dp(IFNULL(`cl`.`AmountCollected`,0),2) AS `AmountCollected`
       ,IFNULL(`e`.`draw_entries`,0) AS `plays`
       ,dp(@CostPerPlay/100,2) AS `per_play`
       ,dp(IFNULL(`cl`.`AmountCollected`,0)-(@CostPerPlay/100*IFNULL(`e`.`draw_entries`,0)),2) AS `balance`
       ,IF(
          `m`.`Status`='' OR `m`.`Status` IS NULL
         ,'DEAD'
         ,IF(
            `m`.`Status` IN ('DELETED','CANCELLED','FAILED','Inactive')
           ,'DEAD'
           ,IF(
              `m`.`Freq`='SINGLE'
             ,'SINGLE'
             ,'ACTIVE'
            )
          )
        ) AS `Active`
       ,`m`.`Status`
       ,`m`.`FailReason`
      FROM `blotto_build_mandate` as `m`
      LEFT JOIN (
        SELECT
          `Provider`
         ,`RefNo`
         ,`RefOrig`
         ,MIN(`DateDue`) AS `FirstSuccessfulDate`
         ,MAX(`DateDue`) AS `LastSuccessfulDate`
         ,COUNT(`DateDue`) AS `SuccessfulPayments`
         ,SUM(`PaidAmount`) AS `AmountCollected`
        FROM `blotto_build_collection`
        -- await BACS jitter as above, deal with in API
        -- WHERE `DateDue`<DATE_SUB(CURDATE(),INTERVAL 7 DAY)
        GROUP BY `Provider`,`RefNo`
      ) AS `cl`
        ON `cl`.`Provider`=`m`.`Provider`
       AND `cl`.`RefNo`=`m`.`RefNo`
      LEFT JOIN (
        SELECT
          `client_ref`
        -- Not DISTINCT because we want total entries in all draws
         ,COUNT(`ticket_number`) AS `draw_entries`
        FROM `blotto_entry`
        WHERE `draw_closed` IS NOT NULL -- superfluous but for clarity
        GROUP BY `client_ref`
      )         AS `e`
                ON `e`.`client_ref`=`m`.`ClientRef`
      LEFT JOIN (
        SELECT
        `tk`.`mandate_provider`, `tk`.`client_ref`,
        GROUP_CONCAT(`tk`.`number` ORDER BY `tk`.`number` SEPARATOR ', ') AS `tickets`
        FROM  `{{BLOTTO_TICKET_DB}}`.`blotto_ticket` AS `tk`
        WHERE `tk`.`org_id`={{BLOTTO_ORG_ID}}
        GROUP BY `tk`.`mandate_provider`, `tk`.`client_ref`
      ) AS `tn`
            ON `tn`.`mandate_provider`=`m`.`Provider`
            AND `tn`.`client_ref`=`m`.`ClientRef`
      WHERE 1
      GROUP BY IFNULL(`tn`.`tickets`,`m`.`ClientRef`) -- DL: check this
    )      AS `d`
           ON `d`.`ClientRef`=`p`.`client_ref`
    GROUP BY `p`.`id`
    ORDER BY `p`.`id`
  ;
  ALTER TABLE `tmp_player`
  ADD PRIMARY KEY (`id`),
  CHANGE `FirstPayment` `FirstPayment` date NOT NULL AFTER `tickets`,
  CHANGE `FirstCreated` `FirstCreated` date NOT NULL AFTER `FirstPayment`,
  CHANGE `AmountCollected` `AmountCollected` decimal(10,2) NOT NULL AFTER `PaymentsCollected`,
  CHANGE `balance` `balance` decimal(6,2) NOT NULL AFTER `plays`
  ;
-- Add supporters and players from external tickets
-- currently a manual process and with any luck we have done the last one
--  CALL externals();
  -- Add player data to supporter data
  DROP TABLE IF EXISTS `Supporters`
  ;
  CREATE TABLE `Supporters` AS
    SELECT
      `s`.`created`
     ,'0000-00-00' AS `cancelled`
     ,`s`.`canvas_code` AS `ccc`
     ,`s`.`original_client_ref`
     ,`s`.`current_client_ref`
     ,`s`.`current_ticket_number`
     ,`s`.`signed`
     ,`s`.`canvas_agent_ref`
     ,`s`.`canvas_ref`
     ,`s`.`id` AS `supporter_id`
     ,`s`.`title`
     ,`s`.`name_first`
     ,`s`.`name_last`
     ,`s`.`email`
     ,`s`.`mobile`
     ,`s`.`telephone`
     ,`s`.`address_1`
     ,`s`.`address_2`
     ,`s`.`address_3`
     ,`s`.`town`
     ,`s`.`county`
     ,`s`.`postcode`
     ,`s`.`dob`
     ,`s`.`p0`
     ,`s`.`p1`
     ,`s`.`p2`
     ,`s`.`p3`
     ,`s`.`p4`
     ,`s`.`p5`
     ,`s`.`p6`
     ,`s`.`p7`
     ,`s`.`p8`
     ,`s`.`p9`
     ,IF(LENGTH(`s`.`RefNo`)=0,'MISSING','') AS `mandate_missing`
     ,`s`.`Provider` AS `mandate_provider`
     ,`s`.`RefNo` AS `mandate_ref_no`
     ,`s`.`Name` AS `mandate_name`
     ,`s`.`LastUpdated` AS `mandate_last_updated`
     ,`s`.`LastPayment` AS `latest_payment_collected`
     ,`s`.`active`
     ,`s`.`status`
     ,`s`.`fail_reason`
     ,`s`.`Freq` AS `latest_mandate_frequency`
     ,`s`.`Amount` AS `latest_mandate_amount`
     ,`s`.`per_play`
     ,`p`.`players`
     ,`p`.`ticket_history`
     ,`p`.`original_first_payment` AS `supporter_first_payment`
     ,`p`.`original_mandate_created` AS `supporter_first_mandate`
     ,`p`.`total_payments` AS `supporter_total_payments`
     ,`p`.`total_amount` AS `supporter_total_amount`
     ,`p`.`total_plays` AS `supporter_total_plays`
     ,`p`.`total_balance` AS `supporter_current_balance`
     ,'' AS `latest_player_columns`
     ,`s`.`first_draw_close` AS `latest_player_first_draw`
     ,`s`.`FirstPayment` AS `latest_player_first_payment`
     ,`s`.`LastCreated` AS `latest_player_mandate`
     ,`s`.`PaymentsCollected` AS `latest_player_payments`
     ,`s`.`AmountCollected` AS `latest_player_amount`
     ,`s`.`plays` AS `latest_player_plays`
     ,`s`.`balance` AS `latest_player_balance`
     ,IFNULL(`s`.`self_excluded`,'') AS `self_excluded`
     ,IFNULL(`s`.`death_reported`,'') AS `death_reported`
     ,IF(`s`.`death_by_suicide`>0,'Y','') AS `death_by_suicide`
    FROM (
      SELECT
        `supporter_id`
       ,COUNT(`id`) AS `players`
       ,GROUP_CONCAT(`tickets` ORDER BY `client_ref` SEPARATOR ' / ') AS `ticket_history`
       ,IF(LENGTH(`FirstPayment`)=0,'',MIN(`FirstPayment`)) AS `original_first_payment`
       ,IF(LENGTH(`FirstCreated`)=0,'',MIN(`FirstCreated`)) AS `original_mandate_created`
       ,SUM(`PaymentsCollected`) AS `total_payments`
       ,SUM(`AmountCollected`) AS `total_amount`
       ,SUM(`plays`) AS `total_plays`
       ,SUM(`balance`) AS `total_balance`
      FROM `tmp_player`
      GROUP BY `supporter_id`
    ) AS `p`
    JOIN `tmp_supporterout` AS `s`
      ON `s`.`id`=`p`.`supporter_id`
    GROUP BY `s`.`id`,`s`.`current_ticket_number`
    ORDER BY `s`.`created`,`ccc`,`s`.`current_client_ref`,`s`.`current_ticket_number`
  ;
  ALTER TABLE `Supporters`
  ADD PRIMARY KEY (`created`,`ccc`,`current_client_ref`,`current_ticket_number`)
  ;
  ALTER TABLE `Supporters`
  ADD UNIQUE KEY `client_ref_ticket_number` (`current_client_ref`,`current_ticket_number`)
  ;
  ALTER TABLE `Supporters`
  ADD KEY `mandate_provider` (`mandate_provider`)
  ;
  ALTER TABLE `Supporters`
  ADD KEY `mandate_reference` (`mandate_provider`,`mandate_ref_no`)
  ;
  ALTER TABLE `Supporters`
  ADD KEY `created` (`created`)
  ;
  ALTER TABLE `Supporters`
  ADD KEY `ccc` (`ccc`)
  ;
  ALTER TABLE `Supporters`
  ADD KEY `canvas_agent_ref` (`canvas_agent_ref`)
  ;
  ALTER TABLE `Supporters`
  ADD KEY `supporter_id` (`supporter_id`)
  ;
  ALTER TABLE `Supporters`
  CHANGE COLUMN `dob` `dob` VARCHAR(255) CHARACTER SET utf8 NOT NULL
  ;
  CREATE FULLTEXT INDEX `search_idx`
    ON `Supporters` (
      `name_first`,
      `name_last`,
      `email`,
      `mobile`,
      `telephone`,
      `address_1`,
      `address_2`,
      `address_3`,
      `town`,
      `postcode`,
      `dob`
    )
  ;
  UPDATE `Supporters`
  SET
    `cancelled`=''
  ;
  UPDATE `Supporters` AS `s`
  JOIN `Cancellations` AS `c`
    ON `s`.`current_client_ref`=`c`.`client_ref`
  SET
    `s`.`cancelled`=`c`.`cancelled_date`
   ,`s`.`status`=`c`.`mandate_status`
   ,`s`.`fail_reason`=`c`.`mandate_fail_reason`
  ;
END$$


DELIMITER $$
DROP PROCEDURE IF EXISTS `updates`$$
CREATE PROCEDURE `updates` (
)
BEGIN
  /*
  First thing is to ensure cancellation milestone_date correctly reflects
  the current configuration of cancelDate()

  This is because milestone_date is a primary key component used to logically
  identify the right cancellation (eg. cancel-reinstate-cancel scenario)

  Without this data refresh, the cancellation INSERT IGNORE previously caused
  ghost insert havoc when cancelDate() started returning different values

  Older cancellation milestones (the "early bread" for a "reinstatement sandwich")
  are unaffected because the "late bread" always gets hit with the INSERT IGNORE
  */
  UPDATE `blotto_update` AS `u`
  -- restrict to the latest cancellation row for each player
  JOIN (
    SELECT
      `c`.`supporter_id`
     ,MAX(`c`.`id`) AS `latest_id`
     ,`r`.`latest_id` AS `reinstate_latest_id`
    FROM `blotto_update` AS `c`
    LEFT JOIN (
      -- reinstatement of the latest cancellation
      SELECT
        `supporter_id`
       ,MAX(`id`) AS `latest_id`
      FROM `blotto_update`
      WHERE `milestone`='reinstatement'
      GROUP BY `supporter_id`
    ) AS `r`
      ON `r`.`supporter_id`=`c`.`supporter_id`
     AND `r`.`latest_id`>`c`.`id`
    WHERE `milestone`='cancellation'
    GROUP BY `supporter_id`
    -- not reinstated after the latest cancellation
    HAVING `reinstate_latest_id` IS NULL
        OR `reinstate_latest_id`<`latest_id`
  ) AS `us`
    ON `us`.`latest_id`=`u`.`id`
  -- get contemporary values for player cancelled_date
  JOIN (
    SELECT
      `p`.`supporter_id`
     ,`cancelled`.`cancelled_date`
    FROM `blotto_player` AS `p`
    JOIN (
      SELECT
        `client_ref`
       ,`cancelled_date`
      FROM `Cancellations`
      GROUP BY `client_ref`
    ) AS `cancelled`
      ON `cancelled`.`client_ref`=`p`.`client_ref`
    GROUP BY `p`.`supporter_id`
  ) AS `currently`
    ON `currently`.`supporter_id`=`u`.`supporter_id`
  -- set the milestone_date to the contemporary cancel date
  SET `u`.`milestone_date`=`currently`.`cancelled_date`
  WHERE `u`.`milestone`='cancellation'
  ;
  -- `milestone`='created'
  INSERT IGNORE INTO `blotto_update`
    (`updated`,`milestone`,`milestone_date`,`supporter_id`,`player_id`,`contact_id`)
    SELECT
      CURDATE()
     ,'created'
     ,`s`.`created`
     ,`s`.`id`
     ,`p`.`id`
     ,MIN(`c`.`id`)
    FROM `blotto_supporter` AS `s`
    JOIN `blotto_player` AS `p`
      ON `p`.`supporter_id`=`s`.`id`
     AND `p`.`client_ref`=`s`.`client_ref`
    JOIN `blotto_contact` AS `c`
      ON `c`.`supporter_id`=`s`.`id`
    GROUP BY `p`.`id`
  ;
  -- `milestone`='first_collection'
  INSERT IGNORE INTO `blotto_update`
    (`updated`,`milestone`,`milestone_date`,`supporter_id`,`player_id`,`contact_id`)
    SELECT
      CURDATE()
     ,'first_collection'
     ,`cln`.`DateDue`
     ,`s`.`id`
     ,MIN(`p`.`id`)
     ,MAX(`c`.`id`)
    FROM (
      SELECT
        `ClientRef`
       ,MIN(`DateDue`) AS `DateDue`
      FROM `blotto_build_collection`
      -- await BACS jitter
      WHERE `DateDue`<DATE_SUB(CURDATE(),INTERVAL 7 DAY)
      GROUP BY `RefNo`
    ) AS `cln`
    JOIN `blotto_player` AS `p`
      ON `p`.`client_ref`=`cln`.`ClientRef`
    JOIN `blotto_supporter` AS `s`
      ON `s`.`id`=`p`.`supporter_id`
     AND `s`.`client_ref`=`p`.`client_ref` -- original player
    JOIN `blotto_contact` AS `c`
      ON `c`.`supporter_id`=`s`.`id`
     AND DATE(`c`.`created`)<=`cln`.`DateDue`
    GROUP BY `s`.`id`
  ;
  -- `milestone`='bacs_change'
  INSERT IGNORE INTO `blotto_update`
    (`updated`,`milestone`,`milestone_date`,`supporter_id`,`player_id`,`contact_id`)
    SELECT
      CURDATE()
     ,'bacs_change'
     ,`p`.`started`
     ,`s`.`id`
     ,`p`.`id`
     ,MAX(`c`.`id`)
    FROM `blotto_player` AS `p`
    JOIN `blotto_supporter` AS `s`
      ON `s`.`id`=`p`.`supporter_id`
     AND `s`.`client_ref`!=`p`.`client_ref`
    JOIN `blotto_contact` AS `c`
      ON `c`.`supporter_id`=`s`.`id`
     AND DATE(`c`.`created`)<=`p`.`started`
    GROUP BY `p`.`id`
  ;
  -- `milestone`='contact_change'
  INSERT IGNORE INTO `blotto_update`
    (`updated`,`milestone`,`milestone_date`,`supporter_id`,`player_id`,`contact_id`)
    SELECT
      CURDATE()
     ,'contact_change'
     ,DATE(`c`.`created`)
     ,`s`.`id`
     ,MAX(`p`.`id`)
     ,`c`.`id`
    FROM (
      SELECT
        `supporter_id`
       ,MIN(`c`.`id`) AS `id`
      FROM `blotto_contact` AS `c`
      GROUP BY `supporter_id`
    ) AS `cfirst`
    JOIN `blotto_contact` AS `c`
           ON `c`.`supporter_id`=`cfirst`.`supporter_id`
          AND `c`.`id`!=`cfirst`.`id`
    JOIN `blotto_supporter` AS `s`
      ON `s`.`id`=`c`.`supporter_id`
    JOIN `blotto_player` AS `p`
      ON `p`.`supporter_id`=`s`.`id`
     AND `p`.`started`<=DATE(`c`.`created`)
    GROUP BY `c`.`id`
  ;
/*
  -- `milestone`='win'
  INSERT IGNORE INTO `blotto_update`
    (`updated`,`milestone`,`milestone_date`,`supporter_id`,`player_id`,`contact_id`)
    SELECT
      CURDATE()
     ,'win'
     ,MIN(`wins`.`draw_closed`)
     ,`s`.`id`
     ,MAX(`p`.`id`)
     ,`c`.`id`
    FROM (
      SELECT
        `e`.`client_ref`
       ,MIN(`e`.`draw_closed`) AS `draw_closed`
      FROM `blotto_winner` AS `w`
      JOIN `blotto_entry` AS `e`
        ON `e`.`id`=`w`.`entry_id`
      -- update unique keys enforce milestones to be
      -- no more frequent than one per day per type
      -- so insert only one win for any given supporter
      -- otherwise CURDATE() above causes primary key collision
      -- when a supporter has won in more than one draw
      GROUP BY `client_ref`
    ) AS `win`
    JOIN `blotto_player` AS `p`
      ON `p`.`client_ref`=`win`.`client_ref`
     AND `p`.`started`<=DATE(`win`.`draw_closed`)
    JOIN `blotto_supporter` AS `s`
      ON `s`.`id`=`p`.`supporter_id`
    JOIN (
      SELECT
        *
      FROM `blotto_contact`
    ) AS `c`
      ON `c`.`supporter_id`=`s`.`id`
     AND DATE(`c`.`created`)<=`e`.`draw_closed`
    GROUP BY `s`.`id`
  ;
*/
  -- `milestone`='self_excluded'
  INSERT IGNORE INTO `blotto_update`
    (`updated`,`milestone`,`milestone_date`,`supporter_id`,`player_id`,`contact_id`)
    SELECT
      CURDATE()
     ,'self_excluded'
     ,`s`.`self_excluded`
     ,`s`.`id`
     ,MAX(`p`.`id`)
     ,MAX(`c`.`id`)
    FROM `blotto_supporter` AS `s`
    JOIN `blotto_player` AS `p`
      ON `p`.`supporter_id`=`s`.`id`
    JOIN `blotto_contact` AS `c`
      ON `c`.`supporter_id`=`s`.`id`
     AND DATE(`c`.`created`)<=`s`.`self_excluded`
    LEFT JOIN `blotto_update` AS `u`
           ON `u`.`supporter_id`=`s`.`id`
          AND `u`.`milestone`='self_excluded'
    WHERE `s`.`self_excluded` IS NOT NULL
      AND `u`.`id` IS NULL -- not already recorded
    GROUP BY `s`.`id`
  ;
  -- `milestone`='excluded'
  INSERT IGNORE INTO `blotto_update`
    (`updated`,`milestone`,`milestone_date`,`supporter_id`,`player_id`,`contact_id`)
    SELECT
      CURDATE()
     ,'excluded'
     ,`s`.`excluded`
     ,`s`.`id`
     ,MAX(`p`.`id`)
     ,MAX(`c`.`id`)
    FROM `blotto_supporter` AS `s`
    JOIN `blotto_player` AS `p`
      ON `p`.`supporter_id`=`s`.`id`
    JOIN `blotto_contact` AS `c`
      ON `c`.`supporter_id`=`s`.`id`
     AND DATE(`c`.`created`)<=`s`.`excluded`
    LEFT JOIN `blotto_update` AS `u`
           ON `u`.`supporter_id`=`s`.`id`
          AND `u`.`milestone`='excluded'
    WHERE `s`.`excluded` IS NOT NULL
      AND `u`.`id` IS NULL -- not already recorded
    GROUP BY `s`.`id`
  ;
  -- `milestone`='death_reported'
  INSERT IGNORE INTO `blotto_update`
    (`updated`,`milestone`,`milestone_date`,`supporter_id`,`player_id`,`contact_id`)
    SELECT
      CURDATE()
     ,'death_reported'
     ,`s`.`death_reported`
     ,`s`.`id`
     ,MAX(`p`.`id`)
     ,MAX(`c`.`id`)
    FROM `blotto_supporter` AS `s`
    JOIN `blotto_player` AS `p`
      ON `p`.`supporter_id`=`s`.`id`
    JOIN `blotto_contact` AS `c`
      ON `c`.`supporter_id`=`s`.`id`
     AND DATE(`c`.`created`)<=`s`.`death_reported`
    LEFT JOIN `blotto_update` AS `u`
           ON `u`.`supporter_id`=`s`.`id`
          AND `u`.`milestone`='death_reported'
    WHERE `s`.`death_reported` IS NOT NULL
      AND `u`.`id` IS NULL -- not already recorded
    GROUP BY `s`.`id`
  ;
  -- `milestone`='cancellation'
  -- cancelled_date changes with BLOTTO_CANCEL_RULE but the primary partial key
  -- milestone_date always gets repaired above so this insert-ignore does not
  -- insert ghosts
  INSERT IGNORE INTO `blotto_update`
    (`updated`,`milestone`,`milestone_date`,`supporter_id`,`player_id`,`contact_id`)
    SELECT
      CURDATE()
     ,'cancellation'
     ,`cnl`.`cancelled_date`
     ,`s`.`id`
     ,`p`.`id`
     ,MAX(`c`.`id`)
    FROM `Cancellations` AS `cnl` -- which is BACS de-jittered
    JOIN `blotto_player` AS `p`
      ON `p`.`client_ref`=`cnl`.`client_ref`
    JOIN `blotto_supporter` AS `s`
      ON `s`.`id`=`p`.`supporter_id`
    JOIN `blotto_contact` AS `c`
      ON `c`.`supporter_id`=`s`.`id`
     AND DATE(`c`.`created`)<=`cnl`.`cancelled_date`
    GROUP BY `cnl`.`client_ref`
  ;
  -- `milestone`='reinstatement'
  DROP TABLE IF EXISTS `blotto_update_tmp`
  ;
  CREATE TABLE `blotto_update_tmp` LIKE `blotto_update`
  ;
  INSERT IGNORE INTO `blotto_update_tmp`
    -- the potential pool that *might* need reinstating
    (`updated`,`milestone`,`milestone_date`,`supporter_id`,`player_id`,`contact_id`)
      SELECT
        CURDATE()
       ,'reinstatement'
       ,CURDATE()
       ,`s`.`id` AS `supporter_id`
       ,`ps`.`latest_id` AS `player_id`
       ,`cs`.`latest_id` AS `contact_id`
      FROM `blotto_supporter` AS `s`
      JOIN (
        SELECT
          `supporter_id`
         ,MAX(`id`) AS `latest_id`
        FROM `blotto_player`
        GROUP BY `supporter_id`
      ) AS `ps`
        ON `ps`.`supporter_id`=`s`.`id`
      JOIN (
        SELECT
          `supporter_id`
         ,MAX(`id`) AS `latest_id`
        FROM `blotto_contact`
        GROUP BY `supporter_id`
      ) AS `cs`
        ON `cs`.`supporter_id`=`s`.`id`
      JOIN (
        SELECT
          `supporter_id`
         ,SUM(`milestone`='cancellation')>SUM(`milestone`='reinstatement') AS `cancelled`
        FROM `blotto_update`
        GROUP BY `supporter_id`
        HAVING `cancelled`>0
      ) AS `chk`
      -- CRM record says cancelled ...
        ON `chk`.`supporter_id`=`s`.`id`
      LEFT JOIN (
        SELECT
          `plyr`.`supporter_id`
        FROM `blotto_player` AS `plyr`
        JOIN `Cancellations` AS `cnl`
          ON `cnl`.`client_ref`=`plyr`.`client_ref`
        GROUP BY `plyr`.`supporter_id`
      )      AS `cnls`
             ON `cnls`.`supporter_id`=`ps`.`supporter_id`
      -- ... but Cancellations table says no longer cancelled
      WHERE `cnls`.`supporter_id` IS NULL
  ;
  INSERT IGNORE INTO `blotto_update`
    (`updated`,`milestone`,`milestone_date`,`supporter_id`,`player_id`,`contact_id`)
    SELECT
      `t`.`updated`
     ,`t`.`milestone`
     ,`t`.`milestone_date`
     ,`t`.`supporter_id`
     ,`t`.`player_id`
     ,`t`.`contact_id`
    FROM `blotto_update_tmp` as `t`
  ;
  DROP TABLE `blotto_update_tmp`
  ;
  CALL updatesTableUpdates();
  CALL updatesTableUpdatesLatest();
END$$


DELIMITER $$
DROP PROCEDURE IF EXISTS `updatesEmpty`$$
CREATE PROCEDURE `updatesEmpty` (
)
BEGIN
  -- guarantee there is are updates output tables on a first build (BLOTTO_DEV_PAY_FREEZE prevents updates() from running)
  CREATE TABLE IF NOT EXISTS `Updates` (
    `updated` date NOT NULL,
    `supporter_id` int(11) unsigned NOT NULL,
    `updater` varchar(255) CHARACTER SET ascii COLLATE ascii_general_ci NOT NULL,
    `milestone` char(16) CHARACTER SET ascii COLLATE ascii_general_ci NOT NULL,
    `milestone_date` date DEFAULT NULL,
    `signed` date DEFAULT NULL,
    `created` date DEFAULT NULL,
    `cancelled` varchar(10) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL,
    `ccc` char(16) CHARACTER SET ascii COLLATE ascii_general_ci DEFAULT NULL,
    `canvas_ref` int(11) unsigned DEFAULT NULL,
    `client_ref_orig` varchar(64) CHARACTER SET ascii COLLATE ascii_general_ci NOT NULL,
    `client_ref` varchar(64) CHARACTER SET ascii COLLATE ascii_general_ci NOT NULL,
    `tickets` bigint(21) NOT NULL,
    `ticket_numbers` mediumtext CHARACTER SET ascii COLLATE ascii_general_ci NOT NULL,
    `title` varchar(255) DEFAULT NULL,
    `name_first` varchar(255) DEFAULT NULL,
    `name_last` varchar(255) DEFAULT NULL,
    `email` varchar(255) DEFAULT NULL,
    `mobile` varchar(255) DEFAULT NULL,
    `telephone` varchar(255) DEFAULT NULL,
    `address_1` varchar(255) DEFAULT NULL,
    `address_2` varchar(255) DEFAULT NULL,
    `address_3` varchar(255) DEFAULT NULL,
    `town` varchar(255) DEFAULT NULL,
    `county` varchar(255) DEFAULT NULL,
    `postcode` varchar(255) DEFAULT NULL,
    `dob` date DEFAULT NULL,
    `no_post` varchar(255) DEFAULT NULL,
    `no_prom` varchar(255) DEFAULT NULL,
    `post` varchar(255) DEFAULT NULL,
    `eml` varchar(255) DEFAULT NULL,
    `tel` varchar(255) DEFAULT NULL,
    `prom` varchar(255) DEFAULT NULL,
    `bnk_pos` varchar(255) DEFAULT NULL,
    `first_collected` varchar(10) CHARACTER SET utf8 COLLATE utf8_general_ci DEFAULT '',
    `last_collected` varchar(10) CHARACTER SET utf8 COLLATE utf8_general_ci DEFAULT '',
    `first_draw` varchar(10) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL,
    `death_reported` varchar(10) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL,
    `death_by_suicide` tinyint(1) unsigned NOT NULL DEFAULT 0,
    `mandate_status` varchar(16) CHARACTER SET ascii COLLATE ascii_general_ci NOT NULL,
    `collection_frequency` varchar(16) CHARACTER SET ascii COLLATE ascii_general_ci NOT NULL,
    `unused_1` char(0) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL,
    `unused_2` char(0) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL,
    `unused_3` char(0) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL,
    `unused_4` char(0) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL,
    PRIMARY KEY (`updated`,`client_ref_orig`,`milestone`,`client_ref`),
    KEY `client_ref` (`client_ref`),
    KEY `milestone_date` (`milestone_date`),
    KEY `updater` (`updater`)
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci
  ;
  CREATE TABLE `UpdatesLatest` (
    `updated` date,
    `sort2_supporter_id` int(11) unsigned NOT NULL,
    `unused_1` char(0) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL,
    `unused_2` char(0) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL,
    `unused_3` char(0) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL,
    `sort1_signed` date NOT NULL,
    `created` date DEFAULT NULL,
    `cancelled` varchar(10) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL,
    `ccc` char(16) CHARACTER SET ascii COLLATE ascii_general_ci DEFAULT NULL,
    `canvas_ref` int(11) unsigned DEFAULT NULL,
    `client_ref_orig` varchar(64) CHARACTER SET ascii COLLATE ascii_general_ci NOT NULL,
    `client_ref` mediumtext CHARACTER SET ascii COLLATE ascii_general_ci DEFAULT NULL,
    `tickets` mediumtext CHARACTER SET utf8 COLLATE utf8_general_ci DEFAULT NULL,
    `ticket_numbers` mediumtext CHARACTER SET ascii COLLATE ascii_general_ci DEFAULT NULL,
    `title` mediumtext DEFAULT NULL,
    `name_first` mediumtext DEFAULT NULL,
    `name_last` mediumtext DEFAULT NULL,
    `email` mediumtext DEFAULT NULL,
    `mobile` mediumtext DEFAULT NULL,
    `telephone` mediumtext DEFAULT NULL,
    `address_1` mediumtext DEFAULT NULL,
    `address_2` mediumtext DEFAULT NULL,
    `address_3` mediumtext DEFAULT NULL,
    `town` mediumtext DEFAULT NULL,
    `county` mediumtext DEFAULT NULL,
    `postcode` mediumtext DEFAULT NULL,
    `dob` mediumtext CHARACTER SET utf8 COLLATE utf8_general_ci DEFAULT NULL,
    `no_post` varchar(255) DEFAULT NULL,
    `no_prom` varchar(255) DEFAULT NULL,
    `post` varchar(255) DEFAULT NULL,
    `eml` varchar(255) DEFAULT NULL,
    `tel` varchar(255) DEFAULT NULL,
    `prom` varchar(255) DEFAULT NULL,
    `bnk_pos` varchar(255) DEFAULT NULL,
    `first_collected` varchar(10) CHARACTER SET utf8 COLLATE utf8_general_ci DEFAULT '',
    `last_collected` varchar(10) CHARACTER SET utf8 COLLATE utf8_general_ci DEFAULT '',
    `first_draw` varchar(10) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL,
    `death_reported` varchar(10) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL,
    `death_by_suicide` tinyint(1) unsigned NOT NULL DEFAULT 0,
    `mandate_status` mediumtext CHARACTER SET ascii COLLATE ascii_general_ci DEFAULT NULL,
    `collection_frequency` mediumtext CHARACTER SET ascii COLLATE ascii_general_ci DEFAULT NULL,
    `spent` varchar(65) CHARACTER SET utf8 COLLATE utf8_general_ci DEFAULT NULL,
    `opening_balance` varchar(44) CHARACTER SET utf8 COLLATE utf8_general_ci DEFAULT NULL,
    `collected` varchar(73) CHARACTER SET utf8 COLLATE utf8_general_ci DEFAULT NULL,
    `balance` varchar(77) CHARACTER SET utf8 COLLATE utf8_general_ci DEFAULT NULL,
    PRIMARY KEY (`sort1_signed`,`sort2_supporter_id`),
    KEY `ccc` (`ccc`),
    KEY `client_ref_orig` (`client_ref_orig`),
    KEY `client_ref` (`client_ref`),
    KEY `cancelled` (`cancelled`)
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci
  ;

END$$


DELIMITER $$
DROP PROCEDURE IF EXISTS `updatesTableUpdates`$$
CREATE PROCEDURE `updatesTableUpdates` (
)
BEGIN
  -- Output CRM milestone table
  DROP TABLE IF EXISTS `Updates`
  ;
  CREATE TABLE `Updates` AS
    SELECT
      `u`.`updated`
     ,`u`.`supporter_id`
     ,IF(`milestone`='contact_change',`c`.`updater`,'SYSTEM') AS `updater`
      -- non-ephemeral things (the model does not store if/when they are changed)
     ,`u`.`milestone`
     ,`u`.`milestone_date`
     ,`s`.`signed`
     ,`s`.`created`
     ,'0000-00-00' AS `cancelled`
     ,`s`.`canvas_code` AS `ccc`
     ,`s`.`canvas_ref`
     ,`s`.`client_ref` AS `client_ref_orig`
      -- ephemeral player/tickets (the player ID at the time of the milestone)
     ,`p`.`client_ref`
     ,IFNULL(COUNT(DISTINCT `t`.`number`),0) AS `tickets`
     ,IFNULL(GROUP_CONCAT(DISTINCT `t`.`number` SEPARATOR ', '),'') AS `ticket_numbers`
      -- ephemeral contact details (the contact ID at the time of the milestone)
     ,`c`.`title`
     ,`c`.`name_first`
     ,`c`.`name_last`
     ,`c`.`email`
     ,`c`.`mobile`
     ,`c`.`telephone`
     ,`c`.`address_1`
     ,`c`.`address_2`
     ,`c`.`address_3`
     ,`c`.`town`
     ,`c`.`county`
     ,`c`.`postcode`
     ,`c`.`dob`
     ,`c`.`p0`
     ,`c`.`p1`
     ,`c`.`p2`
     ,`c`.`p3`
     ,`c`.`p4`
     ,`c`.`p5`
     ,`c`.`p6`
     ,`c`.`p7`
     ,`c`.`p8`
     ,`c`.`p9`
     ,`m`.`first_collected`
     ,`m`.`last_collected`
      -- more non-ephemeral things
     ,IFNULL(`player1`.`first_draw_close`,'') AS `first_draw`
     ,IFNULL(`s`.`death_reported`,'') AS `death_reported`
     ,`s`.`death_by_suicide`
      -- player/mandate properties are ephemeral
     ,IFNULL(`m`.`Status`,'') AS `mandate_status`
     ,IFNULL(`m`.`Freq`,'') AS `collection_frequency`
      -- view UpdatesLatest (legends.php) lines up with Updates column-wise hence we must reserve four columns here
     ,'' AS `unused_1`
     ,'' AS `unused_2`
     ,'' AS `unused_3`
     ,'' AS `unused_4`
    FROM `blotto_update` AS `u`
    JOIN `blotto_supporter` AS `s`
      ON `s`.`id`=`u`.`supporter_id`
    JOIN `blotto_player` AS `player1`
      ON `player1`.`supporter_id`=`s`.`id`
     AND `player1`.`client_ref`=`s`.`client_ref`
    JOIN `blotto_player` as `p`
      ON `p`.`id`=`u`.`player_id`
    LEFT JOIN (
      SELECT
        `mandate`.`ClientRef`
       ,`mandate`.`Status`
       ,`mandate`.`Freq`
       ,IFNULL(MIN(`DateDue`),'') AS `first_collected`
       ,IFNULL(MAX(`DateDue`),'') AS `last_collected`
      FROM `blotto_build_mandate` AS `mandate`
      LEFT JOIN `blotto_build_collection` AS `collection`
        ON `collection`.`ClientRef`=`mandate`.`ClientRef`
      GROUP BY `mandate`.`RefNo`
    )      AS `m`
           ON `m`.`ClientRef`=`p`.`client_ref`
    LEFT JOIN `{{BLOTTO_TICKET_DB}}`.`blotto_ticket` AS `t`
           ON `t`.`org_id`={{BLOTTO_ORG_ID}}
    -- EXT ticket supporters get milestones too because mandates are left joined
          AND `t`.`client_ref`=`p`.`client_ref`
    JOIN `blotto_contact` as `c`
      ON `c`.`id`=`u`.`contact_id`
    GROUP BY `u`.`id`
    ORDER BY `updated`,`client_ref_orig`,`client_ref`
  ;
  ALTER TABLE `Updates`
  ADD PRIMARY KEY (`updated`,`client_ref_orig`,`milestone`,`client_ref`)
  ;
  ALTER TABLE `Updates`
  ADD KEY `client_ref` (`client_ref`)
  ;
  ALTER TABLE `Updates`
  ADD KEY `milestone_date` (`milestone_date`)
  ;
  ALTER TABLE `Updates`
  ADD KEY `updater` (`updater`)
  ;
  -- Add supporter notional cancellation date "cancelled"
  UPDATE `Updates` AS `u`
  JOIN `blotto_player` AS `p`
    ON `p`.`supporter_id`=`u`.`supporter_id`
  JOIN `Cancellations` AS `c`
    ON `c`.`client_ref`=`p`.`client_ref`
  SET
    `u`.`cancelled`=`c`.`cancelled_date`
  ;
  UPDATE `Updates` SET
    `cancelled`=''
  WHERE `cancelled`='0000-00-00'
  ;
END$$


DELIMITER $$
DROP PROCEDURE IF EXISTS `updatesTableUpdatesLatest`$$
CREATE PROCEDURE `updatesTableUpdatesLatest` (
)
BEGIN
  -- output CRM contemporary snapshot
  -- this is the main for-all-time list of supporters
  DROP VIEW IF EXISTS `UpdatesLatest`
  ;
  DROP TABLE IF EXISTS `UpdatesLatest`
  ;
  CREATE TABLE `UpdatesLatest` AS
    SELECT
      MAX(`u`.`updated`) AS `updated`
     ,`u`.`supporter_id` AS `sort2_supporter_id`
     ,'' AS `unused_1`
     ,'' AS `unused_2`
     ,'' AS `unused_3`
      -- non-ephemeral things
     ,`u`.`signed` AS `sort1_signed`
     ,`u`.`created`
     ,`u`.`cancelled`
     ,`u`.`ccc`
     ,`u`.`canvas_ref`
     ,`u`.`client_ref_orig`
      -- latest player
     ,CAST(GROUP_CONCAT(`u`.`client_ref` ORDER BY `u`.`milestone_date` DESC LIMIT 1) AS char(64) character set ascii) AS `client_ref`
     ,CAST(GROUP_CONCAT(`u`.`tickets` ORDER BY `u`.`milestone_date` DESC LIMIT 1) AS int) AS `tickets`
     ,CAST(GROUP_CONCAT(`u`.`ticket_numbers` ORDER BY `u`.`milestone_date` DESC LIMIT 1) AS char(255) character set ascii) AS `ticket_numbers`
      -- bleeding edge contact details
     ,CAST(GROUP_CONCAT(`c`.`title` ORDER BY `c`.`created` DESC LIMIT 1) AS char(255) character set utf8) AS `title`
     ,CAST(GROUP_CONCAT(`c`.`name_first` ORDER BY `c`.`created` DESC LIMIT 1) AS char(255) character set utf8) AS `name_first`
     ,CAST(GROUP_CONCAT(`c`.`name_last` ORDER BY `c`.`created` DESC LIMIT 1) AS char(255) character set utf8) AS `name_last`
     ,CAST(GROUP_CONCAT(`c`.`email` ORDER BY `c`.`created` DESC LIMIT 1) AS char(254) character set ascii) AS `email`
     ,CAST(GROUP_CONCAT(`c`.`mobile` ORDER BY `c`.`created` DESC LIMIT 1) AS char(64) character set ascii) AS `mobile`
     ,CAST(GROUP_CONCAT(`c`.`telephone` ORDER BY `c`.`created` DESC LIMIT 1) AS char(255) character set ascii) AS `telephone`
     ,CAST(GROUP_CONCAT(`c`.`address_1` ORDER BY `c`.`created` DESC LIMIT 1) AS char(255) character set utf8) AS `address_1`
     ,CAST(GROUP_CONCAT(`c`.`address_2` ORDER BY `c`.`created` DESC LIMIT 1) AS char(255) character set utf8) AS `address_2`
     ,CAST(GROUP_CONCAT(`c`.`address_3` ORDER BY `c`.`created` DESC LIMIT 1) AS char(255) character set utf8) AS `address_3`
     ,CAST(GROUP_CONCAT(`c`.`town` ORDER BY `c`.`created` DESC LIMIT 1) AS char(255) character set utf8) AS `town`
     ,CAST(GROUP_CONCAT(`c`.`county` ORDER BY `c`.`created` DESC LIMIT 1) AS char(255) character set utf8) AS `county`
     ,CAST(GROUP_CONCAT(`c`.`postcode` ORDER BY `c`.`created` DESC LIMIT 1) AS char(64) character set ascii) AS `postcode`
     ,CAST(GROUP_CONCAT(`c`.`dob` ORDER BY `c`.`created` DESC LIMIT 1) AS date) AS `dob`
     ,CAST(GROUP_CONCAT(`c`.`p0` ORDER BY `c`.`created` DESC LIMIT 1) AS char(255) character set utf8) AS `p0`
     ,CAST(GROUP_CONCAT(`c`.`p1` ORDER BY `c`.`created` DESC LIMIT 1) AS char(255) character set utf8) AS `p1`
     ,CAST(GROUP_CONCAT(`c`.`p2` ORDER BY `c`.`created` DESC LIMIT 1) AS char(255) character set utf8) AS `p2`
     ,CAST(GROUP_CONCAT(`c`.`p3` ORDER BY `c`.`created` DESC LIMIT 1) AS char(255) character set utf8) AS `p3`
     ,CAST(GROUP_CONCAT(`c`.`p4` ORDER BY `c`.`created` DESC LIMIT 1) AS char(255) character set utf8) AS `p4`
     ,CAST(GROUP_CONCAT(`c`.`p5` ORDER BY `c`.`created` DESC LIMIT 1) AS char(255) character set utf8) AS `p5`
     ,CAST(GROUP_CONCAT(`c`.`p6` ORDER BY `c`.`created` DESC LIMIT 1) AS char(255) character set utf8) AS `p6`
     ,CAST(GROUP_CONCAT(`c`.`p7` ORDER BY `c`.`created` DESC LIMIT 1) AS char(255) character set utf8) AS `p7`
     ,CAST(GROUP_CONCAT(`c`.`p8` ORDER BY `c`.`created` DESC LIMIT 1) AS char(255) character set utf8) AS `p8`
     ,CAST(GROUP_CONCAT(`c`.`p9` ORDER BY `c`.`created` DESC LIMIT 1) AS char(255) character set utf8) AS `p9`
      -- non-ephemeral things
     ,`u`.`first_collected`
     ,`u`.`last_collected`
     ,`u`.`first_draw`
     ,`u`.`death_reported`
     ,`u`.`death_by_suicide`
      -- latest player
     ,CAST(GROUP_CONCAT(`u`.`mandate_status` ORDER BY `u`.`milestone_date` DESC LIMIT 1) AS char(64) character set ascii) AS `mandate_status`
     ,CAST(GROUP_CONCAT(`u`.`collection_frequency` ORDER BY `u`.`milestone_date` DESC LIMIT 1) AS char(64) character set ascii) AS `collection_frequency`
      -- bleeding edge account details
     ,`ac`.`spent`
     ,`ac`.`opening_balance`
     ,`ac`.`collected`
     ,`ac`.`balance`
    FROM `Updates` AS `u`
    JOIN (
      SELECT
        `ps`.`supporter_id`
       ,FORMAT(SUM(`ps`.`entries`)*{{BLOTTO_TICKET_PRICE}}/100,2) AS `spent`
       ,FORMAT(SUM(`ps`.`opening_balance`),2) AS `opening_balance`
       ,FORMAT(SUM(`ps`.`collected`),2) AS `collected`
       ,FORMAT(SUM(`ps`.`opening_balance`+`ps`.`collected`-(`ps`.`entries`*{{BLOTTO_TICKET_PRICE}}/100)),2) AS `balance`
      FROM (
        SELECT
          `p`.`supporter_id`
         ,`p`.`opening_balance`
         ,IFNULL(SUM(`c`.`PaidAmount`),0.00) AS `collected`
         ,IFNULL(`es`.`entries`,0.00) AS `entries`
        FROM `blotto_player` AS `p`
        LEFT JOIN `blotto_build_collection` AS `c`
               ON `c`.`ClientRef`=`p`.`client_ref`
        LEFT JOIN (
          SELECT
            `client_ref`
           ,COUNT(`id`) AS `entries`
          FROM `blotto_entry`
          GROUP BY `client_ref`
        )      AS `es`
               ON `es`.`client_ref`=`p`.`client_ref`
      GROUP BY `p`.`id` -- one row per player
      ) AS `ps`
      GROUP BY `ps`.`supporter_id` -- one row per supporter
    ) AS `ac`
      ON `ac`.`supporter_id`=`u`.`supporter_id`
    JOIN `blotto_contact` AS `c`
      ON `c`.`supporter_id`=`u`.`supporter_id`
    GROUP BY `u`.`supporter_id`
    ORDER BY `u`.`signed`,`u`.`supporter_id`
  ;
  ALTER TABLE `UpdatesLatest`
  ADD PRIMARY KEY (`sort1_signed`,`sort2_supporter_id`)
  ;
  ALTER TABLE `UpdatesLatest`
  ADD KEY `ccc` (`ccc`)
  ;
  ALTER TABLE `UpdatesLatest`
  ADD KEY `client_ref_orig` (`client_ref_orig`)
  ;
  ALTER TABLE `UpdatesLatest`
  ADD KEY `client_ref` (`client_ref`)
  ;
  ALTER TABLE `UpdatesLatest`
  ADD KEY `cancelled` (`cancelled`)
  ;
END$$


DELIMITER $$
DROP PROCEDURE IF EXISTS `winners`$$
CREATE PROCEDURE `winners` (
)
BEGIN
  DROP TABLE IF EXISTS `WinsAdmin`
  ;
  CREATE TABLE `WinsAdmin` AS
    SELECT
      `e`.`draw_closed`
     ,`w`.`amount` AS `winnings`
     ,`w`.`number` AS `ticket_number`
     ,'' AS `blank`
     ,`pz`.`name` AS `prize`
     ,`e`.`client_ref`
     ,`mandate`.`Sortcode`
     ,`mandate`.`Account`
     ,`s`.`created`
     ,`s`.`cancelled`
     ,`s`.`ccc`
     ,`s`.`canvas_agent_ref`
     ,`s`.`canvas_ref`
     ,`s`.`supporter_id`
     ,`s`.`title`
     ,`s`.`name_first`
     ,`s`.`name_last`
     ,`s`.`email`
     ,`s`.`mobile`
     ,`s`.`telephone`
     ,`s`.`address_1`
     ,`s`.`address_2`
     ,`s`.`address_3`
     ,`s`.`town`
     ,`s`.`county`
     ,`s`.`postcode`
     ,`s`.`latest_payment_collected`
     ,`s`.`active`
     ,`s`.`status`
     ,`s`.`fail_reason`
     ,`s`.`latest_mandate_frequency`
     ,`s`.`latest_mandate_amount`
     ,`w`.`entry_id`
     ,DATE_FORMAT(drawOnOrAfter(`e`.`draw_closed`),'%a %D %b %Y') AS `draw_date`
     ,IFNULL(`w`.`letter_batch_ref`,'') AS `letter_batch_ref`
     ,IFNULL(`w`.`letter_status`,'') AS `letter_status`
    FROM `blotto_winner` AS `w`
    JOIN `blotto_entry` AS `e`
      ON `e`.`id`=`w`.`entry_id`
    LEFT JOIN `blotto_player` AS `p`
      ON `p`.`client_ref`=`e`.`client_ref`
    LEFT JOIN (
      SELECT
        *
      FROM `Supporters`
      GROUP BY `supporter_id`
    ) AS `s`
      ON `s`.`supporter_id`=`p`.`supporter_id`
    LEFT JOIN (
      SELECT
        `supporter_id`
       ,MAX(`started`) AS `started`
        FROM `blotto_player`
        GROUP BY `supporter_id`
    ) AS `ps`
      ON `ps`.`supporter_id`=`s`.`supporter_id`
    LEFT JOIN `blotto_player` AS `pl`
      ON `pl`.`supporter_id`=`ps`.`supporter_id`
     AND `pl`.`started`=`ps`.`started`
    LEFT JOIN `blotto_prize` AS `pz`
           ON `pz`.`level`=`w`.`prize_level`
          AND `pz`.`starts`=`w`.`prize_starts`
    LEFT JOIN `blotto_build_mandate` AS `mandate`
           ON `mandate`.`ClientRef`=`pl`.`client_ref`
    ORDER BY `e`.`draw_closed`,`winnings`,`ticket_number`
  ;
  ALTER TABLE `WinsAdmin`
  ADD PRIMARY KEY (`draw_closed`,`winnings`,`ticket_number`)
  ;
  ALTER TABLE `WinsAdmin`
  ADD KEY `draw_closed` (`draw_closed`)
  ;
  ALTER TABLE `WinsAdmin`
  ADD KEY `winnings` (`winnings`)
  ;
  ALTER TABLE `WinsAdmin`
  ADD KEY `ticket_number` (`ticket_number`)
  ;
  ALTER TABLE `WinsAdmin`
  ADD KEY `client_ref` (`client_ref`)
  ;
  DROP TABLE IF EXISTS `Wins`
  ;
  CREATE TABLE `Wins` AS
    SELECT
      *
    FROM `WinsAdmin`
    ORDER BY `draw_closed`,`winnings`,`ticket_number`
  ;
  ALTER TABLE `Wins`
  ADD PRIMARY KEY (`draw_closed`,`winnings`,`ticket_number`)
  ;
  ALTER TABLE `Wins`
  ADD KEY `draw_closed` (`draw_closed`)
  ;
  ALTER TABLE `Wins`
  ADD KEY `winnings` (`winnings`)
  ;
  ALTER TABLE `Wins`
  ADD KEY `ticket_number` (`ticket_number`)
  ;
  ALTER TABLE `Wins`
  ADD KEY `client_ref` (`client_ref`)
  ;
  UPDATE `Wins`
  SET
    `Sortcode`=CONCAT('***',SUBSTR(`Sortcode`,-3))
   ,`Account`=CONCAT('***',SUBSTR(`Account`,-3))
  ;
  DROP VIEW IF EXISTS `WinsForWise`
  ;
  CREATE ALGORITHM=UNDEFINED SQL SECURITY DEFINER VIEW `WinsForWise` AS 
  SELECT
    `m`.`Name` AS `name`
   ,`w`.`email` AS `recipientEmail`
   ,'PERSON' AS `receiverType`
   ,'target' AS `amountCurrency`
   ,`w`.`winnings` AS `amount`
   ,'GBP' AS `sourceCurrency`
   ,'GBP' AS `targetCurrency`
   ,`w`.`Sortcode` AS `sortCode`
   ,`w`.`Account` AS `accountNumber`
   ,`w`.`draw_closed` AS `draw_closed` 
   ,GROUP_CONCAT(c.title, ' ',c.name_first,' ',c.name_last ORDER by c.id desc limit 1) as contact
  FROM `WinsAdmin` AS `w` 
  LEFT JOIN `blotto_build_mandate` AS `m` 
  ON `m`.`ClientRef`=`w`.`client_ref`
  LEFT JOIN `blotto_supporter` AS `s` ON `s`.`client_ref` = `m`.`ClientRef`
  LEFT JOIN `blotto_contact` AS `c` ON `c`.`supporter_id` = `s`.`id`
  GROUP BY `m`.`ClientRef`
  ORDER BY 
  `w`.`draw_closed` DESC,
  `w`.`name_last`
  ;
END$$

SELECT m.`MandateCreated`, m.`Name`, m.`Status`, m.`Updated`, m.`ClientRef`, m.`MandateId`
, GROUP_CONCAT(c.title, ' ',c.name_first,' ',c.name_last ORDER by c.id desc limit 1) as contact
, DATEDIFF(m.`Updated`, m.`MandateCreated`) as dd
FROM `paysuite_mandate` m
LEFT JOIN blotto_supporter s on s.client_ref = m.ClientRef
LEFT JOIN blotto_contact c on c.supporter_id = s.id
WHERE m.`MandateCreated` < '2025-12-01' AND m.`Status` = 'Active'
group by m.ClientRef
having dd > 14
ORDER BY `Updated` DESC
LIMIT 100


-- draft winnersThisWeek.
-- SELECT w.amount, e.draw_closed as dc, c.name_first, c.name_last
-- FROM blotto_winner as w
-- JOIN blotto_entry as e 
-- on w.entry_id = e.id
-- JOIN blotto_player as p
-- on p.client_ref = e.client_ref
-- JOIN blotto_contact as c
-- on c.supporter_id = p.supporter_id
-- WHERE e.draw_closed <= CURDATE()
-- AND e.draw_closed > DATE_SUB(CURDATE(), INTERVAL 7 DAY)

DELIMITER ;

