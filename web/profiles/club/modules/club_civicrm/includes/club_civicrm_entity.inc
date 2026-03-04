<?php

/**
 * @file
 * Enable CiviCRM entities.
 */

function club_civicrm_entity() {
  $enabled_entity_type = [
    'civicrm_address' => "civicrm_address",
    'civicrm_contact' => "civicrm_contact",
    'civicrm_contribution' => "civicrm_contribution",
    'civicrm_contribution_page' => "civicrm_contribution_page",
    'civicrm_custom_field' => "civicrm_custom_field",
    'civicrm_custom_group' => "civicrm_custom_group",
    'civicrm_email' => "civicrm_email",
    'civicrm_entity_financial_trxn' => "civicrm_entity_financial_trxn",
    'civicrm_entity_tag' => "civicrm_entity_tag",
    'civicrm_event' => "civicrm_event",
    'civicrm_financial_account' => "civicrm_financial_account",
    'civicrm_financial_trxn' => "civicrm_financial_trxn",
    'civicrm_financial_type' => "civicrm_financial_type",
    'civicrm_group' => "civicrm_group",
    'civicrm_group_contact' => "civicrm_group_contact",
    'civicrm_membership' => "civicrm_membership",
    'civicrm_membership_payment' => "civicrm_membership_payment",
    'civicrm_membership_type' => "civicrm_membership_type",
    'civicrm_option_value' => "civicrm_option_value",
    'civicrm_participant' => "civicrm_participant",
    'civicrm_participant_payment' => "civicrm_participant_payment",
    'civicrm_participant_status_type' => "civicrm_participant_status_type",
    'civicrm_phone' => "civicrm_phone",
    'civicrm_price_field' => "civicrm_price_field",
    'civicrm_price_field_value' => "civicrm_price_field_value",
    'civicrm_price_set' => "civicrm_price_set",
    'civicrm_tag' => "civicrm_tag",
    ];

  \Drupal::service('config.factory')->getEditable('civicrm_entity.settings')
    ->set('filter_format', "plain_text")
    ->set('enabled_entity_types', $enabled_entity_type)
    ->set('disable_hooks', 0)
    ->save();

  \Drupal::service('router.builder')->rebuildIfNeeded();
  drupal_flush_all_caches();
}
