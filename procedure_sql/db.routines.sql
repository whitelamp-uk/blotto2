

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
     ,`sdtk`.`ticket_numbers` AS `superdraw_tickets`
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
     AND `tk`.`mandate_provider`=`m`.`Provider`
     AND `tk`.`org_id`={{BLOTTO_ORG_ID}}
    LEFT JOIN (
      SELECT
        `client_ref`
       ,GROUP_CONCAT(
          `ticket_number`
          ORDER BY `superdraw_db`,`ticket_number`
          SEPARATOR ', '
        ) AS `ticket_numbers`
      FROM `blotto_super_ticket`
      GROUP BY `client_ref`
    )      AS `sdtk`
           ON `sdtk`.`client_ref`=`p`.`client_ref`
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
  SET @first = (
    SELECT
      MIN(`draw_closed`)
    FROM `blotto_entry`
    WHERE `draw_closed`>=starts
      AND `draw_closed`<=ends
  )
  ;
  SET @last = (
    SELECT
      MAX(`draw_closed`)
    FROM `blotto_entry`
    WHERE `draw_closed`>=starts
      AND `draw_closed`<=ends
  )
  ;
  SET @weeks = (
    SELECT
      COUNT(DISTINCT `draw_closed`)
    FROM `blotto_entry`
    WHERE `draw_closed`>=starts
      AND `draw_closed`<=ends
  )
  ;
  SET @collections = (
    SELECT
      SUM(`PaidAmount`)
    FROM `blotto_build_collection`
    WHERE `DateDue`>=starts
      AND `DateDue`<=ends
  )
  ;
  SET @starting = (
    SELECT
      SUM(`p`.`opening_balance`)
    FROM `blotto_player` AS `p`
    WHERE DATE(`p`.`created`)<=ends
  )
  ;
  SET @allCollected = (
    SELECT
      SUM(`PaidAmount`)
    FROM `blotto_build_collection`
    WHERE `DateDue`<=ends
  )
  ;
  SET @fees = (
    SELECT
      ROUND(SUM(`amount`)/100,2)
    FROM `blotto_super_entry`
    WHERE `draw_closed`>=starts
      AND `draw_closed`<=ends
                      )
  ;
  SET @fees = IFNULL(@fees,0)
  ;
  SET @plays = (
    SELECT
      COUNT(`id`)
    FROM `blotto_entry`
    WHERE `draw_closed`>=starts
      AND `draw_closed`<=ends
                      )
  ;
  SET @allPlays = (
    SELECT
      COUNT(`id`)
    FROM `blotto_entry`
    WHERE `draw_closed`<=ends
                      )
  ;
  SET @perplay      = {{BLOTTO_TICKET_PRICE}}
  ;
  SET @played       = @perplay/100 * @plays
  ;
  SET @allPlayed    = @perplay/100 * @allPlays
  ;
  SET @balOpen      = ( @starting + @allCollected - @collections) - ( @allPlayed - @played )
  ;
  SET @balClose     = @allCollected - @AllPlayed
  ;
  SET @payout       = (
    SELECT
      SUM(`w`.`amount`)
    FROM `blotto_winner` AS `w`
    JOIN `blotto_entry` AS `e`
      ON `e`.`id`=`w`.`entry_id`
    WHERE `e`.`draw_closed`>=starts
      AND `e`.`draw_closed`<=ends
                      )
  ;
  SET @payout       = IFNULL(@payout,0)
  ;
  SET @nett         = @played - ( @payout + @fees )
  ;
  SET @reconcile    = ( @balOpen + @collections ) - ( @played + @balClose )
  ;
  INSERT INTO `blotto_calculation` ( `item`, `units`, `amount`, `notes` )  VALUES
    ( 'head_reconcile',     '',     '',                              'Reconciliation'                 ),
    ( 'draw_first',         '',     DATE_FORMAT(@first,'%Y %b %d'),  'first draw in this period'      ),
    ( 'draw_last',          '',     DATE_FORMAT(@last,'%Y %b %d'),   'last draw in this period'       ),
    ( 'draws',              '',     dp(@weeks,0),                    'draws in this period'           ),
    ( 'amount_per_play',    'GBP',  dp(@perplay/100,2),              'charge per play'                ),
    ( 'plays',              '',     @plays,                          'total plays in this period'     ),
    ( 'balances_opening',   'GBP',  dp(@balOpen,2),                  '+ player opening balances'      ),
    ( 'collected',          'GBP',  dp(@collections,2),              '+ collected this period'        ),
    ( 'play_value',         'GBP',  dp(0-@played,2),                 '− played this period'           ),
    ( 'balances_closing',   'GBP',  dp(0-@balClose,2),               '− player closing balances'      ),
    ( 'reconciliation',     'GBP',  dp(@reconcile,2),                '≡ to be reconciled'             ),
    ( 'head_return',        '',     '',                              'Return'                         ),
    ( 'revenue',            'GBP',  dp(@played,2),                   '+ revenue from plays'           ),
    ( 'winnings',           'GBP',  dp(0-@payout,2),                 '− paid out (except superdraws)' ),
    ( 'fees',               'GBP',  dp(0-@fees,2),                   '− superdraw fees'               ),
    ( 'nett',               'GBP',  dp(@nett,2),                     '≡ return generated'             )
  ;
  SELECT
    *
  FROM `blotto_calculation`
  ;
  DROP TABLE `blotto_calculation`
  ;
