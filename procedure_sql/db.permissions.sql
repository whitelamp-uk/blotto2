

-- ADMIN ROLE

CREATE ROLE IF NOT EXISTS
  '{{BLOTTO_ADMIN_USER}}'
;

GRANT SELECT
ON `{{BLOTTO_CONFIG_DB}}`.*
TO '{{BLOTTO_ADMIN_USER}}'
;
GRANT INSERT ON `{{BLOTTO_CONFIG_DB}}`.`blotto_invoice`
TO '{{BLOTTO_ADMIN_USER}}'
;
GRANT UPDATE(
  `org_code`
 ,`type`
 ,`raised`
 ,`terms`
 ,`description`
 ,`item_text`
 ,`item_quantity`
 ,`item_unit_price`
 ,`item_tax_percent`
)
ON `{{BLOTTO_CONFIG_DB}}`.`blotto_invoice`
TO '{{BLOTTO_ADMIN_USER}}'
;
GRANT UPDATE(
  `admin_email`
 ,`admin_phone`
 ,`signup_verify_email`
 ,`signup_verify_sms`
 ,`signup_paid_email`
 ,`signup_paid_sms`
 ,`pref_nr_email`
 ,`pref_nr_sms`
 ,`pref_nr_post`
 ,`pref_nr_phone`
 ,`signup_cm_key`
 ,`signup_cm_id`
 ,`signup_cm_id_verify`
 ,`signup_cm_id_trigger`
 ,`signup_ticket_options`
 ,`signup_amount_cap`
 ,`signup_dd_text`
 ,`signup_dd_link`
 ,`signup_draw_options`
 ,`signup_sms_from`
 ,`signup_verify_sms_message`
 ,`signup_done_message_ok`
 ,`signup_done_message_fail`
 ,`signup_sms_message`
 ,`signup_url_privacy`
 ,`signup_url_terms`
 ,`invoice_address`
 ,`invoice_terms_game`
 ,`invoice_terms_payout`
)
ON `{{BLOTTO_CONFIG_DB}}`.`blotto_org`
TO '{{BLOTTO_ADMIN_USER}}'
;


GRANT SELECT
ON `{{BLOTTO_TICKET_DB}}`.*
TO '{{BLOTTO_ADMIN_USER}}'
;

GRANT SELECT
ON `{{BLOTTO_DB}}`.*
TO '{{BLOTTO_ADMIN_USER}}'
;

GRANT SELECT
ON `mysql`.`proc`
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



-- ORGANISATION ROLE AND TEST USER


CREATE USER IF NOT EXISTS
  '{{BLOTTO_ORG_USER}}'@'localhost'
;

CREATE ROLE IF NOT EXISTS
  '{{BLOTTO_ORG_USER}}'
;

GRANT '{{BLOTTO_ORG_USER}}'
TO '{{BLOTTO_ORG_USER}}'@'localhost'
;

SET DEFAULT ROLE '{{BLOTTO_ORG_USER}}'
FOR '{{BLOTTO_ORG_USER}}'@'localhost'
;



