

USE `{{BLOTTO_MAKE_DB}}`
;


SET foreign_key_checks=0;


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

