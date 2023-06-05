
USE `{{BLOTTO_MAKE_DB}}`
;
-- TODO check if this is wanted; paysuite-api sets RefNo up from the get-go. Other payment providers
-- could be changed to do the same.  Possibly this is legacy zaffo code.
UPDATE `blotto_build_mandate`
SET
  `RefNo`=CONCAT({{BLOTTO_ORG_ID}},digitsOnly(`RefOrig`))
;

ALTER TABLE `blotto_build_mandate`
ADD PRIMARY KEY (`Provider`,`RefNo`)
;
-- end TODO

CREATE FULLTEXT INDEX `search_idx`
ON `blotto_build_mandate` (
  `Name`
 ,`Sortcode`
 ,`Account`
 ,`StartDate`
 ,`LastStartDate`
 ,`Freq`
)
;


UPDATE `blotto_build_collection`
SET
  `RefNo`=CONCAT({{BLOTTO_ORG_ID}},digitsOnly(`RefOrig`))
;

ALTER TABLE `blotto_build_collection`
ADD PRIMARY KEY `DateDue_Provider_RefNo` (`DateDue`,`Provider`,`RefNo`)
;

ALTER TABLE `blotto_build_collection`
ADD FOREIGN KEY (`Provider`,`RefNo`)
  REFERENCES `blotto_build_mandate` (`Provider`,`RefNo`)
;

ALTER TABLE `blotto_build_collection`
ADD FOREIGN KEY (`Provider`,`RefOrig`)
  REFERENCES `blotto_build_mandate` (`Provider`,`RefOrig`)
;

