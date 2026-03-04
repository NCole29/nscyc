<?php

use Drupal\Core\Form\FormStateInterface;

/**
 * Implements hook_form_FORM_alter().
 */
function club_ride_form_node_form_alter(&$form, FormStateInterface &$form_state, $form_id) {
  //\Drupal::messenger()->addStatus('Form ID: ' . $form_id);

    if ( in_array($form_id, ['node_ride_form',
                          'node_ride_edit_form',
                          'node_ride_quick_node_clone_form',
                          'node_recurring_ride_form',
                          'node_recurring_ride_edit_form',
                          'node_recurring_ride_quick_node_clone_form'] )) {


    // Time: hide field if multiple times is unchecked.
    $form['field_time']['#states'] = [
      'invisible' => [
        ':input[name^="field_multiple_times"]' => ['unchecked' => TRUE],
      ],
    ];

    // Gravel categories: hide field if ride type is not Gravel.
    $form['field_gravel_category']['#states'] = [
      'visible' => [
        ':input[name^="field_activity"]' =>  ['value' => '2'],
      ],
    ];

    // Lunch place.
    $form['field_lunch_place']['#states'] = [
      'visible' => [
        ':input[name^="field_lunch_same"]' =>  ['value' => '1'],
      ],
    ];
    
    // If master ride, hide field_based_on.
    $form['field_based_on']['#states'] = [
      'invisible' => [
        ':input[name^="field_master"]' =>  ['value' => '1'],
      ],
    ];
    
    // Ride leader: change button text to "Add another leader".
    $form['field_ride_leader']['widget']['add_more']['#value'] = "Add another leader";

    // Ride Leader: hide field if show_and_go is checked.
    // [attribute^='value'] where "^=" denotes "starts with"
    $form['field_ride_leader']['#states'] = [
      'invisible' => [
        ':input[name^="field_show_and_go"]' => ['checked' => TRUE],
      ],
    ];

    // Disable Route field if RWGPS API key is empty.
    $rwgps = \Drupal::config('club.adminsettings')->get('rwgps_api');
    if (!$rwgps) {
      $form['field_rwgps_routes']['#disabled'] = TRUE;
      $form['field_rwgps_routes']['widget']['#title'] = "RWGPS Routes - Please enter a <a href='/admin/config/club/rwgps'>RWGPS API key</a> to enable this field.";
    }

		// If Recurring ride.
    if (in_array($form_id, ['node_recurring_ride_form','node_recurring_ride_edit_form',
								            'node_recurring_ride_quick_node_clone_form'])) {
		  // Remove "Add another" date.
		  unset($form['field_datetime']['widget']['add_more']);
		}
    else if (in_array($form_id, ['node_ride_form','node_ride_edit_form',
                                'node_ride_quick_node_clone_form',])) {
          
      // Show free registration form, paid registration form, or registration link - depending on type of event.
      $form['field_registration_free_form']['#states'] = [
        'visible' => [
          ':input[name="field_registration"]' => ['value' => 1],
        ],
      ];
      $form['field_registration_form']['#states'] = [
        'visible' => [
          ':input[name="field_registration"]' => ['value' => 2],
        ],
      ];
      $form['field_registration_link']['#states'] = [
        'visible' => [
          ':input[name="field_registration"]' => ['value' => 3],
        ],
      ];

      // Show "Results are visible to" if free event or paid webform.
      $form['field_results_access']['#states'] = [
        'visible' => [
          [':input[name="field_registration"]' => ['value' => '1']],
          'or',
          [':input[name="field_registration"]' => ['value' => '2']],
        ],        
      ];
      
      // Remove instructions "begin typing" ...
      $form['field_registration_link']['widget'][0]['uri']['#description'] = NULL;

      if ($form_id == 'node_ride_form') {
        // Disable cancel checkbox when creating new ride; keep for clone to allow it unchecked.
        $form['field_cancel']['#disabled'] = TRUE;
      }
    }
  }
}
