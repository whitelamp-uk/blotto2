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

GRANT SELECT ON `{{BLOTTO_DB}}`.`Journeys`
TO '{{BLOTTO_ORG_USER}}'
;

GRANT SELECT ON `{{BLOTTO_DB}}`.`JourneysDormancy`
TO '{{BLOTTO_ORG_USER}}'
;

GRANT SELECT ON `{{BLOTTO_DB}}`.`JourneysMonthly`
TO '{{BLOTTO_ORG_USER}}'
;

GRANT SELECT ON `{{BLOTTO_DB}}`.`Monies`
TO '{{BLOTTO_ORG_USER}}'
;

GRANT SELECT ON `{{BLOTTO_DB}}`.`MoniesMonthly`
TO '{{BLOTTO_ORG_USER}}'
;

GRANT SELECT ON `{{BLOTTO_DB}}`.`MoniesWeekly`
TO '{{BLOTTO_ORG_USER}}'
;

GRANT SELECT ON `{{BLOTTO_DB}}`.`Supporters`
TO '{{BLOTTO_ORG_USER}}'
;

GRANT SELECT ON `{{BLOTTO_DB}}`.`SupportersView`
TO '{{BLOTTO_ORG_USER}}'
;

GRANT SELECT ON `{{BLOTTO_DB}}`.`Updates`
TO '{{BLOTTO_ORG_USER}}'
;

GRANT SELECT ON `{{BLOTTO_DB}}`.`UpdatesLatest`
TO '{{BLOTTO_ORG_USER}}'
;

