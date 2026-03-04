<?php

namespace Drupal\club_leader;

class AddYear {
	/**
	 * Add year of start date.
   * re: fix view so all start dates are in same group.
	 */
	public static function createYear() {

	  // Get an array of  ids.
	  $ids = \Drupal::entityQuery('club_contacts')
		->accessCheck(FALSE)
		->execute();

		$db = \Drupal::database();

		foreach($ids as $id) {

			$start = $db->query("SELECT entity_id, field_start_date_value  FROM club_contacts__field_start_date WHERE entity_id = :id", [':id' => $id,])
			->fetch();

      $year = substr($start->field_start_date_value,0,4);

      //echo "<br>ID: $id  Start date: $start->field_start_date_value  Year: $year";

      $db->update('club_contacts')
      ->condition('contact_id', $id, '=')
      ->fields(['year' => $year])
      ->execute();      
		}

	}
}
