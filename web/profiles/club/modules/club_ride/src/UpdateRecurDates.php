<?php

namespace Drupal\club_ride;

use Drupal\node\Entity\Node;
use Drupal\Component\Datetime\DateTimePlus;
use Drupal\Core\Datetime\DateHelper;

/**
 * Calendar is very slow with Smart Dates, faster with Drupal dates.
 * So insert one record per recurring ride date into Recurring Dates content type 
 *   title          = ride name
 *   field_recurid  = entity reference for recurring ride
 *   field_location = starting location ID 
 *   field_date     = Drupal date (instead of Smart date)
 *   field_cancel   = cancellation indicator is updated by UpdateCancellation.php
 *
 * In club_ride.module:
 *  hook_insert calls addDates() for new recurring dates
 *  hook_presave calls deleteDates() and addDates() for existing rides with updated Smart date
 */

class UpdateRecurDates {

	public static function deleteDates($nid) {
		$nodes = \Drupal::entityQuery("node")
		  ->accessCheck(FALSE)
  		->condition('field_recurid', $nid)
  		->execute();

		$storage_handler = \Drupal::entityTypeManager()->getStorage("node");

		if (!empty($nodes)) {
			foreach ($nodes as $nid) {
				$node = $storage_handler->load($nid);
				$node->delete($node);    
			}
		}
	}

	public static function addDates($node) {

		// CREATE one 'recurring_dates' node for each recurring_ride instance
		// with Drupal date instead of Smart Date timestamp. 
		
		$dates  = $node->field_datetime;
		$mindate = \Drupal::time()->getRequestTime() - 7776000; // now - 90 days

		foreach($dates as $date) {
			// Process dates if greater than today - 30 days.
			// Convert timestamp to Drupal date.

			if ($date->value > $mindate) {
				$datetime = DateTimePlus::createFromTimestamp($date->value,'UTC');
				$instance = $datetime->format('Y-m-d\TH:i:s');
				$dayofweek = DateHelper::dayOfWeek($instance);

				$new_node = Node::create([
					'type' => 'recurring_dates',
					'status' => '1',
					'langcode' => 'en',
					'title' => $node->title->value,
					'field_recurid' => $node->id(),
					'field_location' => $node->field_location->target_id,
					'field_date' => $instance,
					'field_dayofweek' => $dayofweek,
				]);
				$new_node->save();
			}
		}
	}
}

