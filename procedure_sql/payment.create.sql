
USE `{{BLOTTO_MAKE_DB}}`
;

SET foreign_key_checks = 0;

DROP TABLE IF EXISTS `blotto_build_collection`;

CREATE TABLE `blotto_build_collection` (
  `DateDue` date DEFAULT NULL,
  `Provider` char(4) CHARACTER SET ascii DEFAULT NULL,
  `RefNo` bigint(20) unsigned DEFAULT NULL,
  `RefOrig` varchar(64) CHARACTER SET ascii DEFAULT NULL,
  `ClientRef` varchar(255) CHARACTER SET ascii DEFAULT NULL,
  `PaidAmount` decimal (10,2) DEFAULT NULL,
  `Status` char (8) DEFAULT 'Paid',
  UNIQUE KEY `DateDue_Provider_RefOrig` (`DateDue`,`Provider`,`RefOrig`),
  UNIQUE KEY `DateDue_ClientRef` (`DateDue`,`ClientRef`),
  KEY `DateDue` (`DateDue`),
  KEY `RefNo` (`RefNo`),
  KEY `RefOrig` (`RefOrig`),
  KEY `ClientRef` (`ClientRef`),
  KEY `PaidAmount` (`PaidAmount`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8
;

DROP TABLE IF EXISTS `blotto_build_mandate`;

CREATE TABLE `blotto_build_mandate` (
  `Provider` char(4) CHARACTER SET ascii DEFAULT NULL,
  `RefNo` bigint(20) unsigned DEFAULT NULL,
  `RefOrig` varchar(64) CHARACTER SET ascii DEFAULT NULL,
  `ClientRef` varchar(255) CHARACTER SET ascii DEFAULT NULL,
  `Created` date DEFAULT NULL,
  `Updated` date DEFAULT NULL,
  `StartDate` char(16) CHARACTER SET ascii DEFAULT NULL,
  `Status` char(16) CHARACTER SET ascii DEFAULT NULL,
  `Freq` char(16) CHARACTER SET ascii DEFAULT NULL,
  `Amount` decimal(10,2) DEFAULT NULL,
  `ChancesCsv` varchar(255) CHARACTER SET ascii NOT NULL,
  `Name` varchar(255) CHARACTER SET ascii DEFAULT NULL,
  `Sortcode` char(16) CHARACTER SET ascii DEFAULT NULL,
  `Account` char(16) CHARACTER SET ascii DEFAULT NULL,
  `FailReason` varchar(255) CHARACTER SET ascii DEFAULT NULL,
  `ImportId` int(10) unsigned DEFAULT NULL,
  `TimesCreated` tinyint(3) unsigned DEFAULT NULL,
  `LastCreated` date DEFAULT NULL,
  `LastStartDate` char(16) CHARACTER SET ascii DEFAULT NULL,
  UNIQUE KEY `Provider_RefOrig` (`Provider`,`RefOrig`),
  UNIQUE KEY `ClientRef` (`ClientRef`),
  KEY `Created` (`Created`),
  KEY `Updated` (`Updated`),
  KEY `StartDate` (`StartDate`),
  KEY `Status` (`Status`),
  KEY `Freq` (`Freq`),
  KEY `Amount` (`Amount`),
  KEY `TimesCreated` (`TimesCreated`),
  KEY `LastCreated` (`LastCreated`),
  KEY `LastStartDate` (`LastStartDate`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8
;

