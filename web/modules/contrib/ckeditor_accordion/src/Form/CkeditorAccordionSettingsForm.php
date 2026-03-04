<?php

namespace Drupal\ckeditor_accordion\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * A class define for the ckeditor accordion form settings.
 *
 * @package Drupal\ckeditor_accordion\Form
 */
class CkeditorAccordionSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'ckeditor_accordion_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  public function getEditableConfigNames() {
    return ['ckeditor_accordion.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('ckeditor_accordion.settings');

    $form['collapse_all'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Collapse all tabs by default'),
      '#return_value' => 1,
      '#default_value' => $config->get('collapse_all') ?: 0,
    ];

    $form['keep_rows_open'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Keep accordion rows open when opening another one'),
      '#return_value' => 1,
      '#default_value' => $config->get('keep_rows_open') ?: 0,
    ];

    $form['animate_accordion_toggle'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Animate accordion open / close'),
      '#return_value' => 1,
      '#default_value' => $config->get('animate_accordion_toggle') ?? 1,
    ];

    $form['open_tabs_with_hash'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Open tabs with hash using anchor links or on page load'),
      '#return_value' => 1,
      '#default_value' => $config->get('open_tabs_with_hash') ?? 0,
      '#description' => $this->t('With this, your accordion row titles are hashed and used as links to the row. For example, if your accordion title is "Frequently Asked Questions", you can link to it using href="#FrequentlyAskedQuestions".'),
    ];

    $form['allow_html_in_titles'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Allow HTML in row titles'),
      '#default_value' => $config->get('allow_html_in_titles') ?? 0,
      '#description' => $this->t('This is useful if you want to use icons or other HTML elements in your accordion titles.'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('ckeditor_accordion.settings');
    $values = $form_state->getValues();

    $config->set('collapse_all', $values['collapse_all']);
    $config->set('keep_rows_open', $values['keep_rows_open']);
    $config->set('animate_accordion_toggle', $values['animate_accordion_toggle']);
    $config->set('open_tabs_with_hash', $values['open_tabs_with_hash']);
    $config->set('allow_html_in_titles', $values['allow_html_in_titles']);
    $config->save();

    parent::submitForm($form, $form_state);
  }

}
