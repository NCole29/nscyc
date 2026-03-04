<?php

/**
 * @file
 * Install first node. Displays on frontpage, displacing the message to install Club CiviCRM.
 */

use Drupal\node\Entity\Node;

function club_firstnode() {

  // Get current time.
  $now = \Drupal::time()->getRequestTime();

  $node = Node::create([
    'type' => 'page',
    'langcode' => 'en',
    'created' => $now,
    'changed' => $now,
    'uid' => 1,
    'title' => 'Start building your site',
    'field_type_of_page' => 1,
    'field_summary' => [
      'format' => 'full_html',
      'value' => t("Go to <strong><a href='help/ah/club_help/about'>Club Help</a></strong> (top black menu) for information about this distribution and how to start building your site.<br>Delete this page after you add content to the home page."),
    ],
    'field_text' => [
      'format' => 'full_html',
      'value' => t("Go to <strong><a href='help/ah/club_help/about'>Club Help</a></strong> (top black menu) for information about this distribution and how to start building your site.<br>Delete this page after you add content to the home page."),
    ],
    'status' => 1,
    'promote' => 1,
  ]);
  $node->save();

}
