<?php

/**
 * @file
 * cancel_background.inc
 * Add Cancel block background color option to the Appearance > theme > settings form
 * These data are available to the page.html.twig template
 */

/**
 * The theme settings.
 */

$options = [
  'w3-theme-l4' => 'Default',
  'w3-vivid-pink' => 'Pink',
  'w3-vivid-red' => 'Red',
  'w3-vivid-orange' => 'Orange',
  'w3-vivid-yellow' => 'Yellow',
  'w3-vivid-green' => 'Green',
  'w3-vivid-blue' => 'Blue',
  'w3-vivid-violet' => 'Violet',
  'w3-vivid-purple' => 'Purple',
  'w3-vivid-black' => 'Black',
  'w3-vivid-white' => 'White',
];
$form['clubtheme_settings']['cancel_background'] = [
  '#type' => 'details',
  '#title' => t('Club - Cancellation Block Background color'),
  '#collapsible' => TRUE,
  '#collapsed' => FALSE,
];
$form['clubtheme_settings']['cancel_background']['cancel_bgcolor'] = [
  '#type' => 'select',
  '#title' => t('Select a <a href="https://drupal8-w3css-theme.flashwebcenter.com/drupal8-w3css-theme-vivid-colors" target="_blank">Drupal8 W3CSS Theme Vivid Color</a> for the background of cancellation alerts on the home page.'),
  '#description' => t('Default is w3-theme-l4 associated with your theme.'),
  '#options' => $options,
  '#default_value' => theme_get_setting('cancel_bgcolor'),
];
