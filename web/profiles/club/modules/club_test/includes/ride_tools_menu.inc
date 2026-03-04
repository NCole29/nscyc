<?php


// Add Ride tools to top-level Administration MENU.
$menu_link = MenuLinkContent::create([
  'menu_name' => 'administration',
  'weight' => -9,
  'title' => 'Ride tools',
  'link' => [ '<nolink>' ],
  'parent' => NULL,
  'expanded'  => TRUE,
  '#options' => [
    'attributes' => [
      'title' => t('Ride Tools'),
      'class' => [
        'toolbar-item',
        'toolbar-icon',
        'toolbar-icon-ridetools',
      ],
    ],
  ],
  ]);
$menu_link->save();

$new_menu = $menu_link->uuid();

// Add to Ride tools menu.
$items = [
  ['administration',-40,'Cancel ride','/admin/cancel-ride'],
  ['administration',-35,'Enter waiver form','/admin/add-nonmembers'],
  ['administration',-20,'View ride schedules','/ride_schedule'],
];
foreach($items as $item) {
  $menu_link = MenuLinkContent::create([
    'menu_name' => $item[0],
    'weight' => $item[1],
    'title' => $item[2],
    'link' => [ 'uri' => 'internal:' . $item[3] ],
    'parent' => 'menu_link_content:' . $new_menu,
    'expanded'  => TRUE,
    ]);
  $menu_link->save();
}
