<?php

namespace Drupal\shortcutperrole\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\shortcut\ShortcutSetStorageInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\user\Entity\Role;

/**
 * Configure Shortcut per Role settings for this site.
 */
class ShortcutPerRoleSettingsForm extends ConfigFormBase {

  /**
   * The shortcut set storage.
   *
   * @var \Drupal\shortcut\ShortcutSetStorageInterface
   */
  protected $shortcutSetStorage;

  /**
   * Constructs a SwitchShortcutSet object.
   *
   * @param \Drupal\shortcut\ShortcutSetStorageInterface $shortcut_set_storage
   *   The shortcut set storage.
   */
  public function __construct(ShortcutSetStorageInterface $shortcut_set_storage) {
    $this->shortcutSetStorage = $shortcut_set_storage;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager')->getStorage('shortcut_set')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'shortcutperrole_admin_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['shortcutperrole.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('shortcutperrole.settings');
    $roles = Role::loadMultiple();
    $sets = $this->shortcutSetStorage->loadMultiple();

    $options = [];
    foreach ($sets as $name => $set) {
      $options[$name] = $set->label();
    }

    $form['#title'] = $this->t('Assign Shortcuts for Role');

    foreach ($roles as $rid => $role) {
      $default_value_ss = $config->get('role.' . $rid);
      $form['shortcutperrole'][$rid] = [
        '#type' => 'select',
        '#default_value' => $default_value_ss ?? 'default',
        '#options' => $options,
        '#title' => $role->label(),
      ];
    }

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $form_state->cleanValues();
    foreach ($form_state->getValues() as $role => $set) {
      $this->config('shortcutperrole.settings')
        ->set('role.' . $role, $set)
        ->save();
    }

    parent::submitForm($form, $form_state);
  }

}
