<?php

/**
 * @file
 * Club_ride update hooks.
 */

 use Drupal\club\ImportYaml;
 use Drupal\club\ShortcutMenu;
 use Symfony\Component\Yaml\Yaml;
 use Drupal\field\Entity\FieldStorageConfig;
 use Drupal\menu_link_content\Entity\MenuLinkContent;

/**
 * BE PATIENT! Add recurring_dates content type.
 * Update Views for calendar, cancellations, and routes.
 * Add Cancel ride shortcut. Uninstall club_cancellations.
 */
function club_ride_update_9002() {

  // Deleting field storage - this also deletes field and field contents.
  // We delete field_day (string) from RIDE and RECURRING_RIDE.
  // Later add field_day_of_week (INT) to RIDE and RECURRING_DATES.
  
  //FieldStorageConfig::loadByName('node', 'field_day')->delete();

  // Install new config.
  $club_ride = \Drupal::service('extension.list.module')
    ->getPath('club_ride') . '/config/install/';

  // Array(new?, folder, config type, config name).  
  $config_files = [
    [1, $club_ride, 'node_type', 'node.type.recurring_dates'],
    [1, $club_ride, 'date_format','core.date_format.time'],
    [1, $club_ride, 'date_format','core.date_format.day_date'],
    [1, $club_ride, 'field_storage_config','field.storage.node.field_node_id'],
    [1, $club_ride, 'field_config','field.field.node.recurring_dates.field_cancel'],
    [1, $club_ride, 'field_config','field.field.node.recurring_dates.field_date'],
    [1, $club_ride, 'field_config','field.field.node.recurring_dates.field_location'],
    [1, $club_ride, 'field_config','field.field.node.recurring_dates.field_node_id'],
    [1, $club_ride, 'entity_form_display','core.entity_form_display.node.recurring_dates.default'],
    [1, $club_ride, 'entity_view_display','core.entity_view_display.node.recurring_dates.default'],
    [1, $club_ride, 'view','views.view.ride_dates'],
    [1, $club_ride, 'field_storage_config','field.storage.node.field_dayofweek'],
    [1, $club_ride, 'field_config', 'field.field.node.recurring_dates.field_dayofweek'],
    [1, $club_ride, 'field_config', 'field.field.node.ride.field_dayofweek'],
    [0, $club_ride, 'x', 'core.entity_form_display.node.ride.default'],
    [0, $club_ride, 'x', 'views.view.calendar'],
    [0, $club_ride, 'x', 'views.view.cancellations'],
    [0, $club_ride, 'x', 'views.view.club_routes'],
    [0, $club_ride, 'x', 'block.block.views_block__cancellations_cancellations'],
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
 
  // Add shortcuts.
  $shortcuts = [
    ['admin',-7,'Cancel ride','internal:/admin/cancel-ride'],
    ['default',-7,'Cancel ride','internal:/admin/cancel-ride'],
    ['ride-leaders',-7,'Cancel ride','internal:/admin/cancel-ride'],
  ];
  shortcutmenu::addShortcuts($shortcuts);

  // Uninstall club_cancellation module.
  \Drupal::service('module_installer')->uninstall(['club_cancellation','fullcalendar_view']);
}

/**
 * Update 9503 - Add items to Ride Tools menu.
 */
function club_ride_update_9003() {

  // Menu item descriptions
  $cancel = 'Cancel a ride. Permission is granted to Ride leaders (OWN rides) and Rides coordinators (ANY ride).';
  $nonmember = 'Enter nonmembers from waiver form. Permission is granted to Ride leaders (OWN rides) and Membership coordinators (ANY ride).';
  $schedule = 'View all non-recurring rides for the season with separate displays per weekday.';

  $items = [
    ['admin',-40,'Cancel ride','/admin/cancel-ride',$cancel],
    ['admin',-35,'Enter waiver form','/admin/add-nonmembers',$nonmember],
    ['admin',-20,'View ride schedules','/ride_schedule',$schedule],
  ];
  foreach($items as $item) {
    $menu_link = MenuLinkContent::create([
      'menu_name' => $item[0],
      'weight' => $item[1],
      'title' => $item[2],
      'link' => [ 'uri' => 'internal:' . $item[3] ],
      'description' => $item[4],
      'parent' => 'club_ride.admin_ride_tools',
      'expanded'  => TRUE,
      ]);
    $menu_link->save();
  }
}