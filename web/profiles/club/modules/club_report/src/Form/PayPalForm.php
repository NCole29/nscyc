<?php

namespace Drupal\club_report\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Language\Language;


/**
 * Form controller for the club_paypal entity edit forms.
 *
 * @ingroup club_report
 */
class PayPalForm extends ContentEntityForm {

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $entity = $this->entity;
    $schedule_date = strtotime($entity->field_schedule_date->value);
    $print_date = date('l, M d, Y', $schedule_date);

    $form['schedule_form']['#markup'] = 'Add ride leader for ' . $print_date;

    $form = parent::buildForm($form, $form_state);

    $form['langcode'] = [
      '#title' => $this->t('Language'),
      '#type' => 'language_select',
      '#default_value' => $entity->getUntranslated()->language()->getId(),
      '#languages' => Language::STATE_ALL,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $form_state->setRedirect('entity.paypal.collection');
    $entity = $this->getEntity();
    $entity->save();
  }

}
