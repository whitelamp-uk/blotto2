

CREATE DATABASE IF NOT EXISTS `{{BLOTTO_CONFIG_DB}}`
  DEFAULT CHARACTER SET = utf8
;

CREATE DATABASE IF NOT EXISTS `{{BLOTTO_TICKET_DB}}`
  DEFAULT CHARACTER SET = utf8
;

CREATE DATABASE IF NOT EXISTS `{{BLOTTO_MAKE_DB}}`
  DEFAULT CHARACTER SET = utf8
;

CREATE DATABASE IF NOT EXISTS `{{BLOTTO_RESULTS_DB}}`
  DEFAULT CHARACTER SET = utf8
;



USE `{{BLOTTO_CONFIG_DB}}`
;



CREATE TABLE IF NOT EXISTS `_readme` (
  `project` char(64),
  `location` varchar(255) NOT NULL,
  PRIMARY KEY (`project`)
) ENGINE=InnoDB DEFAULT CHARSET=ascii;
INSERT IGNORE INTO `_readme` (`project`, `location`) VALUES
('whitelamp-uk/blotto2', 'https://github.com/whitelamp-uk/blotto2.git');

CREATE TABLE IF NOT EXISTS `blotto_bacs` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `requested_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `org_id` int(11) unsigned NOT NULL,
  `updater` varchar(255) CHARACTER SET ascii NOT NULL,
  `ClientRef` varchar(255) CHARACTER SET utf8,
  `OldDDRef` varchar(255) CHARACTER SET utf8,
  `OldName` varchar(255) CHARACTER SET utf8,
  `OldSortcode` varchar(255) CHARACTER SET utf8,
  `OldAccount` varchar(255) CHARACTER SET utf8,
  `NewClientRef` varchar(255) CHARACTER SET utf8,
  `Name` varchar(255) CHARACTER SET utf8,
  `Sortcode` varchar(255) CHARACTER SET utf8,
  `Account` varchar(255) CHARACTER SET utf8,
  `Freq` varchar(255) CHARACTER SET utf8,
  `Amount` varchar(255) CHARACTER SET utf8,
  `Chances` int(11) unsigned DEFAULT NULL,
  `StartDate` varchar(255) CHARACTER SET utf8,
  PRIMARY KEY (`id`),
  KEY `org_id` (`org_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


CREATE TABLE IF NOT EXISTS `blotto_help` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `comments` text NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8
;

ALTER TABLE `blotto_help`
DROP INDEX IF EXISTS `search_idx`
;


INSERT IGNORE INTO `blotto_help` (`id`, `comments`) VALUES
(1, 'Column headings containing capital letters show data derived from direct debit data.'),
(2, 'Column heading having no capital letters show data derived from sign-up data or from internal calculations.'),
(3, 'Contact details are always the latest on record - even for historical draw and winner reports. Historical records of contact details are retained as an audit trail in case of problems contacting winners. Archived contact details are only available by specific request - contact your account administrator.'),
(4, 'Results based on supporter show the latest ClientRef and tickets for each supporter. Results based on mandate/collection history show all ClientRef and ticket values in the relevant context')
;

CREATE FULLTEXT INDEX `search_idx`
  ON `blotto_help` (
    `comments`
  )
;

