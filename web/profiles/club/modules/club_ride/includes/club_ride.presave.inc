<?php

use Drupal\club\ImageCategory;
use Drupal\club_ride\UpdateRecurDates;
use Drupal\Core\Datetime\DateHelper;
use Drupal\Core\Entity\EntityInterface;
use Drupal\media\Entity\Media;

/**
 * Implements hook_node_presave().
 *
 */
function club_ride_node_presave(EntityInterface $node) {
  $bundle = $node->bundle();

  if ($bundle == 'ride') {
    $cancel = $node->field_cancel->value;
    $node->field_dayofweek = DateHelper::dayOfWeek($node->field_date->value);

    // Clear registration fields depending on registration type (field_registration)
    // 0 = No registration, 1 = Free, 2 = Paid webform, 3 = Paid link
    $regType = $node->get('field_registration')->value;

    if ($regType <> NULL) {
      if($regType <> 1 and $node->hasField('field_registration_free_form')) {
          $node->field_registration_free_form->target_id = NULL;
      }
      if($regType <> 2 and $node->hasField('field_registration_form')) {
        $i = 0;
        while (isset($node->get('field_registration_form')[$i]->target_id)) {
          $node->get('field_registration_form')[$i]->target_id = NULL;
          $i++;
        }
      }
      if($regType <> 3 and $node->hasField('field_registration_link')) {
        $i = 0;
        if (isset($node->get('field_registration_link')[$i])) {
          $node->get('field_registration_link')[$i]->uri = NULL;
          $i++;
        }
      }
    }
  }

  // If Ride or Recurring ride. If cancelling ride, bypass presave processing.
  if(($bundle == 'ride' and $cancel <> 1) or $bundle == 'recurring_ride') {
    $nid = $node->id();

    /*
    // Set node AUTHOR equal to the first listed RIDE LEADER.
    if ( !empty($node->field_ride_leader[0]->target_id) ) {
      $ride_leader = $node->field_ride_leader[0]->target_id;
      $node->setOwnerId($ride_leader);
    }
    */

    // If MULTIPLE TIMES is unchecked, set TIME to NULL.
    if ($node->field_multiple_times->value == FALSE) {
      foreach ($node->field_time as $item) {
        $item->value = NULL;
        }
    }

    // If RIDE TYPE is checked and not Gravel, set Gravel category to NULL.
    if ($node->field_gravel_category->target_id > 0 and $node->field_activity->target_id <> 2) {
      $node->field_gravel_category->target_id = NULL;
    }

    // If Lunch is not "Enter below" clear lunch location.
    if ($node->field_lunch_same->value <> 1) {
      $node->field_lunch_place->target_id = NULL;
    }
    
    // Load media item (limit 1). Fill Name with Alt text and Image_category with node type.
    $mid = $node->field_ride_picture->target_id;

    if ($mid) {
      $media = Media::load($mid);
																  
      if($media) {
          $alt = $media->get('thumbnail')[0]->get('alt')->getString();

          // Get the taxonomy term id for the node type to fill the image_category field.
          $image_cat = imagecategory::getImageCatTid($node);

          if ($alt) {
            $media->set('name',$alt)->save();
          }
          if ($image_cat) {
            $media->set('field_image_category', $image_cat)->save();
          }
        }
    }

    $ride_start = $node->field_location->target_id;

    // Process RWGPS routes.
    if ($node->field_rwgps_routes) {

      // When Ride is published, call RWGPS API and store data in club_route table.
      // Ride leaders can save draft Ride pages but not publish, so they can't put junk in route table.
      if ($node->status->value == 1) {
        $rwgps = \Drupal::getContainer()->get(get_rwgps_client::class);

        foreach ($node->field_rwgps_routes as $routeId) {
          // Trim to remove whitespace then call RouteInfo to save or update route entity.
          $routeId->target_id = trim($routeId->target_id);
          $rwgps->getRouteInfo($routeId->target_id, $ride_start);
        }
      }
    }

    // Create or update RECURRING DATES content type when RECURRING RIDE is edited.
    //  If recurring_dates don't exist -> add.
    //  If recurring_dates exist but recurring_ride dates have changed -> delete and recreate.
    if ($bundle == 'recurring_ride' and isset($nid) ) {

      // Do recurring_dates exist?
      $rdates = \Drupal::entityQuery('node')
        ->accessCheck(FALSE)
        ->condition('type','recurring_dates')
        ->condition('field_recurid', $nid)
        ->execute();

      if (count($rdates) < 1) {
        $nomatch = 0;
        UpdateRecurDates::addDates($node);
      } 
      else {
        // Have the number of recurring_ride dates changed?  
        $orig = $node->original->get('field_datetime');
        $new  = $node->field_datetime;

        $num_orig = count($orig);
        $num_new = count($new);

        if ($num_orig <> $num_new) {
          $nomatch = 1;
        } else {
          // If same number of dates, compare each date.
          $nomatch = 0;
          for ($i = 0; $i < $num_orig; $i++) {
            if ($new[$i]->value <> $orig[$i]->value) {
              $nomatch++;
            } 
         }
       }
      }

      // If change in dates, call updateRecurDates to delete old and add new.
      if ($nomatch > 0) {
        UpdateRecurDates::deleteDates($nid);
        UpdateRecurDates::addDates($node);
      } 
    } // end check for date change on updated recurring ride
  } // end IF ride or recurring_ride
} // end hook_presave

