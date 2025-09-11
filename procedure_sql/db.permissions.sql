

-- IN CASE ONBOARDING TABLE IS MISSING

CREATE TABLE IF NOT EXISTS `{{BLOTTO_CONFIG_DB}}`.`{{BLOTTO_BRAND}}_onboarding_{{BLOTTO_ORG_USER}}`
LIKE `{{BLOTTO_CONFIG_DB}}`.`blotto_onboarding`
;


-- ADMIN ROLE

CREATE ROLE IF NOT EXISTS
  '{{BLOTTO_ADMIN_USER}}'
;


-- ADMIN CONFIG

GRANT SELECT
ON `mysql`.`proc`
TO '{{BLOTTO_ADMIN_USER}}'
;

GRANT SELECT
ON `mysql`.`user`
TO `{{BLOTTO_ADMIN_USER}}`
;

GRANT SELECT, EXECUTE
ON `blotto_root`.*
TO `admin`
;

GRANT SELECT,EXECUTE
ON `{{BLOTTO_CONFIG_DB}}`.*
TO '{{BLOTTO_ADMIN_USER}}'
;

GRANT INSERT,UPDATE ON `{{BLOTTO_CONFIG_DB}}`.`blotto_claim`
TO '{{BLOTTO_ADMIN_USER}}'
;

GRANT INSERT,UPDATE ON `{{BLOTTO_CONFIG_DB}}`.`blotto_invoice`
TO '{{BLOTTO_ADMIN_USER}}'
;

GRANT UPDATE (`value`)
ON `{{BLOTTO_CONFIG_DB}}`.`{{BLOTTO_BRAND}}_onboarding_{{BLOTTO_ORG_USER}}`
TO `{{BLOTTO_ADMIN_USER}}`
;

GRANT INSERT,UPDATE(
  `admin_email`
 ,`admin_phone`
 ,`anl_cm_id`
 ,`invoice_address`
 ,`invoice_terms_game`
 ,`invoice_terms_payout`
 ,`pref_nr_email`
 ,`pref_nr_phone`
 ,`pref_nr_post`
 ,`pref_nr_sms`
 ,`signup_amount_cap`
 ,`signup_close_advance_hours`
 ,`signup_cm_id`
 ,`signup_cm_id_trigger`
 ,`signup_cm_id_verify`
 ,`signup_cm_key`
 ,`signup_dd_link`
 ,`signup_dd_text`
 ,`signup_done_message_fail`
 ,`signup_done_message_ok`
 ,`signup_draw_options`
 ,`signup_paid_email`
 ,`signup_paid_sms`
 ,`signup_sms_from`
 ,`signup_sms_message`
 ,`signup_ticket_options`
 ,`signup_url_privacy`
 ,`signup_url_terms`
 ,`signup_verify_email`
 ,`signup_verify_sms`
 ,`signup_verify_sms_message`
 ,`territories_csv`
)
ON `{{BLOTTO_CONFIG_DB}}`.`blotto_org`
TO '{{BLOTTO_ADMIN_USER}}'
;

GRANT INSERT, UPDATE ON `{{BLOTTO_CONFIG_DB}}`.`blotto_user`
TO `{{BLOTTO_ADMIN_USER}}`
;


-- ADMIN ORG DATABASES

GRANT SELECT
ON `{{BLOTTO_TICKET_DB}}`.*
TO '{{BLOTTO_ADMIN_USER}}'
;

GRANT SELECT
ON `{{BLOTTO_MAKE_DB}}`.*
TO '{{BLOTTO_ADMIN_USER}}'
;

GRANT UPDATE(
  `self_excluded`
 ,`death_reported`
 ,`death_by_suicide`
)
ON `{{BLOTTO_MAKE_DB}}`.`blotto_supporter`
TO '{{BLOTTO_ADMIN_USER}}'
;

GRANT SELECT
ON `{{BLOTTO_DB}}`.*
TO '{{BLOTTO_ADMIN_USER}}'
;

GRANT EXECUTE
ON PROCEDURE `{{BLOTTO_DB}}`.`activityAll`
TO '{{BLOTTO_ADMIN_USER}}'
;

GRANT EXECUTE
ON PROCEDURE `{{BLOTTO_DB}}`.`activityBank`
TO '{{BLOTTO_ADMIN_USER}}'
;

GRANT EXECUTE
ON PROCEDURE `{{BLOTTO_DB}}`.`activityEmail`
TO '{{BLOTTO_ADMIN_USER}}'
;

GRANT EXECUTE
ON PROCEDURE `{{BLOTTO_DB}}`.`activityHouse`
TO '{{BLOTTO_ADMIN_USER}}'
;

GRANT EXECUTE
ON PROCEDURE `{{BLOTTO_DB}}`.`activityMobile`
TO '{{BLOTTO_ADMIN_USER}}'
;


GRANT EXECUTE
ON PROCEDURE `{{BLOTTO_DB}}`.`help`
TO '{{BLOTTO_ADMIN_USER}}'
;

GRANT EXECUTE
ON PROCEDURE `{{BLOTTO_DB}}`.`mandatesHavingNoSupporter`
TO '{{BLOTTO_ADMIN_USER}}'
;

GRANT EXECUTE
ON PROCEDURE `{{BLOTTO_DB}}`.`supportersHavingNoMandate`
TO '{{BLOTTO_ADMIN_USER}}'
;



-- ORGANISATION ROLE AND TEST USER (xyztff) (if needed)

CREATE ROLE IF NOT EXISTS
  '{{BLOTTO_ORG_USER}}'
;

GRANT SELECT
ON `mysql`.`proc`
TO '{{BLOTTO_ORG_USER}}'
;

-- CREATE USER IF NOT EXISTS
--   '{{BLOTTO_ORG_USER}}tff'@'localhost'
-- ;
-- 
-- GRANT '{{BLOTTO_ORG_USER}}'
-- TO '{{BLOTTO_ORG_USER}}tff'@'localhost'
-- ;
-- 
-- SET DEFAULT ROLE '{{BLOTTO_ORG_USER}}'
-- FOR '{{BLOTTO_ORG_USER}}tff'@'localhost'
-- ;



