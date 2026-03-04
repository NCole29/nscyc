<?php

/**
 * @file
 * Club_event update hooks.
 */

 use Drupal\club\ImportYaml;
 use Symfony\Component\Yaml\Yaml;
 use Drupal\field\Entity\FieldStorageConfig;

/**
 * 
 * Add Drupal date (field_date) and update form and displays.
 */
function club_event_update_9001() {

  // Install new config.
  $club_event = \Drupal::service('extension.list.module')
    ->getPath('club_event') . '/config/install/';

  // Array(new?, folder, config type, config name).  
  $config_files = [
    [1, $club_event, 'field_config', 'field.field.node.event.field_date'],
    [0, $club_event, 'x', 'views.view.events'],
    [0, $club_event, 'x', 'core.entity_view_display.node.event.default'],
    [0, $club_event, 'x', 'core.entity_view_display.node.event.past_event'],
    [0, $club_event, 'x', 'core.entity_view_display.node.event.teaser'],
    [0, $club_event, 'x', 'core.entity_view_display.node.event.teaser2'],
	[0, $club_event, 'x', 'views.view.frontpage'],
  ];

  foreach ($config_files as $new) {
    $settings = Yaml::parse((string) file_get_contents($new[1] . $new[3] . '.yml'));

    if(is_array($settings)) {
      // New config are created.
      if ($new[0] == 1) {
          \Drupal::entityTypeManager()->getStorage($new[2])
          ->create($settings)
          ->save();
      } else {
        // Existing config are replaced. GetEditable reads config = filename without extension.
        $config = \Drupal::configFactory()->getEditable($new[3]);
        $config->setData($settings)->save(TRUE);
      }
    }
  }
}
