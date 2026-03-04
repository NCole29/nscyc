<?php

namespace Drupal\permission_spreadsheet\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Defines a form that configures permission spreadsheet settings.
 */
class SettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'permission_spreadsheet_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'permission_spreadsheet.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('permission_spreadsheet.settings');

    $form['import'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Import'),
      '#tree' => TRUE,
    ];
    $form['import']['auto_preview'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Automatically show preview on upload'),
      '#default_value' => $config->get('import.auto_preview'),
    ];
    $form['import']['text_revoked'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Text for revoked'),
      '#description' => nl2br($this->t("Specify text that will be treated as revoked.\nEnter one text per line.")),
      '#default_value' => $config->get('import.text_revoked'),
    ];

    $form['export'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Export'),
      '#tree' => TRUE,
    ];
    $form['export']['tokens'] = [
      '#theme' => 'token_tree_link',
      '#global_types' => TRUE,
    ];
    $form['export']['filename'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Download filename'),
      '#description' => $this->t("Specify default filename (without extension) on download file. e.g., 'permissions'. You can use tokens."),
      '#required' => TRUE,
      '#default_value' => $config->get('export.filename'),
    ];
    $form['export']['sheet_title'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Sheet title'),
      '#description' => $this->t("Specify default title for Excel sheet. e.g., 'Permissions'. You can use tokens."),
      '#required' => TRUE,
      '#default_value' => $config->get('export.sheet_title'),
    ];
    $form['export']['text_granted'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Text for granted'),
      '#description' => $this->t("Specify output text for granted permission. e.g., 'Y'."),
      '#size' => 6,
      '#required' => TRUE,
      '#default_value' => $config->get('export.text_granted'),
    ];
    $form['export']['text_revoked'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Text for revoked'),
      '#description' => $this->t("Specify output text for revoked permission. e.g., 'N', can be empty."),
      '#size' => 6,
      '#default_value' => $config->get('export.text_revoked'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    $this->config('permission_spreadsheet.settings')
      ->set('import', $values['import'])
      ->set('export', $values['export'])
      ->save();

    parent::submitForm($form, $form_state);
  }

}
