<?php

/**
 * @file
 * Multiple fields remove button API documentation.
 */

/**
 * @addtogroup hooks
 * @{
 */

/**
 * Modify field types for which remove button will be added.
 *
 * @param array $fieldTypes
 *   A list with field types.
 */
function hook_multiple_field_remove_button_field_types_alter(array &$fieldTypes): void {
  $fieldTypes[] = "custom_field_type";
}

/**
 * Modify field types for which remove button will not be added.
 *
 * @param array $skipTypes
 *   A list with field types.
 */
function hook_multiple_field_remove_button_skip_types_alter(array &$skipTypes): void {
  $skipTypes[] = "custom_field_type";
}

/**
 * Modify field widgets for which remove button will not be added.
 *
 * @param array $skipWidgets
 *   A list with field widgets.
 */
function hook_multiple_field_remove_button_skip_widgets_alter(array &$skipWidgets): void {
  $skipWidgets[] = "custom_field_widget";
}

/**
 * To only include certain fields.
 *
 * @param array $includedFields
 *   A list with field names.
 */
function hook_multiple_field_remove_button_included_fields_alter(array &$includedFields): void {
  $includedFields = [];
}

/**
 * @} End of "addtogroup hooks".
 */
