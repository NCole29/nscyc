<?php

/**
 * @file
 * Club_report update hooks.
 */

 use Drupal\club\ImportYaml;
 use Drupal\club\ShortcutMenu;
 use Symfony\Component\Yaml\Yaml;
 use Drupal\menu_link_content\Entity\MenuLinkContent;

/**
 * Update 9001 - Add items to File Uploads menu.
 */
function club_report_update_9001() {

  // Menu item descriptions
  $minutes = 'Upload Board Meeting Minutes. Permission is granted to Site Administrator and Clerk.';
  $reports = 'Upload Club Reports. Permission is granted to Site Adminitrator and Clerk.';

  $items = [
    ['admin',-40,'Board minutes','/form/board-meeting-minutes',$minutes],
    ['admin',-35,'Club reports','/form/club-reports',$reports],
  ];
  foreach($items as $item) {
    $menu_link = MenuLinkContent::create([
      'menu_name' => $item[0],
      'weight' => $item[1],
      'title' => $item[2],
      'link' => [ 'uri' => 'internal:' . $item[3] ],
      'description' => $item[4],
      'parent' => 'club_report.admin_uploads',
      'expanded'  => TRUE,
      ]);
    $menu_link->save();
  }
}