CREATE TABLE IF NOT EXISTS `blotto_level` (
  `config_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `level` char(4) CHARACTER SET ascii NOT NULL,
  `comments` varchar(64) NOT NULL,
  PRIMARY KEY (`config_id`),
  UNIQUE KEY `level` (`level`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

INSERT IGNORE INTO `blotto_level` (`config_id`, `level`, `comments`) VALUES
(1, '6LR6', 'Match 6 of 6 numbers left or right'),
(2, '5LR6', 'Match 5 of 6 numbers left or right'),
(3, '4LR6', 'Match 4 of 6 numbers left or right'),
(4, '3LR6', 'Match 3 of 6 numbers left or right');


CREATE TABLE IF NOT EXISTS `blotto_org` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `org_code` char(16) CHARACTER SET ascii NOT NULL,
  `zaffo_merchant_id` int(11) unsigned DEFAULT NULL,
  `admin_email` varchar(255) CHARACTER SET ascii DEFAULT NULL,
  `admin_phone` varchar(16) CHARACTER SET ascii DEFAULT NULL,
  `signup_verify_email` tinyint(1) unsigned NOT NULL DEFAULT 0,
  `signup_verify_sms` tinyint(1) unsigned NOT NULL DEFAULT 0,
  `signup_paid_email` tinyint(1) unsigned NOT NULL DEFAULT 0,
  `signup_paid_sms` tinyint(1) unsigned NOT NULL DEFAULT 0,
  `signup_cm_key` varchar(255) CHARACTER SET ascii DEFAULT NULL,
  `signup_cm_id` varchar(255) CHARACTER SET ascii DEFAULT NULL,
  `signup_cm_id_verify` varchar(255) CHARACTER SET ascii DEFAULT NULL,
  `signup_cm_id_trigger` varchar(255) CHARACTER SET ascii DEFAULT NULL,
  `signup_amount_cap` mediumint(4) unsigned NOT NULL,
  `signup_ticket_options` varchar(255) CHARACTER SET utf8 COLLATE utf8_unicode_ci DEFAULT NULL,
  `signup_dd_text` mediumtext CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
  `signup_dd_link` varchar(255) CHARACTER SET ascii NOT NULL,
  `signup_draw_options` mediumtext CHARACTER SET utf8 COLLATE utf8_unicode_ci,
  `signup_sms_from` char(11) CHARACTER SET ascii DEFAULT NULL,
  `signup_verify_sms_message` mediumtext CHARACTER SET utf8 COLLATE utf8_unicode_ci,
  `signup_done_message_ok` mediumtext CHARACTER SET utf8 COLLATE utf8_unicode_ci,
  `signup_done_message_fail` mediumtext CHARACTER SET utf8 COLLATE utf8_unicode_ci,
  `signup_sms_message` mediumtext CHARACTER SET utf8 COLLATE utf8_unicode_ci,
  `signup_url_privacy` varchar(255) CHARACTER SET ascii DEFAULT NULL,
  `signup_url_terms` varchar(255) CHARACTER SET ascii DEFAULT NULL,
  `invoice_address` mediumtext CHARACTER SET utf8 COLLATE utf8_unicode_ci,
  `invoice_terms_game` varchar(255) CHARACTER SET utf8 COLLATE utf8_unicode_ci DEFAULT NULL,
  `invoice_terms_payout` varchar(255) CHARACTER SET utf8 COLLATE utf8_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `org_code` (`org_code`),
  UNIQUE KEY `zaffo_merchant_id` (`zaffo_merchant_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

INSERT IGNORE INTO `blotto_org`
SET
  `org_code`=UPPER('{{BLOTTO_ORG_USER}}')
;



USE `{{BLOTTO_TICKET_DB}}`
;


-- PERMANENT TABLES



CREATE TABLE IF NOT EXISTS `_readme` (
  `project` char(64),
  `location` varchar(255) NOT NULL,
  PRIMARY KEY (`project`)
) ENGINE=InnoDB DEFAULT CHARSET=ascii;
INSERT IGNORE INTO `_readme` (`project`, `location`) VALUES
('whitelamp-uk/blotto2', 'https://github.com/whitelamp-uk/blotto2.git');

CREATE TABLE IF NOT EXISTS `blotto_ticket` (
  `number` char(16) CHARACTER SET ascii NOT NULL,
  `issue_date` date DEFAULT NULL,
  `org_id` int(11) unsigned DEFAULT NULL,
  `provider`char(4) CHARACTER SET ascii DEFAULT NULL,
  `client_ref` varchar(255) CHARACTER SET ascii DEFAULT NULL,
  PRIMARY KEY (`number`),
  UNIQUE KEY `org_id_client_ref` (`org_id`,`client_ref`),
  KEY `issue_date` (`issue_date`),
  KEY `org_id` (`org_id`),
  KEY `client_ref` (`client_ref`),
  KEY `org_id_client_ref_issue_date` (`org_id`,`client_ref`,`issue_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8
;



USE `{{BLOTTO_RESULTS_DB}}`
;


CREATE TABLE IF NOT EXISTS `_readme` (
  `project` char(64),
  `location` varchar(255) NOT NULL,
  PRIMARY KEY (`project`)
) ENGINE=InnoDB DEFAULT CHARSET=ascii;
INSERT IGNORE INTO `_readme` (`project`, `location`) VALUES
('whitelamp-uk/blotto2', 'https://github.com/whitelamp-uk/blotto2.git');

CREATE TABLE IF NOT EXISTS `blotto_result` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `draw_closed` date DEFAULT NULL,
  `draw_date` date DEFAULT NULL,
  `prize_level` int(11) unsigned NOT NULL DEFAULT 1,
  `number` varchar(16) CHARACTER SET ascii DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `draw_closed_prize_level_number` (`draw_closed`,`prize_level`,`number`),
  KEY `draw_closed_prize_level` (`draw_closed`,`prize_level`),
  KEY `draw_closed` (`draw_closed`),
  KEY `draw_date` (`draw_date`),
  KEY `prize_level` (`prize_level`),
  KEY `number` (`number`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8
;



USE `{{BLOTTO_MAKE_DB}}`
;


SET foreign_key_checks=0;


CREATE TABLE IF NOT EXISTS `_readme` (
  `project` char(64),
  `location` varchar(255) NOT NULL,
  PRIMARY KEY (`project`)
) ENGINE=InnoDB DEFAULT CHARSET=ascii;
INSERT IGNORE INTO `_readme` (`project`, `location`) VALUES
('whitelamp-uk/blotto2', 'https://github.com/whitelamp-uk/blotto2.git');

CREATE TABLE IF NOT EXISTS `blotto_entry` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `draw_closed` date NULL,
  `ticket_number` varchar(16) CHARACTER SET ascii,
  `client_ref` varchar(64) CHARACTER SET ascii,
  `created` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `draw_closed_ticket_number` (`draw_closed`,`ticket_number`),
  KEY `draw_closed` (`draw_closed`),
  KEY `ticket_number` (`ticket_number`),
  KEY `client_ref` (`client_ref`),
  KEY `created` (`created`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8
;

CREATE TABLE IF NOT EXISTS `blotto_generation` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `generated` datetime DEFAULT NULL,
  `provider` varchar(255) CHARACTER SET ascii DEFAULT NULL,
  `reference` varchar(64) CHARACTER SET ascii NOT NULL,
  `min` int(11) unsigned DEFAULT NULL,
  `max` int(11) unsigned DEFAULT NULL,
  `n` int(11) unsigned DEFAULT NULL,
  `results_csv` mediumtext CHARACTER SET ascii,
  `random_object` mediumtext CHARACTER SET ascii,
  `signature` mediumtext CHARACTER SET ascii NOT NULL,
  `log` mediumtext NOT NULL,
  PRIMARY KEY (`id`),
  KEY `provider` (`provider`),
  KEY `reference` (`reference`),
  KEY `generated` (`generated`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8
;

CREATE TABLE IF NOT EXISTS `blotto_insurance` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `draw_closed` date DEFAULT NULL,
  `ticket_number` varchar(16) CHARACTER SET ascii DEFAULT NULL,
  `org_ref` char(4) CHARACTER SET ascii DEFAULT NULL,
  `client_ref` varchar(64) CHARACTER SET ascii DEFAULT NULL,
  `created` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `draw_closed_ticket_number` (`draw_closed`,`ticket_number`),
  KEY `draw_closed` (`draw_closed`),
  KEY `ticket_number` (`ticket_number`),
  KEY `org_ref` (`org_ref`),
  KEY `client_ref` (`client_ref`),
  KEY `created` (`created`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8
;

CREATE TABLE IF NOT EXISTS `blotto_prize` (
  `starts` date NOT NULL DEFAULT '2000-01-01',
  `level` tinyint(3) unsigned NOT NULL,
  `expires` date NOT NULL DEFAULT '2099-12-31',
  `name` varchar(255) COLLATE 'utf8_general_ci' NOT NULL DEFAULT 'Unnamed',
  `insure` tinyint(1) unsigned NOT NULL DEFAULT 0,
  `results_manual` tinyint(1) unsigned NOT NULL DEFAULT 0,
  `function_name` varchar(64) CHARACTER SET ascii DEFAULT NULL,
  `level_method` char(4) CHARACTER SET ascii DEFAULT NULL,
  `quantity` tinyint(3) unsigned DEFAULT NULL,
  `quantity_percent` decimal(3,1) unsigned DEFAULT NULL,
  `amount` int(11) unsigned DEFAULT NULL,
  `amount_cap` int(11) unsigned NOT NULL DEFAULT 0,
  `amount_brought_forward` int(11) unsigned NOT NULL DEFAULT 0,
  `rollover_amount` int(11) unsigned NOT NULL DEFAULT 0,
  `rollover_cap` tinyint(3) unsigned NOT NULL DEFAULT 0,
  `rollover_count` tinyint(3) unsigned NOT NULL DEFAULT 0,
  PRIMARY KEY (`starts`,`level`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8
  COMMENT='expires column only matters if a prize level is removed rather than replaced'
;

CREATE TABLE IF NOT EXISTS `blotto_verification` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `created` timestamp DEFAULT CURRENT_TIMESTAMP,
  `type` char(16) CHARACTER SET ascii NOT NULL,
  `verify_value` varchar(255) CHARACTER SET ascii NOT NULL,
  `code` char(16) CHARACTER SET ascii NOT NULL,
  PRIMARY KEY (`id`),
  KEY `created` (`created`),
  KEY `type` (`type`),
  KEY `verify_value` (`verify_value`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8
;

CREATE TABLE IF NOT EXISTS `blotto_winner` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `entry_id` int(11) unsigned NOT NULL,
  `number` varchar(100) NOT NULL,
  `prize_level` int(11) unsigned DEFAULT NULL,
  `prize_starts` date NULL,
  `amount` int(11) unsigned NOT NULL,
  `letter_batch_ref` varchar(64) CHARACTER SET ascii DEFAULT NULL,
  `letter_status` varchar(64) CHARACTER SET ascii DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `entry_id_number` (`entry_id`,`number`),
  KEY `entry_id` (`entry_id`),
  KEY `number` (`number`),
  KEY `amount` (`amount`),
  KEY `letter_batch_ref` (`letter_batch_ref`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8
;

