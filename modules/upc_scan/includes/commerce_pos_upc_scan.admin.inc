<?php

/**
 * @file
 * UPC settings page.
 */

/**
 * Callback for the UPC Scan settings form.
 */
function commerce_pos_upc_scan_settings($form, &$form_state) {
  $field_options = array(
    '' => t('None'),
    'sku' => t('Base SKU field'),
  );

  $field_info = field_info_fields();
  foreach (field_info_instances('commerce_product') as $bundle_name => $fields) {
    foreach ($fields as $field_name => $field) {
      foreach (array_keys($field_info[$field_name]['columns']) as $column_name) {
        $field_options[$field_name][$field_name . '|' . $column_name] = $field_name . ' (' . $column_name . ')';
      }
    }
  }

  $form['commerce_pos_upc_scan_upc_field'] = array(
    '#title' => t('UPC Field'),
    '#description' => t("If you would like to use a barcode scanner in the POS, please select the field used to store your product's UPCs"),
    '#type' => 'select',
    '#options' => $field_options,
    '#default_value' => variable_get('commerce_pos_upc_scan_upc_field', NULL),
  );

  return system_settings_form($form);
}
