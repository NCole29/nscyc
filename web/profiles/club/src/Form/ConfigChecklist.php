<?php

/**
 * Provides consolidated configuration form to update Drupal and CiviCRM.
 *
 * @internal
 */
namespace Drupal\club\Form;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Site\Settings;
use Drupal\user\UserStorageInterface;
use Drupal\user\UserInterface;

class ConfigChecklist extends ConfigFormBase {
 /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'club.adminsettings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'config_checklist';
  }

   /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $site = $this->config('system.site');
    $rwgps = $this->config('club.adminsettings');
    $google = $this->config('geocoder.geocoder_provider.googlemaps');

    $form['#title'] = $this->t('Configuration Checklist');

    // Site information.
    $form['site_information'] = [
      '#type' => 'details',
      '#title' => $this->t('Site information'),
      '#access' => empty($install_state['config_install_path']),
      '#open' => TRUE,
    ];
      $form['site_information']['name'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Site name'),
        '#description' => $this->t('Site name is updated in Drupal and CiviCRM.'),
        '#required' => TRUE,
        '#weight' => -20,
        '#default_value' => $site->get('name'),
      ];
      // Use the default site mail if one is already configured, or fall back to
      // PHP's configured sendmail_from.
      $default_site_mail = $site->get('mail') ?: ini_get('sendmail_from');
      $form['site_information']['site_mail'] = [
        '#type' => 'email',
        '#title' => $this->t('Site email address'),
        '#default_value' => $default_site_mail,
        '#description' => $this->t("Site email address is updated in Drupal and CiviCRM. Automated emails, such as registration information, will be sent from this address. Use an address ending in your site's domain to help prevent these emails from being flagged as spam."),
        '#required' => TRUE,
        '#weight' => -15,
        '#access' => empty($install_state['config_install_path']),
      ];
    // Site administrator account.
    $form['admin_account'] = [
      '#type' => 'details',
      '#title' => $this->t('Site administrator account'),
      '#description' => $this->t("This account has role of 'Site administrator'."),
      '#open' => TRUE,
    ];
    $form['admin_account']['account']['name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Username'),
      '#maxlength' => UserInterface::USERNAME_MAX_LENGTH,
      '#description' => $this->t("To assign this role to additional persons, go to the PEOPLE page.<br>Username should be the person's name. They may also login with their email address."),
      '#required' => TRUE,
      '#attributes' => ['class' => ['username']],
    ];
    $form['admin_account']['account']['pass'] = [
      '#type' => 'password_confirm',
      '#required' => TRUE,
      '#size' => 25,
    ];
    $form['admin_account']['account']['#tree'] = TRUE;
    $form['admin_account']['account']['mail'] = [
      '#type' => 'email',
      '#title' => $this->t('Email address'),
      '#required' => TRUE,
    ];
    // Regional settings.
    $form['regional_settings'] = [
      '#type' => 'details',
      '#title' => $this->t('Default time zone'),
      '#access' => empty($install_state['config_install_path']),
      '#open' => TRUE,
    ];
    // Use the default site timezone if one is already configured, or fall back
    // to the system timezone if set (and avoid throwing a warning in
    // PHP >=5.4).
    $default_timezone = $this->config('system.date')->get('timezone.default') ?: @date_default_timezone_get();
    $form['regional_settings']['date_default_timezone'] = [
      '#type' => 'select',
      '#title' => $this->t('Default time zone'),
      '#default_value' => $default_timezone,
      '#options' => system_time_zones(NULL, TRUE),
      '#weight' => 5,
      '#attributes' => ['class' => ['timezone-detect']],
      '#access' => empty($install_state['config_install_path']),
    ];

    $form['club_settings'] = [
      '#type' => 'details',
      '#title' => $this->t('Club accounts'),
      '#access' => empty($install_state['config_install_path']),
      '#open' => TRUE,
    ];
    $form['club_settings']['rwgps_api'] = [
      '#type' => 'textfield',
      '#title' => $this->t('RWGPS API key'),
      '#description' => $this->t('Paste the RWGPS API key to enable retrieval of RWGPS route information.'),
      '#default_value' => $rwgps->get('rwgps_api'),
    ];
    $form['club_settings']['google_api'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Google Maps API key'),
      '#description' => $this->t('Paste the RWGPS API key to enable retrieval of RWGPS route information.'),
      '#default_value' => $google->get('configuration.apiKey'),
    ];

    return parent::buildForm($form, $form_state);
  }

   /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    $this->config('club.adminsettings')
      ->set('rwgps_api', $form_state->getValue('rwgps_api'))
      ->save();

    $site_settings = ['name', 'mail'];

    foreach ($site_settings as $setting) {
      $this->config('system.site')
      ->set($setting, $form_state->getValue($setting))
      ->save();
    }

    if ($form_state->getValue('google_api')) {
      $google = \Drupal::service('config.factory')
        ->getEditable('geocoder.geocoder_provider.googlemaps');

      $google
        ->set('configuration.apiKey', $form_state->getValue('google_api'))
        ->save();
    }
  }
}

/*
function form_example_form_validate($form, &$form_state) {
  if (!($form_state['values']['price'] > 0)){
    form_set_error('price', t('Price must be a positive number.'));
  }
}
*/
