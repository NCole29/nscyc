<?php

/**
 * Triggers installation of club_civicrm via button on home page.
 * Why? because if civicrm_entity module is installed during install, warnings are displayed.
 * After Drupal install is complete, errors & warnings are not displayed.
 *
 * @internal
 */
namespace Drupal\club\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\block\Entity\Block;

class CiviComplete extends ConfigFormBase {
  /**
   * Config settings.
   *
   * @var string
   */
  const SETTINGS = 'complete.install';

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'complete_install';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      static::SETTINGS,
    ];
  }

   /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config(static::SETTINGS);

    $form['#title'] = $this->t('Complete Installation');

    $form['installation']['#markup'] =
      "<h3>Complete the Installation</h3>
      <p>Click Submit to install custom CiviCRM configuration.
      <br>Please be patient as this may take several minutes.</p>";

    return parent::buildForm($form, $form_state);
}

 /**
  * {@inheritdoc}
  */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    $module_service = \Drupal::service('module_installer');
    $module_service->install(['civicrm_entity', 'club_civicrm']);

    // Disable the "Complete installation" block after form is submitted.
    $block = Block::load('civicomplete');
    $block->setRegion('auto_hidden_block')->save();

    //$form_state->setRedirect('entity.node.canonical', ['node' => 1]);
    $form_state->setRedirect('<front>');
    $form['actions']['submit']['#value'] = t('Submit');
    parent::submitForm($form, $form_state);
  }
}
