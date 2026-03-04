<?php

namespace Drupal\club_leader\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class ContactSettingsForm.
 *
 * @ingroup club
 */
class ContactSettingsForm extends FormBase {

  /**
   * Returns a unique string identifying the form.
   *
   * @return string
   *   The unique string identifying the form.
   */
  public function getFormId() {
    return 'club_contacts_settings';
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
    $form['contact_settings']['#markup'] =
	'Manage fields, form, and display for Club Leaders.
	<p>Base fields cannot be deleted so they are not listed on the <a href="/admin/structure/club_contacts_settings/fields">Manage fields</a> tab;
	they are listed on the <a href="/admin/structure/club_contacts_settings/form-display">Manage form display</a> and <a href="/admin/structure/club_contacts_settings/display">Manage display</a> tabs.
	<br/>New fields may be added on the <a href="/admin/structure/club_contacts_settings/fields">Manage fields</a> tab.</p>

	See the list of <a href="positions/list">club positions</a> with associated term type, term length, and Drupal role (permissions). This default Drupal Role  may be changed on individual member accounts.

	<p>Historic data are maintained in the system. Start and end dates determine which persons are on the list of <a href="#">current</a> versus <a href="#">past</a> leaders. These dates are also used to disable a person\'s administrative Drupal role when their term ends</p>';

    return $form;
  }

}
