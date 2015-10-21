<?php

/**
 * @file
 * Admin-related callbacks and functions for commerce_pos.
 */

/**
 * Callback for the POS settings form.
 */
function commerce_pos_settings($form, &$form_state) {
  $form['image_fields'] = array(
    '#type' => 'fieldset',
    '#title' => t('Image fields'),
    '#description' => t('Select which fields represent an image for your product types.'),
  );

  $options = array(NULL => t('No field'));

  foreach (field_info_fields() as $field_name => $field) {
    if ($field['type'] == 'image') {
      $options[$field_name] = $field_name;
    }
  }

  foreach (commerce_product_types() as $name => $type) {
    $variable_name = 'commerce_pos_image_field_' . $name;

    $form['image_fields'][$variable_name] = array(
      '#type' => 'select',
      '#title' => $type['name'],
      '#options' => $options,
      '#default_value' => variable_get($variable_name, NULL),
      '#multiple' => TRUE,
    );
  }

  return system_settings_form($form);
}
