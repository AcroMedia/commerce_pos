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
    '#description' => t('Select which field represents an image for your product types.'),
  );

  foreach (commerce_product_types() as $name => $type) {
    $variable_name = 'commerce_pos_image_field_' . $name;

    $fields = field_info_instances('commerce_product', $name);
    $options = array(NULL => t('No field'));

    foreach ($fields as $field_name => $field) {
      $options[$field_name] = $field_name;
    }

    $form[$variable_name] = array(
      '#type' => 'select',
      '#title' => $type['name'],
      '#options' => $options,
      '#default_value' => variable_get($variable_name, NULL),
    );
  }

  return system_settings_form($form);
}
