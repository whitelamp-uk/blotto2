
USE `{{BLOTTO_MAKE_DB}}`
;


SET foreign_key_checks=0;


CREATE TABLE IF NOT EXISTS `blotto_change` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `changed_date` date NULL,
  `client_ref` varchar(64) CHARACTER SET ascii,
  `ccc` char(4) CHARACTER SET ascii NOT NULL,
  `canvas_agent_ref` char(16) CHARACTER SET ascii,
  `signed` date NOT NULL,
  `approved` date NOT NULL,
  `created` date NOT NULL,
  `canvas_ref` int(11) unsigned NOT NULL,
  `chance_number` tinyint(3) unsigned NOT NULL,
  `chance_ref` char(16) CHARACTER SET ascii,
  `type` char(4) CHARACTER SET ascii NOT NULL,
  `type_is_increment` tinyint(1) unsigned NOT NULL DEFAULT 0,
  `is_termination` tinyint(1) unsigned NOT NULL DEFAULT 0,
  `chances_orig` tinyint(3) unsigned NOT NULL,
  `supporter_id` int(11) unsigned NOT NULL,
  `update_id` int(11) unsigned NOT NULL,
  `milestone` char(16) CHARACTER SET ascii,
  `milestone_date` date NULL,
  `collected_last` date NULL,
  `collected_times` int(11) unsigned NOT NULL DEFAULT 0,
  `collected_amount` decimal(8,2) NOT NULL DEFAULT 0.00,
  PRIMARY KEY (`id`),
  KEY `changed_date` (`changed_date`),
  KEY `client_ref` (`client_ref`),
  KEY `ccc` (`ccc`),
  KEY `supporter_id` (`supporter_id`),
  KEY `update_id` (`update_id`),
  KEY `milestone` (`milestone`),
  KEY `milestone_date` (`milestone_date`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8
;

CREATE TABLE IF NOT EXISTS `blotto_contact` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `supporter_id` int(11) unsigned NOT NULL,
  `created` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updater` varchar(255) CHARACTER SET ascii NOT NULL DEFAULT 'SYSTEM',
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
  `country` varchar(255) DEFAULT NULL,
  `dob` date DEFAULT NULL,
  `yob` smallint(4) unsigned DEFAULT NULL,
  `p0` varchar(255) NOT NULL,
  `p1` varchar(255) NOT NULL,
  `p2` varchar(255) NOT NULL,
  `p3` varchar(255) NOT NULL,
  `p4` varchar(255) NOT NULL,
  `p5` varchar(255) NOT NULL,
  `p6` varchar(255) NOT NULL,
  `p7` varchar(255) NOT NULL,
  `p8` varchar(255) NOT NULL,
  `p9` varchar(255) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `supporter_id` (`supporter_id`),
  KEY `created` (`created`),
  KEY `updater` (`updater`),
  KEY `p0` (`p0`),
  KEY `p1` (`p1`),
  KEY `p2` (`p2`),
  KEY `p3` (`p3`),
  KEY `p4` (`p4`),
  KEY `p5` (`p5`),
  KEY `p6` (`p6`),
  KEY `p7` (`p7`),
  KEY `p8` (`p8`),
  KEY `p9` (`p9`),
  FULLTEXT KEY `search_idx` (`name_first`,`name_last`,`email`,`mobile`,`address_1`,`address_2`,`address_3`,`town`,`postcode`),
  CONSTRAINT `blotto_contact_ibfk_1` FOREIGN KEY (`supporter_id`) REFERENCES `blotto_supporter` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8
;

CREATE TABLE IF NOT EXISTS `blotto_crm_campaign` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `handle` varchar(64) CHARACTER SET ascii NOT NULL,
  `restrictions` varchar(64) CHARACTER SET ascii NOT NULL,
  `schedule_date` char(10) CHARACTER SET ascii NOT NULL COMMENT 'Non-digit = repeat eg yyyy-mm-08',
  `schedule_time` time DEFAULT '12:00:00',
  `letter_status` char(8) CHARACTER SET ascii NOT NULL DEFAULT 'Drafting',
  `letter_template_ref` char(64) CHARACTER SET ascii NOT NULL COMMENT 'Eg Stannp template ID',
  `letter_auto_approve` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `letter_auto_book` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `email_status` char(8) CHARACTER SET ascii NOT NULL DEFAULT 'Drafting',
  `email_template_ref` char(64) CHARACTER SET ascii NOT NULL COMMENT 'Eg Campaign Monitor ID',
  `sms_status` char(8) CHARACTER SET ascii NOT NULL DEFAULT 'Drafting',
  `sms_template` varchar(255) COLLATE utf8_unicode_ci NOT NULL COMMENT 'Dear {name_first}, ...',
  `created` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `letter_status` (`letter_status`),
  KEY `email_status` (`email_status`),
  KEY `sms_status` (`sms_status`),
  CONSTRAINT `blotto_crm_campaign_letter_status` FOREIGN KEY (`letter_status`) REFERENCES `blotto_crm_status` (`status`),
  CONSTRAINT `blotto_crm_campaign_email_status` FOREIGN KEY (`email_status`) REFERENCES `blotto_crm_status` (`status`),
  CONSTRAINT `blotto_crm_campaign_sms_status` FOREIGN KEY (`sms_status`) REFERENCES `blotto_crm_status` (`status`)
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci
;
-- TEST DATA
-- INSERT INTO `blotto_crm_campaign` (`id`, `handle`, `restrictions`, `schedule`, `letter_status`, `letter_template_ref`, `letter_auto_approve`, `letter_auto_book`, `email_status`, `email_template_ref`, `sms_status`, `sms_template`, `created`, `updated`) VALUES
-- (1, 'christmas-email',  'WHERE (1)',  'YYYY-12-24 11:00:00',  'Drafting', '', 0,  0,  'Running',  '03d77082-cf2c-4240-56a8-5e32ca9093f0', 'Drafting', '', '2021-11-06 21:39:42',  '2021-11-06 23:04:53'),
-- (2, 'birthday-sms', 'WHERE (4) AND NOT (2)',  'YYYY-MM-DD 16:45:00',  'Drafting', '', 0,  0,  'Drafting', '', 'Running',  'Hi {name_first}, we hope you have a great birthday tomorrow!', '2021-11-06 21:44:23',  '2021-11-06 23:05:11')
-- ;

CREATE TABLE IF NOT EXISTS `blotto_crm_job` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `campaign_id` int(11) unsigned NOT NULL,
  `type` char(8) CHARACTER SET ascii NOT NULL,
  `time_scheduled` datetime DEFAULT NULL,
  `job_ref` varchar(64) CHARACTER SET ascii NOT NULL,
  `job_status` char(16) CHARACTER SET ascii NOT NULL,
  `created` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `campaign_id` (`campaign_id`),
  CONSTRAINT `blotto_crm_job_campaign` FOREIGN KEY (`campaign_id`) REFERENCES `blotto_crm_campaign` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci
;
-- TEST DATA
-- INSERT INTO `blotto_crm_job` (`id`, `campaign_id`, `type`, `time_scheduled`, `job_ref`, `job_status`, `created`, `updated`) VALUES
-- (1, 1,  'email',  '2020-12-24 11:00:00',  '', 'complete', '2021-11-06 23:30:09',  NULL),
-- (2, 2,  'sms',  '2020-12-24 16:45:00',  '', 'complete', '2021-11-06 23:30:59',  NULL),
-- (3, 2,  'sms',  '2020-12-25 16:45:00',  '', 'complete', '2021-11-06 23:31:20',  NULL),
-- (4, 2,  'sms',  '2020-12-26 16:45:00',  '', 'complete', '2021-11-06 23:31:34',  NULL)
-- ;

CREATE TABLE IF NOT EXISTS `blotto_crm_restriction` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(64) COLLATE utf8_unicode_ci NOT NULL,
  `sql_clause` tinytext CHARACTER SET ascii NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci
  COMMENT='Use `s` for `Supporters` columns and `u` for `Updates` columns'
;
INSERT IGNORE INTO `blotto_crm_restriction` (`id`, `name`, `sql_clause`) VALUES
(1, 'Active supporters',  '`s`.`active`=\'ACTIVE\''),
(2, 'Inactive supporters',  '`s`.`active`=\'DEAD\''),
(3, 'Birthday today', '`s`.`dob` LIKE CONCAT(\r\n  \'____\'\r\n ,SUBSTR(\r\n    CURDATE()\r\n   ,5,6\r\n  )\r\n)'),
(4, 'Birthday tomorrow',  '`s`.`dob` LIKE CONCAT(\r\n  \'____\'\r\n ,SUBSTR(\r\n    DATE_ADD(\r\n      CURDATE()\r\n     ,INTERVAL 1 DAY)\r\n   ,5,6\r\n  )\r\n)')
;

CREATE TABLE IF NOT EXISTS `blotto_crm_status` (
  `status` char(16) CHARACTER SET ascii NOT NULL,
  `caution` mediumtext COLLATE utf8_unicode_ci NOT NULL,
  `description` mediumtext COLLATE utf8_unicode_ci NOT NULL,
  `allowed_statuses` tinytext CHARACTER SET armscii8 NOT NULL,
  PRIMARY KEY (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci
;
INSERT IGNORE INTO `blotto_crm_status` (`status`, `caution`, `description`, `allowed_statuses`) VALUES
('Complete',  'The campaign will be irreversibly terminated.',  'This campaign has been terminated.', ''),
('Drafting',  'The campaign will not run while in drafting mode.',  'Run when ready and tested.', 'Running'),
('Revising',  'The campaign will stop running for modification.', 'The campaign is under revision.',  'Running\r\nComplete'),
('Running', 'The campaign will be made live.',  'The campaign is now running.', 'Revising\r\nComplete')
;


CREATE TABLE `blotto_external` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `draw_closed` date DEFAULT NULL,
  `ticket_number` char(6) not null,
  `client_ref` varchar(255) not null,
  `created` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`ticket_number`),
  KEY `draw_closed` (`draw_closed`)
) ENGINE=InnoDB DEFAULT CHARSET=ascii COLLATE=ascii_general_ci;


CREATE TABLE IF NOT EXISTS `blotto_player` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `started` date DEFAULT NULL,
  `supporter_id` int(11) unsigned DEFAULT NULL,
  `client_ref` varchar(64) CHARACTER SET ascii DEFAULT NULL,
  `first_draw_close` date DEFAULT NULL,
  `letter_batch_ref` varchar(64) CHARACTER SET ascii DEFAULT NULL,
  `letter_status` varchar(64) CHARACTER SET ascii DEFAULT NULL,
  `letter_batch_ref_prev` varchar(64) CHARACTER SET ascii DEFAULT NULL,
  `opening_balance` decimal(8,2) NOT NULL DEFAULT 0.00,
  `chances` int(11) unsigned DEFAULT NULL,
  `created` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `client_ref` (`client_ref`),
  UNIQUE KEY `supporter_id_started` (`supporter_id`,`started`),
  KEY `supporter_id` (`supporter_id`),
  KEY `letter_batch_ref` (`letter_batch_ref`),
  KEY `created` (`created`),
  CONSTRAINT `blotto_player_ibfk_1` FOREIGN KEY (`supporter_id`) REFERENCES `blotto_supporter` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `blotto_super_entry` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `superdraw_db` varchar(64) CHARACTER SET ascii DEFAULT NULL,
  `superdraw_entry_id` int(11) unsigned NOT NULL,
  `draw_closed` date DEFAULT NULL,
  `amount` int(11) unsigned NOT NULL DEFAULT 0,
  `ticket_number` varchar(16) CHARACTER SET ascii DEFAULT NULL,
  `client_ref` varchar(64) CHARACTER SET ascii DEFAULT NULL,
  `created` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `superdraw_entry` (`superdraw_db`,`superdraw_entry_id`),
  UNIQUE KEY `superdraw_closed_ticket_number` (`superdraw_db`,`draw_closed`,`ticket_number`),
  KEY `superdraw_db` (`superdraw_db`),
  KEY `superdraw_entry_id` (`superdraw_entry_id`),
  KEY `draw_closed` (`draw_closed`),
  KEY `ticket_number` (`ticket_number`),
  KEY `client_ref` (`client_ref`),
  KEY `created` (`created`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8
  COMMENT='(superdraw_db,superdraw_entry_id) references `{superdraw_db}`.`blotto_entry`. Last 5 columns = * from `blotto_entry`'
;

CREATE TABLE IF NOT EXISTS `blotto_super_ticket` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `superdraw_db` varchar(64) CHARACTER SET ascii DEFAULT NULL,
  `superdraw_name` varchar(64) DEFAULT NULL,
  `ticket_number` char(16) CHARACTER SET ascii NOT NULL,
  `client_ref` varchar(255) CHARACTER SET ascii NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `superdraw_db_ticket_number` (`superdraw_db`,`ticket_number`),
  KEY `superdraw_db` (`superdraw_db`),
  KEY `ticket_number` (`ticket_number`),
  KEY `client_ref` (`client_ref`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8
;

CREATE TABLE IF NOT EXISTS `blotto_super_winner` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `entry_id` int(11) unsigned NOT NULL,
  `number` varchar(100) NOT NULL,
  `client_ref` varchar(64) CHARACTER SET ascii NOT NULL,
  `prize_level` int(11) unsigned DEFAULT NULL,
  `prize_starts` date NULL,
  `prize_name` varchar(64) DEFAULT NULL,
  `amount` int(11) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `entry_id_number` (`entry_id`,`number`),
  KEY `entry_id` (`entry_id`),
  KEY `number` (`number`),
  KEY `client_ref` (`client_ref`),
  KEY `amount` (`amount`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8
;

CREATE TABLE IF NOT EXISTS `blotto_supporter` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `created` date NULL,
  `signed` date DEFAULT NULL,
  `approved` date DEFAULT NULL,
  `redacted` tinyint(1) unsigned NOT NULL DEFAULT 0,
  `mandate_blocked` tinyint(1) unsigned NOT NULL DEFAULT 0,
  `self_excluded` date NULL,
  `death_reported` date NULL,
  `death_by_suicide` tinyint(1) unsigned NOT NULL DEFAULT 0,
  `projected_first_draw_close` date DEFAULT NULL,
  `projected_chances` tinyint(3) unsigned DEFAULT NULL,
  `canvas_code` char(16) CHARACTER SET ascii DEFAULT NULL,
  `canvas_agent_ref` varchar(16) CHARACTER SET ascii,
  `canvas_ref` int(11) unsigned,
  `client_ref` varchar(64) CHARACTER SET ascii,
  `inserted` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `client_ref` (`client_ref`),
  KEY `created` (`created`),
  KEY `signed` (`signed`),
  KEY `approved` (`approved`),
  KEY `projected_first_draw_close` (`projected_first_draw_close`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8
;

