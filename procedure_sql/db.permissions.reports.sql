-- ORGANISATION ROLE TABLE ACCESS


GRANT SELECT ON `{{BLOTTO_DB}}`.`ANLs`
TO '{{BLOTTO_ORG_USER}}'
;

GRANT SELECT ON `{{BLOTTO_DB}}`.`Cancellations`
TO '{{BLOTTO_ORG_USER}}'
;

GRANT SELECT ON `{{BLOTTO_DB}}`.`Changes`
TO '{{BLOTTO_ORG_USER}}'
;

GRANT SELECT ON `{{BLOTTO_DB}}`.`Draws`
TO '{{BLOTTO_ORG_USER}}'
;

GRANT SELECT ON `{{BLOTTO_DB}}`.`Draws_Summary`
TO '{{BLOTTO_ORG_USER}}'
;

GRANT SELECT ON `{{BLOTTO_DB}}`.`Draws_Supersummary`
TO '{{BLOTTO_ORG_USER}}'
;

GRANT SELECT ON `{{BLOTTO_DB}}`.`Supporters`
TO '{{BLOTTO_ORG_USER}}'
;

GRANT SELECT ON `{{BLOTTO_DB}}`.`Updates`
TO '{{BLOTTO_ORG_USER}}'
;

GRANT SELECT ON `{{BLOTTO_DB}}`.`Wins`
TO '{{BLOTTO_ORG_USER}}'
;


