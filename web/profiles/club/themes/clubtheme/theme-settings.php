<?php

/**
 * @file
 * Add items to Appearance > Settings > Clubtheme form.
 *
 */

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\File;

/**
 * Implements hook_form_system_theme_settings_alter().
 */
function clubtheme_form_system_theme_settings_alter(&$form, FormStateInterface $form_state) {

  // Theme settings files.
  require_once __DIR__ . '/includes/club_accounts.inc';
  require_once __DIR__ . '/includes/club_layouts.inc';
  require_once __DIR__ . '/includes/cancel_background.inc';

  $form['#submit'][] = 'clubtheme_form_system_theme_settings_submit';
}

/**
 * Update ckeditor.css if Custom CSS has ":root" colors
 *
 */
function clubtheme_form_system_theme_settings_submit(&$form, FormStateInterface $form_state) {
  // This submit handler is called before default submit handler for this form.

  // Get Custom CSS "root" colors from css_editor config because
  //  CSS_editor submit handler saves CSS & unsets value from form_state.
  $css = \Drupal::config('css_editor.theme.clubtheme')->get('css');

  if ($css) {
    $start = strpos($css,':root');
    $end   = strpos($css,'}',$start);

    if ($start and $end) {
      $root  = substr($css, $start, $end);

      // Read /themes/clubtheme/css/ckeditor4.base & ckeditor5.base and append after ":root" to write new css files
      $themepath = \Drupal::service('extension.list.theme')->getPath('clubtheme');
      $base5file = $themepath . '/css/ckeditor5.base';
      $css5file = $themepath . '/css/ckeditor5.css';

      $data5 = $root . "\r" . file_get_contents($base5file);

      file_put_contents($css5file, $data5, FILE_USE_INCLUDE_PATH | LOCK_EX);
    }
  }
}
