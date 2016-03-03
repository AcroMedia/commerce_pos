<?php

/**
 * @file
 */

/**
 * Callback for the UPC Scan settings form.
 */
function commerce_pos_upc_scan_settings($form, &$form_state) {
  $field_options = array(
    '' => t('None'),
    'sku' => t('Base SKU field'),
  );

  foreach (field_info_instances('commerce_product') as $bundle_name => $fields) {
    foreach ($fields as $field_name => $field) {
      $field_options[$field_name] = $field_name;
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
