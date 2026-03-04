<?php

/**
 * @file
 * Implements hook_form_FORM_ID_alter() to set defaults in the site configuration form.
 * Enables CiviCRM modules via hook_install_tasks (to see this listed on left sidebar).
 */

use Drupal\club\ImportYaml;
use Drupal\contact\Entity\ContactForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\Core\Routing\TrustedRedirectResponse;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\RedirectResponse;

function club_form_install_configure_form_alter(&$form, FormStateInterface $form_state) {
  // Add message to configuration form: CiviCRM install takes time.
  $form['site_information']['civi_install_info'] = [
    '#type' => 'details',
    '#title' => t('CiviCRM Installation'),
    '#description' => t('CiviCRM is installed after you submit this form.<br>Installation may take several minutes.<br>Please be patient.'),
    '#open' => TRUE,
    '#weight' => -9,
  ];
  // TEMPORARY FILL
  $form['site_information']['site_name']['#default_value'] = t('Site with PHP 7.4 & CiviCRM 5.55');
  $form['site_information']['site_mail']['#default_value'] = 'info@clubdemo.org';
  $form['admin_account']['account']['mail']['#default_value'] = 'admin@clubdemo.com';

  // Alter the site configuration form. Admin account name cannot be changed.
  $form['admin_account']['account']['name']['#default_value'] = 'admin';
  $form['admin_account']['account']['name']['#attributes']['disabled'] = TRUE;
  $form['regional_settings']['site_default_country']['#default_value'] = 'US';
  $form['regional_settings']['date_default_timezone']['#default_value'] = 'America/New York';
}

function club_form_install_configure_submit($form, FormStateInterface $form_state) {
  // Submission handler to sync the contact.form.feedback recipient.
  $site_mail = $form_state->getValue('site_mail');
  ContactForm::load('feedback')->setRecipients([$site_mail])->trustData()->save();
}

/**
 * Implements hook_install_tasks().
 */
function club_install_tasks(&$install_state) {
  $tasks = [
    'club_misc' => [
      'display_name' => t('Fills contact form recipient email with site email. Replaces webform node configs.'),
      'display' => FALSE,
      'type' => 'normal',
    ],
/*
    'club_civi_module_installs' => [
      'display_name' => t('Install CiviCRM and related modules'),
      'display' => TRUE,
      'type' => 'batch',
    ],
*/
    'club_clear_cache' => [
      'display_name' => t('Clear cache'),
      'display' => TRUE,
      'type' => 'normal',
    ],
  ];
  return $tasks;
}

/**
 * Update contact form recipient email to site email.
 * Replace webform_node form and display.
 */
function club_misc() {
  // Website contact form and membership contact form.
  $site_email = \Drupal::config('system.site')->get('mail');

  $config = \Drupal::configFactory();
  $config->getEditable('contact.form.feedback')
    ->set('recipients', [$site_email])
    ->save(TRUE);
  $config->getEditable('contact.form.membership')
    ->set('recipients', [$site_email])
    ->save(TRUE);

  // Replace form and display config - did not work as part of module.

  $files = [
    'core.entity_form_display.node.webform.default',
    'core.entity_view_display.node.webform.default',
    'core.entity_view_display.node.webform.teaser',
  ];
  importyaml::readNewYaml('club_event', $files);
}

/**
 * Install CiviCRM and related modules.
 */
function club_civi_module_installs() {
  set_time_limit(0);

  // CiviCRM requires a completed Drupal install.
  \Drupal::service('module_installer')->install([
    'civicrm',
    'civicrm_member_roles',
    'webform_civicrm',
  ]);
}

/**
 * Clear cache on installation and redirect to install civicrm_entity & club_civicrm.
 * re: civicrm_entity throws warnings which are visible if done before Drupal install is complete.
 *
 */
function club_clear_cache() {
  // Rebuild routes and permissions with node_access_rebuild, and clear CiviCRM cache.
  \Drupal::service('router.builder')->rebuildIfNeeded();
  node_access_rebuild();
  drupal_flush_all_caches();
}
