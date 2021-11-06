
USE `{{BLOTTO_MAKE_DB}}`
;


SET foreign_key_checks=0;


CREATE TABLE IF NOT EXISTS `blotto_change` (
  `changed_date` date NOT NULL,
  `ccc` char(4) CHARACTER SET ascii NOT NULL,
  `agent_ref` char(16) CHARACTER SET ascii,
  `canvas_ref` int(11) unsigned NOT NULL,
  `chance_number` tinyint(3) DEFAULT NULL,
  `client_ref_original` varchar(64) CHARACTER SET ascii,
  `type` char(4) CHARACTER SET ascii NOT NULL DEFAULT 'DEC',
  `is_termination` tinyint(1) unsigned NOT NULL DEFAULT 0,
  `reinstatement_for` char(10) CHARACTER SET ascii NOT NULL,
  `amount_paid_before_this_date` decimal(8,2) NOT NULL,
  `supporter_signed` date DEFAULT NULL,
  `supporter_approved` date DEFAULT NULL,
  `supporter_created` date DEFAULT NULL,
  `supporter_first_paid` date DEFAULT NULL,
  PRIMARY KEY (`changed_date`,`ccc`,`canvas_ref`,`chance_number`),
  KEY `client_ref_original` (`client_ref_original`)
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

CREATE TABLE IF NOT EXISTS `blotto_player` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `started` date DEFAULT NULL,
  `supporter_id` int(11) unsigned DEFAULT NULL,
  `client_ref` varchar(64) CHARACTER SET ascii DEFAULT NULL,
  `first_draw_close` date DEFAULT NULL,
  `letter_batch_ref` varchar(64) CHARACTER SET ascii DEFAULT NULL,
  `letter_status` varchar(64) CHARACTER SET ascii DEFAULT NULL,
  `chances` int(11) unsigned DEFAULT NULL,
  `created` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
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
  `projected_first_draw_close` date DEFAULT NULL,
  `canvas_code` char(4) CHARACTER SET ascii DEFAULT NULL,
  `canvas_agent_ref` varchar(16) CHARACTER SET ascii,
  `canvas_ref` int(11) unsigned,
  `client_ref` varchar(64) CHARACTER SET ascii,
  PRIMARY KEY (`id`),
  UNIQUE KEY `canvas_ref` (`canvas_ref`),
  UNIQUE KEY `client_ref` (`client_ref`),
  KEY `created` (`created`),
  KEY `signed` (`signed`),
  KEY `approved` (`approved`),
  KEY `projected_first_draw_close` (`projected_first_draw_close`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8
;

