<?php

namespace Drupal\club_ride;

use Drupal\node\Entity\Node;

/**
 * Set coauthors equal to ride leaders.
 * Except, if the node Owner is a ride leader, do not include as coauthor.
 *
 * See club_ride.module, hook_presave.
 */

class UpdateCoauthors {

	public static function deleteCoauthors($nid) {
		$db = \Drupal::database();
		$db->query("DELETE FROM {node__co_authors} WHERE entity_id = :nid", [':nid' => $nid,]);
		$db->query("DELETE FROM {node_revision__co_authors} WHERE entity_id = :nid", [':nid' => $nid,]);

		echo 'Deleted co-authors for node: ' . $nid;
	}

	public static function addCoauthors($node, $leaders) {
		$db = \Drupal::database();
		$owner = $node->getOwnerId();
		$delta = 0;

		echo '<br><br>Adding co-authors for node: ' . $node->id() . ' bundle: ' . $node->bundle();

		kint($leaders);

		foreach($leaders as $leader) {
			// Each ride leader except ride owner (author) is added as a co-author.
			$leader = reset($leader); // Convert array element to string.

			if ($leader != $owner) {

				echo "<br>Leader $leader is not the owner ($owner)";

				$fields = array(
					'bundle' => $node->bundle(),
					'deleted' => 0,
					'entity_id' => $node->id(),
					'revision_id' => $node->get('vid')->value,
					'langcode' => $node->langcode->value,
					'delta' => $delta,
					'co_authors_target_id' => $leader,
				);

				kint($fields);
				/*
				$db->insert('node__co_authors')
					->fields($fields)
					->execute();
				$db->insert('node_revision__co_authors')
					->fields($fields)
					->execute();
				*/
			} 
		}
	}
}

