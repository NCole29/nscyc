<?php

namespace Drupal\club;

use Drupal\node\Entity\Node;

/**
 * Get the image category taxonomy term id (TID) corresponding to the node type.
 * This class is called in club_page.module
 */
class ImageCategory {

  public static function getImageCatTid($node) {
    $node_type = ucfirst($node->bundle());
    $node_type = str_replace("_"," ",$node_type);

    $term = \Drupal::entityTypeManager()->getStorage('taxonomy_term')
      ->loadByProperties(['vid' => 'image_category', 'name' => $node_type]);
    $term = reset($term);

    if($term) {
      $tid = $term->id();
    } else {
      // 'Other' if there is no taxonomy term for the node type.
      $term = \Drupal::entityTypeManager()->getStorage('taxonomy_term')
        ->loadByProperties(['vid' => 'image_category', 'name' => 'Other']);
      $term = reset($term);
      $tid = $term->id();
    }
    return $tid;
    }
  }
