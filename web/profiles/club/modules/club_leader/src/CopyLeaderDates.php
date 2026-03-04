<?php

namespace Drupal\club_leader;

class CopyLeaderDates {
	/**
	 * Copy Start and End dates from base table (club_contacts) to field tables.
   * re: cannot filter dates in views and cannot figure out views_hook_data.
	 */
	public static function copyDates() {

	  // Get an array of  ids.
	  $ids = \Drupal::entityQuery('club_contacts')
		->accessCheck(FALSE)
		->execute();

		$db = \Drupal::database();

		foreach($ids as $id) {

			$leader = $db->query("SELECT contact_id, langcode, start_date, end_date FROM club_contacts WHERE contact_id = :id", [':id' => $id,])
			->fetch();

      $start = array(
        'bundle' => 'club_contacts',
        'deleted' => 0,
        'entity_id' => $id,
        'revision_id' => $id,
        'langcode' => $leader->langcode,
        'delta' => 0,
        'field_start_date_value' => $leader->start_date,
      );
      $end = array(
        'bundle' => 'club_contacts',
        'deleted' => 0,
        'entity_id' => $id,
        'revision_id' => $id,
        'langcode' => $leader->langcode,
        'delta' => 0,
        'field_end_date_value' => $leader->end_date,
      );

      $db->insert('club_contacts__field_start_date')
        ->fields($start)
        ->execute();
      $db->insert('club_contacts__field_end_date')
        ->fields($end)
        ->execute();
		}

	}
}