END $$


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
       ,cancelDate(`s`.`created`,'')
       ,IF(
          `c`.`Payments_Collected` IS NULL
          -- If no collections, use mandate start date
         ,cancelDate(`m`.`StartDate`,`m`.`Freq`)
         ,cancelDate(`c`.`Last_Payment`,`m`.`Freq`)
        )
      ) AS `cancelled_date`

-- This bit is soon to be sacked
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
      GROUP BY `Provider`,`RefNo`
    )      AS `c`
           ON `c`.`Provider`=`m`.`Provider`
          AND `c`.`RefNo`=`m`.`RefNo`
    LEFT JOIN `crucible_ticket_zaffo`.`blotto_ticket` AS `t`
           ON `t`.`mandate_provider`=`m`.`Provider`
          AND `t`.`client_ref`=`m`.`ClientRef`
          AND `t`.`org_id`=2
    -- One-off payments are not applicable
    WHERE `m`.`Freq`!='Single'
    GROUP BY `client_ref`,`ticket_number`
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
      `ch`.`changed_date`
     ,IF(`ch`.`type`='DEC',`c`.`cancelled_date_legacy`,`ch`.`changed_date`) AS `changed_date_legacy`
     ,`ch`.`ccc`
     ,`ch`.`canvas_ref`
     ,`ch`.`chance_number`
     ,CONCAT(`ch`.`canvas_ref`,'-',`ch`.`chance_number`) AS `chance_ref`
     ,`ch`.`client_ref_original`
     ,`ch`.`agent_ref`
     ,`ch`.`type`
     ,`ch`.`is_termination`
     ,`ch`.`reinstatement_for`
     ,`ch`.`amount_paid_before_this_date`
     ,`ch`.`supporter_signed`
     ,`ch`.`supporter_approved`
     ,`ch`.`supporter_created`
     ,IFNULL(`ch`.`supporter_first_paid`,'') AS `supporter_first_paid`
    FROM `blotto_change` AS `ch`
    LEFT JOIN (
      SELECT
        `client_ref`
       ,`cancelled_date_legacy`
      FROM `Cancellations`
      GROUP BY `client_ref`
    ) AS `c`
      ON `ch`.`type`='DEC'
     AND `c`.`client_ref`=`ch`.`client_ref_original`
    ORDER BY `ch`.`changed_date`,`ch`.`ccc`,`ch`.`canvas_ref`,`ch`.`chance_number`
  ;
  ALTER TABLE `Changes`
  ADD PRIMARY KEY (`changed_date`,`ccc`,`canvas_ref`,`chance_number`)
  ;
  ALTER TABLE `Changes`
  ADD KEY `changed_date` (`changed_date`)
  ;
  ALTER TABLE `Changes`
  ADD KEY `changed_date_legacy` (`changed_date_legacy`)
  ;
  ALTER TABLE `Changes`
  ADD KEY `ccc` (`ccc`)
  ;
  ALTER TABLE `Changes`
  ADD KEY `canvas_ref` (`canvas_ref`)
  ;
  ALTER TABLE `Changes`
  ADD KEY `agent_ref` (`agent_ref`)
  ;
  ALTER TABLE `Changes`
  ADD KEY `client_ref_original` (`client_ref_original`)
  ;
END $$


