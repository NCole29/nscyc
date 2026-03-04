<?php
/**
 * @file
 * Contains \Drupal\club_ride\Form\RideCancellationForm.
 */
namespace Drupal\club_ride\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\node\Entity\Node;

class RideCancellationForm extends FormBase {
  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'cancel_ride_form';
  }

  public function buildForm(array $form, FormStateInterface $form_state) {
    $roles = \Drupal::currentUser()->getRoles();

    // Applies to all forms.
    $form['instructions'] = [
      '#type' => 'markup',
      '#markup' => "<h4>Rides scheduled within the <u>next</u> two weeks may be cancelled.</h4>"
      . "To <strong>undo</strong> a cancellation, select the ride and uncheck the cancel box."
      . "<p><strong>** Cancelled**</strong> will immediately appear on the calendar and the home page list of rides.<br>A cancellation notice is displayed at the top of the home page until 4 hours after the ride start (and no earlier than 4 days prior to ride start).",
    ];
      $form['layout'] = [
      '#type' => 'fieldset',
    ];

    // Admin - 1 form field to select ride or recurring ride.
    if (in_array('administrator',$roles) or in_array('rides_coordinator',$roles)) {
      $display = 'all_next_rides';

      $form['layout']['ride'] = [
        '#type' => 'entity_autocomplete',
        '#title' => t('Ride name'),
        '#description' => t('Enter part of the ride name and select from list.'),
        '#target_type' => 'node',
        '#required' => TRUE,
        '#selection_handler' => 'views',
        '#selection_settings' => [
          'view' => [
            'view_name' => 'ride_dates',
            'display_name' => $display,
            'arguments' => [],
          ],
        ],
      ];
    }
    // Ride leaders - 2 form fields for rides and recurring (due to join of recurring_dates with recurring_rides).
    else if(in_array('ride_leader',$roles)) {
      $display1 = 'own_next_rides'; 
      $display2 = 'own_next_recurring'; 

      $form['layout']['select_one'] = [
        '#type' => 'markup',
        '#markup' => "<p>Select one ride. Separate fields are needed due to how recurring dates are stored.",
      ];
      $form['layout']['ride'] = [
        '#type' => 'entity_autocomplete',
        '#title' => t('Non-recurring ride'),
        '#description' => t('Enter part of the ride name and select from list.'),
        '#target_type' => 'node',
        '#required' => FALSE,
        '#selection_handler' => 'views',
        '#selection_settings' => [
          'view' => [
            'view_name' => 'ride_dates',
            'display_name' => $display1,
            'arguments' => [],
          ],
        ],
      ];
      $form['layout']['recur_ride'] = [
        '#type' => 'entity_autocomplete',
        '#title' => t('Recurring ride'),
        '#description' => t('Enter part of the ride name and select from list.'),
        '#target_type' => 'node',
        '#required' => FALSE,
        '#selection_handler' => 'views',
        '#selection_settings' => [
          'view' => [
            'view_name' => 'ride_dates',
            'display_name' => $display2,
            'arguments' => [],
          ],
        ],
      ];
    }

    // Applies to all forms.
    $form['layout']['cancel'] = [
      '#type' => 'checkbox',
      '#title' => t('Cancel'),
      '#default_value' => 1,
    ];
    $form['actions']['#type'] = 'actions';
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Save'),
      '#button_type' => 'primary',
    ];
    return $form;
   }
  
  public function validateForm(array &$form, FormStateInterface $form_state) {
  }

  public function submitForm(array &$form, FormStateInterface $form_state) {

    if ($form_state->getValue('ride')) {
      $node = Node::load($form_state->getValue('ride'));
    } else {
      $node = Node::load($form_state->getValue('recur_ride'));
    }
    $cancel = $form_state->getValue('cancel');

    $node->set('field_cancel', $cancel);
    $node->save();

    $rideName = $node->getTitle();

    if ($cancel == 1) {
     \Drupal::messenger()->addMessage(t("$rideName has been cancelled"));
    } else {
      \Drupal::messenger()->addMessage(t("$rideName cancellation is undone"));
    }
    $form_state->setRedirect('<front>');
  }
}