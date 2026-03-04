<?php

namespace Drupal\club_report\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class PayPalSettingsForm.
 *
 * @ingroup club_report
 */
class PayPalSettingsForm extends FormBase {

  /**
   * Returns a unique string identifying the form.
   *
   * @return string
   *   The unique string identifying the form.
   */
  public function getFormId() {
    return 'paypal_settings';
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Empty implementation of the abstract submit class.
  }

  /**
   * {@inheritdoc}
   */

  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['paypal_settings']['#markup'] =
	'Manage fields, form, and display for PayPal IPN data.
	<p>Base fields are not listed on the <a href="/admin/structure/club_paypal_settings/fields">Manage fields</a> tab because they cannot be deleted;
	they are listed on the <a href="/admin/structure/club_paypal_settings/form-display">Manage form display</a> and <a href="/admin/structure/club_paypal_settings/display">Manage display</a> tabs.
	<br/>New fields may be added on the <a href="/admin/structure/club_paypal_settings/fields">Manage fields</a> tab.</p>';

    return $form;
  }

}