DELIMITER $$
DROP PROCEDURE IF EXISTS `changesGenerate`$$
CREATE PROCEDURE `changesGenerate` (
)
BEGIN
  DROP TABLE IF EXISTS `tmp_changes_player`
  ;
  CREATE TABLE `tmp_changes_player` AS
    SELECT
      `p`.`id`
     ,`p`.`supporter_id`
     ,`p`.`started`
     ,`p`.`client_ref`
     ,`p`.`first_draw_close`
     ,`tk`.`chances`
     ,`s`.`canvas_code` AS `ccc`
     ,`s`.`canvas_agent_ref` AS `agent_ref`
     ,`s`.`canvas_ref`
     ,`p1`.`client_ref` AS `client_ref_original`
     ,`s`.`signed` AS `supporter_signed`
     ,`s`.`approved` AS `supporter_approved`
     ,`s`.`created` AS `supporter_created`
     ,`p1`.`FirstPaid` AS `supporter_first_paid`
     ,`p1`.`chances` AS `starting_chances`
    FROM `blotto_player` AS `p`
    JOIN `blotto_supporter` AS `s`
      ON `s`.`id`=`p`.`supporter_id`
    JOIN (
      SELECT
        `pfirst`.`client_ref`
       ,`c`.`FirstPaid`
       ,`tkt`.`chances`
      FROM `blotto_player` AS `pfirst`
      JOIN (
        SELECT
          `client_ref`
         ,COUNT(`number`) AS `chances`
        FROM `{{BLOTTO_TICKET_DB}}`.`blotto_ticket`
        WHERE `org_id`={{BLOTTO_ORG_ID}}
        GROUP BY `client_ref`
      ) AS `tkt`
        ON `tkt`.`client_ref`=`pfirst`.`client_ref`
      LEFT JOIN (
        SELECT
            `ClientRef`
         ,MIN(`DateDue`) AS `FirstPaid`
        FROM `blotto_build_collection`
        WHERE 1
        GROUP BY `ClientRef`
      ) AS `c`
        ON `c`.`ClientRef`=`pfirst`.`client_ref`
    ) AS `p1`
      ON `p1`.`client_ref`=`s`.`client_ref`
    JOIN (
      SELECT
        `client_ref`
       ,COUNT(`number`) AS `chances`
      FROM `{{BLOTTO_TICKET_DB}}`.`blotto_ticket`
      WHERE `org_id`={{BLOTTO_ORG_ID}}
      GROUP BY `client_ref`
    )      AS `tk`
           ON `tk`.`client_ref`=`p`.`client_ref`

