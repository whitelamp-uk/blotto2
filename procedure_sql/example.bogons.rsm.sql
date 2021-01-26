

-- 1. Delete mandate rows created in error
DELETE FROM `blotto_build_mandate`
WHERE `ClientRef`='RUBBISH'
;
DELETE FROM `blotto_build_mandate`
WHERE `DDRefNo`='ORIGDREF'
  AND `Amount`=4.44
LIMIT 1
;



-- 2. Transform incorrect ClientRefs
UPDATE `blotto_build_mandate`
  SET
    `ClientRef`='CORRECT'
WHERE `ClientRef`='WRONG'
;
UPDATE `blotto_build_collection`
  SET
    `ClientRef`='CORRECT'
WHERE `ClientRef`='WRONG'
;



-- 3. Incorrect amount
UPDATE `blotto_build_mandate`
  SET
    `Amount`='4.34'
WHERE `ClientRef`='ORIGCREF'
;


-- 4. DDRefOrig:ClientRef not 1:1
--      * Too few DDRefNos per ClientRef:
UPDATE `blotto_build_mandate`
  SET
    `DDRefNo`='ORIGDREF0001'
WHERE `ClientRef`='ORIGCREF-0001'
;
UPDATE `blotto_build_collection`
  SET
    `DDRefNo`='ORIGDREF0001'
WHERE `ClientRef`='ORIGCREF-0001'
;
--      * Too few ClientRefs per DDRefNo:
UPDATE `blotto_build_mandate`
  SET
    `ClientRef`='ORIGCREF-0001'
WHERE `ClientRef`='ORIGCREF'
  AND `DDRefNo`='REPLACEMENTDREF'
;
UPDATE `blotto_build_collection`
  SET
    `ClientRef`='ORIGCREF-0001'
WHERE `ClientRef`='ORIGCREF'
  AND `DDRefNo`='REPLACEMENTDREF'
;


-- 5. Freq-Amount not unique per DDRefOrig



-- 6. DDRefOrig not unique per DateDue



-- 7. Amount not unique per DDRefOrig







