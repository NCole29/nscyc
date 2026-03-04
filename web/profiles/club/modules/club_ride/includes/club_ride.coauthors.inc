<?php

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Access\AccessResultInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\node\NodeInterface;

/**
 * Allow edit/delete access for ride leaders.
 * Code is based on the drupal/node_co_authors module.
 *
 * Implements @see hook_ENTITY_TYPE_access().
 */
function club_ride_node_access(NodeInterface $node, $op, AccountInterface $account): AccessResultInterface {
  if (!$node->hasField('field_ride_leader')) {
    return AccessResult::neutral();
  }

  $type = $node->bundle();
  $ids = array_column(
        $node->get('field_ride_leader')->getValue(),
        'target_id'
    );

  $isCoAuthor = AccessResult::allowedIf(in_array($account->id(), $ids, TRUE))
    ->cachePerUser()
    ->addCacheableDependency($node);

  switch ($op) {
    case 'update':
      return AccessResult::allowedIfHasPermission($account, 'edit own ' . $type . ' content')
        ->andIf($isCoAuthor);

    case 'delete':
      return AccessResult::allowedIfHasPermission($account, 'delete own ' . $type . ' content')
        ->andIf($isCoAuthor);

    default:
      $access = AccessResult::neutral();
  }

  return $access;
}
