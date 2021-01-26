
USE `{{BLOTTO_MAKE_DB}}`
;


DROP TABLE IF EXISTS `tmp_supporter`;


CREATE TABLE `tmp_supporter` (
  `Nominated` date DEFAULT NULL,
  `Signed` date DEFAULT NULL,
  `Approved` date DEFAULT NULL,
  `AgentRef` int(11) unsigned NOT NULL,
  `CanvasRef` int(11) unsigned NOT NULL,
  `ClientRef` varchar(255) NOT NULL,
  `Title` varchar(64) NOT NULL,
  `FirstName` varchar(255) NOT NULL,
  `LastName` varchar(255) NOT NULL,
  `Email` varchar(255) CHARACTER SET ascii NOT NULL,
  `Mobile` varchar(255) CHARACTER SET ascii NOT NULL,
  `Telephone` varchar(255) CHARACTER SET ascii NOT NULL,
  `AddressLine1` varchar(255) NOT NULL,
  `AddressLine2` varchar(255) NOT NULL,
  `AddressLine3` varchar(255) NOT NULL,
  `Town` varchar(255) NOT NULL,
  `County` varchar(255) NOT NULL,
  `Postcode` varchar(255) NOT NULL,
  `Country` varchar(255) NOT NULL,
  `DOB` varchar(255) NOT NULL,
  `P0` varchar(255) NOT NULL,
  `P1` varchar(255) NOT NULL,
  `P2` varchar(255) NOT NULL,
  `P3` varchar(255) NOT NULL,
  `P4` varchar(255) NOT NULL,
  `P5` varchar(255) NOT NULL,
  `P6` varchar(255) NOT NULL,
  `P7` varchar(255) NOT NULL,
  `P8` varchar(255) NOT NULL,
  `P9` varchar(255) NOT NULL,
  UNIQUE KEY `ClientRef` (`ClientRef`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


LOAD DATA LOCAL INFILE '{{BLOTTO_CSV_S}}'
INTO TABLE `tmp_supporter`
FIELDS TERMINATED BY ',' ENCLOSED BY '\"'
LINES TERMINATED BY '\n'
IGNORE 1 ROWS
;