/*
TODO: Do this a proper way - see scripts/entries.php which has the same problem
*/
-- HORRIBLE HACK
    WHERE `p`.`client_ref` NOT LIKE 'STRP%'

    ORDER BY `p`.`started`,`p`.`id`
  ;
  DROP TABLE IF EXISTS `tmp_changes_collection`
  ;
  CREATE TABLE `tmp_changes_collection` AS
    SELECT
      `p`.`id` AS `player_id`
     ,`c`.*
    FROM `blotto_build_collection` AS `c`
    JOIN `tmp_changes_player` AS `p`
      ON `p`.`client_ref`=`c`.`ClientRef`
    WHERE `c`.`DateDue`<=DATE_ADD(IFNULL(`p`.`supporter_first_paid`,`p`.`supporter_created`),INTERVAL {{BLOTTO_CC_NOTIFY}})
  ;
  DROP TABLE IF EXISTS `tmp_changes_by_player`
  ;
  CREATE TABLE `tmp_changes_by_player` AS
    SELECT
      `p`.*
     ,IFNULL(MIN(`c`.`DateDue`),'') AS `first_payment`
     ,IFNULL(MAX(`c`.`DateDue`),'') AS `last_payment`
     ,IFNULL(SUM(`c`.`PaidAmount`),0) AS `amount_paid`
    FROM `tmp_changes_player` AS `p`
    LEFT JOIN `blotto_build_collection` AS `c`
      ON `c`.`ClientRef`=`p`.`client_ref`
    GROUP BY `p`.`id`
    ORDER BY `p`.`supporter_id`,`p`.`started`
  ;
  DROP TABLE IF EXISTS `tmp_changes_by_supporter`
  ;
  CREATE TABLE `tmp_changes_by_supporter` AS
  SELECT
    `id`
   ,`supporter_id`
   ,`first_draw_close`
   ,`ccc`
   ,`agent_ref`
   ,`canvas_ref`
   ,`client_ref_original`
   ,`supporter_signed`
   ,`supporter_approved`
   ,`supporter_created`
   ,`supporter_first_paid`
   ,`starting_chances`
   ,GROUP_CONCAT(
      CONCAT_WS(
        '::'
       ,`started`
       ,`client_ref`
       ,`chances`
       ,`first_payment`
       ,`last_payment`
       ,`amount_paid`
      )
      ORDER BY `started`
      SEPARATOR ';;'
    ) AS `players`
    FROM `tmp_changes_by_player`
    GROUP BY `supporter_id`
  ;
  DROP TABLE IF EXISTS `tmp_changes_termination`
  ;
  CREATE TABLE `tmp_changes_termination` AS
    SELECT
      `p`.*
     ,`c`.`Cancelled_Date`
     ,`c`.`Amount_Collected_(all_tickets)` AS `amount_paid`
    FROM `Cancellations` AS `c`
    JOIN `tmp_changes_player` AS `p`
      ON `p`.`client_ref`=`c`.`client_ref`
    WHERE `c`.`Cancelled_Date`<=DATE_ADD(IFNULL(`p`.`supporter_first_paid`,`p`.`supporter_created`),INTERVAL {{BLOTTO_CC_NOTIFY}})
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
    JOIN `blotto_supporter` AS `s`
      ON `s`.`id`=`p`.`supporter_id`
    LEFT JOIN (
      SELECT
        `client_ref`
       ,COUNT(DISTINCT `draw_closed`) AS `plays`
      FROM `blotto_entry`
      GROUP BY `client_ref`
    ) AS `e`
      ON `e`.`client_ref`=`p`.`client_ref`
    LEFT JOIN `Cancellations` AS `cancelled`
           ON `cancelled`.`client_ref`=`s`.`client_ref`
    -- The player is deemed to be active
    WHERE `cancelled`.`client_ref` IS NULL
    -- Player is ready to go
      AND `p`.`first_draw_close` IS NOT NULL
    -- Player is ready to go right now
      AND `p`.`first_draw_close`<=futureCloseDate
    -- Player has enough balance to play one more time
      AND
              `p`.`opening_balance`
            + IFNULL(`c`.`AmountCollected`,0)
            - (IFNULL(`e`.`plays`,0)+1)*`p`.`chances`*{{BLOTTO_TICKET_PRICE}}/100
          >= 0
    ORDER BY `tk`.`number`
  ;
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
  CREATE TABLE IF NOT EXISTS `{{BLOTTO_TICKET_DB}}`.`Insurance_Export` (
    `entry_urn` bigint(21) unsigned NOT NULL,
    `draw_close_date` date DEFAULT NULL,
    `player_urn` varchar(69) CHARACTER SET ascii DEFAULT NULL,
    `ticket_number` varchar(18) CHARACTER SET ascii DEFAULT NULL,
    PRIMARY KEY (`entry_urn`),
    KEY `draw_close_date` (`draw_close_date`),
    KEY `player_urn` (`player_urn`),
    KEY `ticket_number` (`ticket_number`)
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8
  ;
  DELETE FROM `{{BLOTTO_TICKET_DB}}`.`Insurance_Export`
  WHERE `player_urn` LIKE CONCAT(UPPER('{{BLOTTO_ORG_USER}}'),'-%')
  ;
  SET @latest = ( SELECT MAX(`draw_close_date`) FROM `Insurance` )
  ;
  INSERT INTO `{{BLOTTO_TICKET_DB}}`.`Insurance_Export`
    SELECT
      *
    FROM `Insurance`
    WHERE `draw_close_date`=@latest
  ;
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
DROP PROCEDURE IF EXISTS `mandates`$$
-- Functionality moved to rsm-api


DELIMITER $$
DROP PROCEDURE IF EXISTS `supporters`$$
CREATE PROCEDURE `supporters` (
)
BEGIN
  SET @CostPerPlay = {{BLOTTO_TICKET_PRICE}};
  -- Supporter data
  DROP TABLE IF EXISTS `tmp_supporter`
  ;
  CREATE TABLE `tmp_supporter` AS
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

-- Weening us off mandate status
       ,IF(`m`.`Status`='','',IF(`m`.`Status` IN ('DELETED','CANCELLED','FAILED'),'DEAD','ACTIVE')) AS `Active`

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
        WHERE 1
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
  ALTER TABLE `tmp_supporter`
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
     ,IFNULL(`d`.`balance`,0) AS `balance`
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

-- Weening us off mandate status
       ,IF(`m`.`Status`='','',IF(`m`.`Status` IN ('DELETED','CANCELLED','FAILED'),'DEAD','ACTIVE')) AS `Active`

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
        WHERE 1
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
    JOIN `tmp_supporter` AS `s`
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
  -- Updates triggered by first draw entry creation (per SUPPORTER)
  DROP TABLE IF EXISTS `tmp_updates_first_collect`
  ;
  CREATE TABLE `tmp_updates_first_collect` AS
    SELECT
      `f`.`first_collection` AS `updated`
     ,`s`.`id` AS `supporter_id`
     ,'SYSTEM' AS `updater`
     ,'first_collection' AS `milestone`
     ,`s`.`signed`
     ,`s`.`created`
     ,IF(`cl`.`cancelled_date`<=MIN(`u`.`first_draw`),`cl`.`cancelled_date`,'') AS `cancelled`
     ,`s`.`canvas_code`
     ,`s`.`canvas_ref`
     ,`s`.`client_ref` AS `client_ref_orig`
     ,`u`.`client_ref`
     ,`u`.`chances` AS `tickets`
     ,GROUP_CONCAT(DISTINCT `pl`.`number` SEPARATOR ', ') AS `ticket_numbers`
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
     ,IFNULL(`c`.`dob`,'') AS `dob`
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
     ,`f`.`first_collection` AS `first_collected`
     ,IFNULL(`l`.`last_collection`,'') AS `last_collected`
     ,IFNULL(MIN(`u`.`first_draw`),'') AS `first_draw`
     ,IFNULL(`u`.`Status`,'') AS `mandate_status`
    FROM `blotto_supporter` AS `s`
    JOIN (
      SELECT
        `p`.`id`
       ,`p`.`started`
       ,`p`.`supporter_id`
       ,`p`.`chances`
       ,`m`.`Provider`
       ,`m`.`RefNo`
       ,`e`.`client_ref`
       ,`e`.`first_draw`
       ,`m`.`Freq`
       ,`m`.`Status`
       ,`m`.`Updated`
       ,MAX(DATE(`cs`.`created`)) AS `contact_date`
      FROM `blotto_player` AS `p`
      JOIN `blotto_build_mandate` AS `m`
        ON `m`.`ClientRef`=`p`.`client_ref`
      JOIN (
        SELECT
          `client_ref`
         ,MIN(`draw_closed`) AS `first_draw`
        FROM `blotto_entry`
        GROUP BY `client_ref`
      ) AS `e`
        ON `e`.`client_ref`=`m`.`ClientRef`
      LEFT JOIN `blotto_contact` AS `cs`
             ON `cs`.`supporter_id`=`p`.`supporter_id`
            AND DATE(`cs`.`created`)<=dateSilly2Sensible(`m`.`Updated`)
      GROUP BY `m`.`Provider`,`m`.`RefNo`
    ) AS `u`
      ON `u`.`supporter_id`=`s`.`id`
    JOIN (
      SELECT
        `ClientRef`
       ,MIN(`DateDue`) AS `first_collection`
      FROM `blotto_build_collection`
      WHERE 1
      GROUP BY `Provider`,`RefNo`
    ) AS `f`
      ON `f`.`ClientRef`=`s`.`client_ref`
    JOIN (
      SELECT
        `supporter_id`
       ,MAX(`started`) AS `started`
      FROM `blotto_player`
      GROUP BY `supporter_id`
    ) AS `plast`
      ON `plast`.`supporter_id`
    JOIN `blotto_player` as `pcurr`
      ON `pcurr`.`supporter_id`=`s`.`id`
     AND `pcurr`.`started`=`plast`.`started`
    LEFT JOIN `blotto_contact` AS `c`
           ON `c`.`supporter_id`=`s`.`id`
          AND ( `u`.`contact_date` IS NULL OR DATE(`c`.`created`)=`u`.`contact_date` )
    LEFT JOIN (
      SELECT
        `lc`.`client_ref`
       ,MAX(`lc`.`DateDue`) AS `last_collection`
      FROM (
        SELECT
          `lcc`.`Provider`
         ,`lcc`.`RefNo`
         ,`lcp`.`client_ref`
         ,`lcc`.`DateDue`
        FROM `blotto_player` AS `lcp`
        JOIN `blotto_build_collection` AS `lcc`
          ON `lcc`.`ClientRef`=`lcp`.`client_ref`
         AND `lcc`.`DateDue`<=`lcp`.`started`
      ) AS `lc`
      GROUP BY `lc`.`Provider`,`lc`.`RefNo`
    ) AS `l`
      ON `l`.`client_ref`=`pcurr`.`client_ref`
    LEFT JOIN `{{BLOTTO_TICKET_DB}}`.`blotto_ticket` AS `pl`
           ON `pl`.`org_id`={{BLOTTO_ORG_ID}}
          AND `pl`.`client_ref`=`u`.`client_ref`
    LEFT JOIN `Cancellations` AS `cl`
           ON `cl`.`client_ref`=`s`.`client_ref`
    GROUP BY `s`.`id`
    -- Draw entries might be in the future (?) but updates definitely not
    HAVING `updated`<CURDATE()
    ORDER BY `updated`,`client_ref_orig`,`client_ref`
  ;
  -- Updates triggered by mandate replacement => player creation (per PLAYER)
  DROP TABLE IF EXISTS `tmp_updates_player`
  ;
  CREATE TABLE `tmp_updates_player` AS
    SELECT
      `u`.`player_started` AS `updated`
     ,`s`.`id` AS `supporter_id`
     ,'SYSTEM' AS `updater`
     ,'bacs_change' AS `milestone`
     ,`s`.`signed`
     ,`s`.`created`
     ,IF(`cl`.`cancelled_date`<=`u`.`player_started`,`cl`.`cancelled_date`,'') AS `cancelled`
     ,`s`.`canvas_code`
     ,`s`.`canvas_ref`
     ,`s`.`client_ref` AS `client_ref_orig`
     ,`u`.`client_ref`
     ,`u`.`chances` AS `tickets`
     ,GROUP_CONCAT(DISTINCT `pl`.`number` SEPARATOR ', ') AS `ticket_numbers`
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
     ,IFNULL(`c`.`dob`,'') AS `dob`
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
     ,IFNULL(`f`.`first_collection`,'') AS `first_collected`
     ,IFNULL(`l`.`last_collection`,'') AS `last_collected`
     ,IFNULL(MIN(`u`.`first_draw`),'') AS `first_draw`
     ,IFNULL(`u`.`Status`,'') AS `mandate_status`
    FROM `blotto_supporter` AS `s`
    JOIN (
        SELECT
          `supporter_id`
         ,MIN(`started`) AS `started`
        FROM `blotto_player`
        GROUP BY `supporter_id`
    ) AS `fp`
      ON `fp`.`supporter_id`=`s`.`id`
    JOIN (
      SELECT
        `p`.`id`
       ,`p`.`started` AS `player_started`
       ,`p`.`chances`
       ,`m`.`created`
       ,`p`.`supporter_id`
       ,`m`.`Provider`
       ,`m`.`RefNo`
       ,`e`.`client_ref`
       ,`e`.`first_draw`
       ,`m`.`Freq`
       ,`m`.`Status`
       ,`m`.`Updated`
       ,MAX(DATE(`cs`.`created`)) AS `contact_date`
      FROM `blotto_player` AS `p`
      JOIN `blotto_build_mandate` AS `m`
        ON `m`.`ClientRef`=`p`.`client_ref`
      JOIN (
        SELECT
          `client_ref`
         ,MIN(`draw_closed`) AS `first_draw`
        FROM `blotto_entry`
        GROUP BY `client_ref`
      ) AS `e`
        ON `e`.`client_ref`=`m`.`ClientRef`
      LEFT JOIN `blotto_contact` AS `cs`
             ON `cs`.`supporter_id`=`p`.`supporter_id`
            AND DATE(`cs`.`created`)<=dateSilly2Sensible(`m`.`Updated`)
      GROUP BY `m`.`Provider`,`m`.`RefNo`
    ) AS `u`
      ON `u`.`supporter_id`=`s`.`id`
     -- first contact is not an update
     AND `u`.`player_started`!=`fp`.`started`
    LEFT JOIN `blotto_contact` AS `c`
           ON `c`.`supporter_id`=`s`.`id`
          AND ( `u`.`contact_date` IS NULL OR DATE(`c`.`created`)=`u`.`contact_date` )
    LEFT JOIN (
      SELECT
        `ClientRef`
       ,MIN(`DateDue`) AS `first_collection`
      FROM `blotto_build_collection`
      WHERE 1
      GROUP BY `Provider`,`RefNo`
    ) AS `f`
      ON `f`.`ClientRef`=`s`.`client_ref`
    LEFT JOIN (
      SELECT
        `lc`.`client_ref`
       ,MAX(`lc`.`DateDue`) AS `last_collection`
      FROM (
        SELECT
          `lcp`.`client_ref`
         ,`lcc`.`DateDue`
        FROM `blotto_player` AS `lcp`
        JOIN `blotto_build_collection` AS `lcc`
          ON `lcc`.`ClientRef`=`lcp`.`client_ref`
         AND `lcc`.`DateDue`<=`lcp`.`started`
      ) AS `lc`
      GROUP BY `lc`.`client_ref`
    ) AS `l`
      ON `l`.`client_ref`=`u`.`client_ref`
    LEFT JOIN `{{BLOTTO_TICKET_DB}}`.`blotto_ticket` AS `pl`
           ON `pl`.`org_id`={{BLOTTO_ORG_ID}}
          AND `pl`.`client_ref`=`l`.`client_ref`
    LEFT JOIN `Cancellations` AS `cl`
           ON `cl`.`client_ref`=`s`.`client_ref`
    GROUP BY `u`.`id`
    ORDER BY `updated`,`client_ref_orig`,`client_ref`
  ;
  -- Updates triggered by details edit => contact creation (per CONTACT)
  DROP TABLE IF EXISTS `tmp_updates_contact`
  ;
  CREATE TABLE `tmp_updates_contact` AS
    SELECT
      DATE(`c`.`created`) AS `updated`
     ,`s`.`id` AS `supporter_id`
     ,`c`.`updater`
     ,'contact_change' AS `milestone`
     ,`s`.`signed`
     ,`s`.`created`
     ,IF(`cl`.`cancelled_date`<=DATE(`c`.`created`),`cl`.`cancelled_date`,'') AS `cancelled`
     ,`s`.`canvas_code`
     ,`s`.`canvas_ref`
     ,`s`.`client_ref` AS `client_ref_orig`
     ,`p`.`client_ref`
     ,`p`.`chances` AS `tickets`
     ,GROUP_CONCAT(DISTINCT `tk`.`number` SEPARATOR ', ') AS `ticket_numbers`
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
     ,IFNULL(`c`.`dob`,'') AS `dob`
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
     ,IFNULL(`f`.`first_collection`,'') AS `first_collected`
     ,IFNULL(MAX(`cn`.`DateDue`),'') AS `last_collected`
     ,IFNULL(`e`.`first_draw`,'') AS `first_draw`
     ,IFNULL(`p`.`Status`,'') AS `mandate_status`
    FROM `blotto_supporter` AS `s`
    JOIN (
        SELECT
          `supporter_id`
         ,MIN(`created`) AS `created`
        FROM `blotto_contact`
        GROUP BY `supporter_id`
    ) AS `fc`
      ON `fc`.`supporter_id`=`s`.`id`
    JOIN `blotto_contact` AS `c`
      ON `c`.`supporter_id`=`s`.`id`
     -- first contact is not an update
     AND `c`.`created`!=`fc`.`created`
    JOIN (
      SELECT
        `cs`.`id`
       ,MAX(`ps`.`started`) AS `player_started`
      FROM `blotto_contact` AS `cs`
      JOIN `blotto_player` AS `ps`
        ON `ps`.`supporter_id`=`cs`.`supporter_id`
       AND `ps`.`created`<`cs`.`created`
      GROUP BY `cs`.`id`
    ) AS `cp`
      ON `cp`.`id`=`c`.`id`
    JOIN (
      SELECT
        `pm`.`id`
       ,`pm`.`started`
       ,`pm`.`supporter_id`
       ,`pm`.`chances`
       ,`m`.`Provider`
       ,`m`.`RefNo`
       ,`pm`.`client_ref`
       ,`m`.`Freq`
       ,`m`.`Status`
       ,`m`.`Updated`
      FROM `blotto_player` AS `pm`
      JOIN `blotto_build_mandate` AS `m`
        ON `m`.`ClientRef`=`pm`.`client_ref`
      GROUP BY `pm`.`id`
    ) AS `p`
      ON `p`.`supporter_id`=`s`.`id`
     AND `p`.`started`=`cp`.`player_started`
    LEFT JOIN (
      SELECT
        `ClientRef`
       ,MIN(`DateDue`) AS `first_collection`
      FROM `blotto_build_collection`
      WHERE 1
      GROUP BY `Provider`,`RefNo`
    ) AS `f`
      ON `f`.`ClientRef`=`s`.`client_ref`
    LEFT JOIN `blotto_build_collection` AS `cn`
           ON `cn`.`ClientRef`=`p`.`client_ref`
    LEFT JOIN `{{BLOTTO_TICKET_DB}}`.`blotto_ticket` AS `tk`
           ON `tk`.`org_id`={{BLOTTO_ORG_ID}}
          AND `tk`.`mandate_provider`=`p`.`Provider`
          AND `tk`.`client_ref`=`p`.`client_ref`
    LEFT JOIN `Cancellations` AS `cl`
           ON `cl`.`client_ref`=`s`.`client_ref`
    LEFT JOIN (
      SELECT
        `ep`.`supporter_id`
       ,MIN(`ee`.`draw_closed`) AS `first_draw`
      FROM `blotto_supporter` AS `es`
      JOIN `blotto_player` AS `ep`
        ON `ep`.`supporter_id`=`es`.`id`
      JOIN `blotto_entry` AS `ee`
        ON `ee`.`client_ref`=`ep`.`client_ref`
      GROUP BY `es`.`id`
         ) AS `e`
           ON `e`.`supporter_id`=`s`.`id`
    WHERE `cn`.`DateDue` IS NULL OR `cn`.`DateDue`<=DATE(`c`.`created`)
    GROUP BY `c`.`id`
    ORDER BY `updated`,`client_ref_orig`,`client_ref`
  ;
  -- Updates triggered by cancellation (per SUPPORTER)
  DROP TABLE IF EXISTS `tmp_updates_cancelled`
  ;
  CREATE TABLE `tmp_updates_cancelled` AS
    SELECT
      `cl`.`cancelled_date` AS `updated`
     ,`s`.`id` AS `supporter_id`
     ,'SYSTEM' AS `updater`
     ,'cancellation' AS `milestone`
     ,`s`.`signed`
     ,`s`.`created`
     ,`cl`.`cancelled_date` AS `cancelled`
     ,`s`.`canvas_code`
     ,`s`.`canvas_ref`
     ,`s`.`client_ref` AS `client_ref_orig`
     ,`u`.`client_ref`
     ,`u`.`chances` AS `tickets`
     ,GROUP_CONCAT(DISTINCT `tk`.`number` SEPARATOR ', ') AS `ticket_numbers`
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
     ,IFNULL(`c`.`dob`,'') AS `dob`
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
     ,IFNULL(`f`.`first_collection`,'') AS `first_collected`
     ,IFNULL(`l`.`last_collection`,'') AS `last_collected`
     ,IFNULL(MIN(`u`.`first_draw`),'') AS `first_draw`
     ,IFNULL(`u`.`Status`,'') AS `mandate_status`
    FROM `blotto_supporter` AS `s`
    JOIN (
      SELECT
        `p`.`id`
       ,`p`.`started`
       ,`p`.`supporter_id`
       ,`p`.`chances`
       ,`m`.`Provider`
       ,`m`.`RefNo`
       ,`e`.`client_ref`
       ,`e`.`first_draw`
       ,`m`.`Freq`
       ,`m`.`Status`
       ,`m`.`Updated`
       ,MAX(DATE(`cs`.`created`)) AS `contact_date`
      FROM `blotto_player` AS `p`
      JOIN `blotto_build_mandate` AS `m`
        ON `m`.`ClientRef`=`p`.`client_ref`
      JOIN (
        SELECT
          `client_ref`
         ,MIN(`draw_closed`) AS `first_draw`
        FROM `blotto_entry`
        GROUP BY `client_ref`
      ) AS `e`
        ON `e`.`client_ref`=`m`.`ClientRef`
      LEFT JOIN `blotto_contact` AS `cs`
             ON `cs`.`supporter_id`=`p`.`supporter_id`
            AND DATE(`cs`.`created`)<=dateSilly2Sensible(`m`.`Updated`)
      GROUP BY `m`.`Provider`,`m`.`RefNo`
    ) AS `u`
      ON `u`.`supporter_id`=`s`.`id`
    LEFT JOIN `blotto_contact` AS `c`
           ON `c`.`supporter_id`=`s`.`id`
          AND ( `u`.`contact_date` IS NULL OR DATE(`c`.`created`)=`u`.`contact_date` )
    LEFT JOIN (
      SELECT
        `ClientRef`
       ,MIN(`DateDue`) AS `first_collection`
      FROM `blotto_build_collection`
      WHERE 1
      GROUP BY `Provider`,`RefNo`
    ) AS `f`
      ON `f`.`ClientRef`=`s`.`client_ref`
    JOIN (
      SELECT
        `supporter_id`
       ,MAX(`started`) AS `started`
      FROM `blotto_player`
      GROUP BY `supporter_id`
    ) AS `plast`
      ON `plast`.`supporter_id`
    JOIN `blotto_player` as `pcurr`
      ON `pcurr`.`supporter_id`=`s`.`id`
     AND `pcurr`.`started`=`plast`.`started`
    LEFT JOIN (
      SELECT
        `lc`.`client_ref`
       ,MAX(`lc`.`DateDue`) AS `last_collection`
      FROM (
        SELECT
          `lcp`.`client_ref`
         ,`lcc`.`DateDue`
        FROM `blotto_player` AS `lcp`
        JOIN `blotto_build_collection` AS `lcc`
          ON `lcc`.`ClientRef`=`lcp`.`client_ref`
         AND `lcc`.`DateDue`<=`lcp`.`started`
      ) AS `lc`
      GROUP BY `lc`.`client_ref`
    )      AS `l`
           ON `l`.`client_ref`=`pcurr`.`client_ref`
    LEFT JOIN `{{BLOTTO_TICKET_DB}}`.`blotto_ticket` AS `tk`
           ON `tk`.`org_id`={{BLOTTO_ORG_ID}}
          AND `tk`.`client_ref`=`pcurr`.`client_ref`
    JOIN `Cancellations` AS `cl`
      ON `cl`.`client_ref`=`s`.`client_ref`
    GROUP BY `s`.`id`
    ORDER BY `updated`,`client_ref_orig`,`client_ref`
  ;
  -- Compile and order updates in a single table
  DROP TABLE IF EXISTS `Updates`
  ;
  CREATE TABLE `Updates` AS
    SELECT
      `u`.*
    FROM (
      SELECT
        *
      FROM `tmp_updates_first_collect`
      UNION
      SELECT
        *
      FROM `tmp_updates_player`
      UNION
      SELECT
        *
      FROM `tmp_updates_contact`
      UNION
      SELECT
        *
      FROM `tmp_updates_cancelled`
    ) AS `u`
    WHERE `u`.`updated`<CURDATE()
    ORDER BY `updated`,`client_ref_orig`,`client_ref`
  ;
  ALTER TABLE `Updates`
  ADD PRIMARY KEY (`updated`,`client_ref_orig`,`milestone`,`client_ref`)
  ;
  ALTER TABLE `Updates`
  ADD KEY `updater` (`updater`)
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
     ,'N' AS `superdraw`
     ,`pz`.`name` AS `prize`
     ,`e`.`client_ref`
     ,`mandate`.`Sortcode`
     ,`mandate`.`Account`
     ,`s`.`created`
     ,`s`.`cancelled`
     ,`s`.`ccc`
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
  INSERT INTO `WinsAdmin`
    SELECT
      `e`.`draw_closed`
     ,`w`.`amount` AS `winnings`
     ,`w`.`number` AS `ticket_number`
     ,'Y' AS `superdraw`
     ,`w`.`prize_name` AS `prize`
     ,`e`.`client_ref`
     ,`mandate`.`Sortcode`
     ,`mandate`.`Account`
     ,`s`.`created`
     ,`s`.`cancelled`
     ,`s`.`ccc`
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
    -- TODO: SUPER WINNERS GET LETTER FROM SUPER GAME INSTEAD?
     ,'' AS `entry_id`
     ,'' AS `draw_date`
     ,'' AS `letter_batch_ref`
     ,'' AS `letter_status`
    FROM `blotto_super_winner` AS `w`
    JOIN `blotto_super_entry` AS `e`
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
END$$



DELIMITER ;

