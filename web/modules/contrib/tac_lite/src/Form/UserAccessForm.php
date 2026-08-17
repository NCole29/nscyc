<?php
namespace Drupal\tac_lite\Form;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\taxonomy\Entity\Vocabulary;
use Drupal\user\UserInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Builds the form for User Access.
 */
class UserAccessForm extends ConfigFormBase {

  /**
   * The user id.
   *
   * @var string|int|null
   */
  protected $uid;

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'tac_lite_user_access_form';
  }

  /**
   * {@inheritdoc}
   */
  public function getEditableConfigNames() {
    return ['tac_lite.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, ?UserInterface $user = NULL) {
    if (!$user) {
      throw new NotFoundHttpException();
    }

    $this->uid = $user->id();
    $vocabularies = Vocabulary::loadMultiple();
    $config_factory = $this->configFactory->get('tac_lite.settings');
    $vids = $config_factory->get('tac_lite_categories') ?: [];
    $schemes = $config_factory->get('tac_lite_schemes');
    $user_data_service = \Drupal::service('user.data');

    if ($vids) {
      for ($i = 1; $i <= $schemes; $i++) {
        $config = SchemeForm::tacLiteConfig($i);
        if ($config['name']) {
          $perms = $config['perms'];

          if ($config['term_visibility']) {
            $perms[] = $this->t('term visibility');
          }

          $form['tac_lite'][$config['realm']] = [
            '#type' => 'details',
            '#title' => $config['name'],
            '#description' => $this->t('This scheme controls %perms.', ['%perms' => implode(' and ', $perms)]),
            '#open' => TRUE,
            '#tree' => TRUE,
          ];

          // Fetch user data ONCE per scheme, not per vocabulary.
          $all_scheme_data = $user_data_service->get('tac_lite', $user->id(), 'tac_lite_scheme_' . $i) ?: [];

          foreach ($vids as $vid) {
            if (!isset($vocabularies[$vid])) continue;

            $v = $vocabularies[$vid];
            // Use the data we already fetched above.
            $default_values = $all_scheme_data[$vid] ?? [];
            $form['tac_lite'][$config['realm']][$vid] = SchemeForm::tacLiteTermSelect($v, $default_values);
            $form['tac_lite'][$config['realm']][$vid]['#description'] = $this->t('Grant permission to this user by selecting terms.  Note that permissions are in addition to those granted based on user roles.');
          }
        }
      }
      $form['tac_lite'][0] = [
        '#type' => 'markup',
        '#markup' => '<p>' . $this->t('You may grant this user access based on the schemes and terms below.  These permissions are in addition to <a href=":url">role based grants on scheme settings pages</a>.',
          [':url' => Url::fromRoute('tac_lite.scheme_1')->toString()]) . "</p>\n",
        '#weight' => -1,
      ];
    }
    else {
      $form['tac_lite_help'] = [
        '#type' => 'markup',
        '#prefix' => '<p>',
        '#suffix' => '</p>',
        '#markup' => $this->t('First, select one or more vocabularies on the <a href=:url>settings page</a>. Then, return to this page to complete configuration.', [':url' => Url::fromRoute('tac_lite.administration')->toString()]),
      ];
    }

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $uid = $this->uid;
    $settings = $this->config('tac_lite.settings');
    $schemes = $settings->get('tac_lite_schemes');
    $user_data = \Drupal::service('user.data');

    for ($i = 1; $i <= $schemes; $i++) {
      $scheme_config = SchemeForm::tacLiteConfig($i);
      if ($scheme_config['name']) {
        $values = $form_state->getValue($scheme_config['realm']);

        if (is_array($values)) {
          foreach ($values as $vid => $tids) {
            // Remove the '<none>' (0) option and filter empty values.
            $values[$vid] = array_filter($tids);
          }
        }

        $user_data->set('tac_lite', $uid, $scheme_config['realm'], $values);
      }
    }

    // In Drupal 11+, if you change user access, you may need
    // to invalidate node access cache tags for this specific user.
    Cache::invalidateTags(['user:' . $uid]);

    parent::submitForm($form, $form_state);
  }

}
