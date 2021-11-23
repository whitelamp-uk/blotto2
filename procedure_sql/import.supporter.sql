
USE `{{BLOTTO_MAKE_DB}}`
;

-- FLC format https://www.whitelamp.com/flc/
-- Not all RHS columns are necessarily populated
-- but these column positions are immutable
-- eg. System exclusive messages must be in column J
DROP TABLE IF EXISTS `tmp_supporter`;
CREATE TABLE `tmp_supporter` (
  `ClientRef` varchar(255) CHARACTER SET ascii NOT NULL,
  `Type` char(1) CHARACTER SET ascii NOT NULL DEFAULT 'C',
  `Chances` varchar(255) CHARACTER SET ascii NOT NULL,
  `PayDay` varchar(255) CHARACTER SET ascii NOT NULL,
  `Name` varchar(255) NOT NULL,
  `SortCode` varchar(255) CHARACTER SET ascii NOT NULL,
  `Account` varchar(255) CHARACTER SET ascii NOT NULL,
  `Amount` varchar(255) CHARACTER SET ascii NOT NULL,
  `Freq` varchar(255) CHARACTER SET ascii NOT NULL,
  `SysEx` varchar(255) NOT NULL,
  `Signed` varchar(255) CHARACTER SET ascii NOT NULL,
  `Approved` varchar(255) CHARACTER SET ascii NOT NULL,
  `Created` varchar(255) CHARACTER SET ascii NOT NULL,
  `Tickets` varchar(255) CHARACTER SET ascii NOT NULL,
  `NamesGiven` varchar(255) NOT NULL,
  `NamesFamily` varchar(255) NOT NULL,
  `EasternOrder` char(1) CHARACTER SET ascii NOT NULL,
  `DOB` varchar(255) NOT NULL,
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
  `Preferences` text,
  `PayChannel` varchar(255) NOT NULL
  `PayChanged` varchar(255) NOT NULL
  `PayLatest` varchar(255) NOT NULL
  `Active` varchar(255) NOT NULL
  `Status` varchar(255) NOT NULL
  `FailReason` varchar(255) NOT NULL
  `TicketPricePerDraw` varchar(255) NOT NULL
  `PaymenFirstReceived` varchar(255) NOT NULL
  `PaymentTotal` varchar(255) NOT NULL
  `Plays` varchar(255) NOT NULL
  `CurrentBalance` varchar(255) NOT NULL
  UNIQUE KEY `ClientRef` (`ClientRef`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


LOAD DATA LOCAL INFILE '{{BLOTTO_CSV_S}}'
INTO TABLE `tmp_supporter`
FIELDS TERMINATED BY ',' ENCLOSED BY '\"'
LINES TERMINATED BY '\n'
IGNORE 1 ROWS
;



