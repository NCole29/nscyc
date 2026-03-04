<?php

/**
 * @file
 * club_layout.inc
 * Add to the Appearance > theme > settings form
 * These data are available to the page.html.twig template
 */

/**
 * The theme settings.
 */

$form['clubtheme_settings']['club_layouts'] = [
  '#type' => 'details',
  '#title' => t('Club - Changes to Page layout'),
  '#collapsible' => TRUE,
  '#collapsed' => FALSE,
];
$form['clubtheme_settings']['club_layouts']['topnav'] = [
  '#type' => 'checkbox',
  '#title' => t('Main navigation at top of page'),
  '#description' => t('Check to place the main navigation menu at the top of the page.<br>Uncheck to place the navigation menu below the header.'),
  '#default_value' => theme_get_setting('topnav'),
];
$form['clubtheme_settings']['club_layouts']['solid_footer'] = [
  '#type' => 'checkbox',
  '#title' => t('Solid Footer'),
  '#description' => t('Check to make all footer regions the same color as the footer container (w3-theme-d3).<br>Uncheck to restore W3CSS theme colors for footer first (w3-theme-l4), footer second (w3-theme-l5), and footer third (w3-theme-l4) regions.'),
  '#default_value' => theme_get_setting('solid_footer'),
];
$form['clubtheme_settings']['club_layouts']['image_captions'] = [
  '#type' => 'checkbox',
  '#title' => t('Display image captions'),
  '#description' => t('Check to display image Alt text as caption below image.'),
  '#default_value' => theme_get_setting('image_captions'),
];
