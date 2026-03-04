<?php
/**
 * @file
 * Contains \Drupal\club_ride\Form\NonmemberForm.
 */
namespace Drupal\club_ride\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\node\Entity\Node;

class NonmemberForm extends FormBase {
  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'nonmember_form';
  }

  public function buildForm(array $form, FormStateInterface $form_state) {
    $roles = \Drupal::currentUser()->getRoles();

    // Applies to all forms.
    $form['instructions'] = [
      '#type' => 'markup',
      '#markup' => "<h4>Rides scheduled within the <u>last 5 days</u> may be selected.</h4>",
    ];
    $form['layout'] = [
      '#type' => 'fieldset',
    ];

    // Admin - 1 form field to select ride or recurring ride.
    if (in_array('administrator',$roles) or in_array('civicrm',$roles)) {
      $display = 'all_past_rides';

      $form['layout']['ride'] = [
        '#type' => 'entity_autocomplete',
        '#title' => t('Ride name'),
        '#description' => t('Enter part of the ride name and select from list. Recurring rides appear first, alphabetically, followed by non-recurring rides.'),
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
      $display1 = 'own_past_rides'; 
      $display2 = 'own_past_recurring'; 

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
    $form['actions']['#type'] = 'actions';
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Submit'),
      '#button_type' => 'primary',
    ];
    return $form;
  }

  public function validateForm(array &$form, FormStateInterface $form_state) {
    // check that one and only one field was filled
  }

  public function submitForm(array &$form, FormStateInterface $form_state) {
    if ($form_state->getValue('ride')) {
      $node = Node::load($form_state->getValue('ride'));
    } else {
      $node = Node::load($form_state->getValue('recur_ride'));
    }

    $ride_name = $node->getTitle();
    $ride_date = $node->field_date->value;
    
    //\Drupal::messenger()->addMessage(t("Bundle: $bundle, $rideName ($ride_id) $ride_date"));

    $url = \Drupal\core\Url::fromUserInput("/enter-waivers?civicrm_1_activity_1_activity_subject=$ride_name&civicrm_1_activity_1_activity_activity_date_time=$ride_date");
    $form_state->setRedirectUrl($url);
  }
}