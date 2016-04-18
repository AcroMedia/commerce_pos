<?php

/**
 * @file
 * Admin-related callbacks and functions for commerce_pos.
 */

/**
 * Callback for the POS settings form.
 */
function commerce_pos_settings($form, &$form_state) {
  if (module_exists('search_api')) {
    $index_options = array(
      'default' => t('Use default search'),
    );

    foreach (search_api_index_load_multiple(FALSE) as $id => $index) {
      $index_options[$id] = $index->name;
    }

    $form['commerce_pos_search_api_index'] = array(
      '#type' => 'select',
      '#title' => t('Search API Index'),
      '#description' => t('If you would like to use a search API index when search for products in the POS, you may select one here.'),
      '#options' => $index_options,
      '#default_value' => variable_get('commerce_pos_search_api_index', NULL),
    );
  }

  $product_options = array();

  foreach (commerce_product_types() as $name => $type) {
    $product_options[$name] = $type['name'];
  }

  $form['commerce_pos_available_products'] = array(
    '#title' => t('Available products'),
    '#type' => 'checkboxes',
    '#options' => $product_options,
    '#default_value' => variable_get('commerce_pos_available_products', array('product')),
    '#description' => t('Select the types of products that can be added to POS transactions.'),
  );

  $form['image_fields'] = array(
    '#type' => 'fieldset',
    '#title' => t('Image settings'),
  );

  $options = array(NULL => t('No field'));

  foreach (field_info_fields() as $field_name => $field) {
    if ($field['type'] == 'image') {
      $options[$field_name] = $field_name;
    }
  }

  foreach (commerce_product_types() as $name => $type) {
    $variable_name = 'commerce_pos_image_field_' . $name;

    $form['image_fields'][$name] = array(
      '#type' => 'fieldset',
      '#title' => $type['name'],
      '#states' => array(
        'invisible' => array(
          ':input[name="commerce_pos_available_products[' . $name . ']"]' => array('checked' => FALSE),
        ),
      ),
    );

    $form['image_fields'][$name][$variable_name] = array(
      '#type' => 'select',
      '#title' => t('Fields'),
      '#options' => $options,
      '#default_value' => variable_get($variable_name, NULL),
      '#multiple' => TRUE,
    );

    $variable_name = 'commerce_pos_image_default_' . $name;

    $form['image_fields'][$name][$variable_name] = array(
      '#type' => 'managed_file',
      '#title' => t('Default image'),
      '#description' => t('You may provide a default image for when no other image is available.'),
      '#default_value' => variable_get($variable_name, NULL),
      '#upload_location' => 'public://commerce_pos_default_images',
      '#upload_validators' => array(
        'file_validate_is_image' => array(),
        'file_validate_extensions' => array('png gif jpg jpeg'),
      ),
    );
  }

  $form['actions'] = array(
    '#type' => 'actions',
  );

  $form['actions']['submit'] = array(
    '#type' => 'submit',
    '#value' => t('Save settings'),
  );

  return $form;
}

/**
 * Validate handler for the POS settings form.
 */
function commerce_pos_settings_validate($form, &$form_state) {
  // Make sure at least one product type has been checked.
  $product_selected = FALSE;

  foreach ($form_state['values']['commerce_pos_available_products'] as $checked) {
    if ($checked) {
      $product_selected = TRUE;
      break;
    }
  }

  if (!$product_selected) {
    form_error($form['commerce_pos_available_products'], t('You must select at least one product type.'));
  }

  $directory = 'public://commerce_pos_default_thumbnails';
  file_prepare_directory($directory, FILE_MODIFY_PERMISSIONS | FILE_CREATE_DIRECTORY);
}

/**
 * Submit handler for the POS settings form.
 */
function commerce_pos_settings_submit($form, &$form_state) {
  if (!empty($form_state['values']['commerce_pos_search_api_index'])) {
    variable_set('commerce_pos_search_api_index', $form_state['values']['commerce_pos_search_api_index']);
  }

  variable_set('commerce_pos_available_products', $form_state['values']['commerce_pos_available_products']);

  foreach (commerce_product_types() as $name => $type) {
    variable_set('commerce_pos_image_field_' . $name, $form_state['values']['commerce_pos_image_field_' . $name]);

    $image_variable = 'commerce_pos_image_default_' . $name;
    $existing_image_fid = variable_get($image_variable, 0);
    $remove_existing = FALSE;
    $new_fid = $existing_image_fid;

    if (!empty($form_state['values'][$image_variable])) {
      if ($form_state['values'][$image_variable] != $existing_image_fid) {
        $file = file_load($form_state['values'][$image_variable]);
        $file->status = FILE_STATUS_PERMANENT;

        file_save($file);
        file_usage_add($file, 'commerce_pos', 'settings', 1);

        $new_fid = $file->fid;
        $remove_existing = TRUE;
      }
    }
    else {
      $new_fid = 0;
      $remove_existing = TRUE;
    }

    if ($existing_image_fid && $remove_existing) {
      if ($file = file_load($existing_image_fid)) {
        file_delete($file, TRUE);
      }
    }

    variable_set($image_variable, $new_fid);
  }

  if (!empty($form_state['storage']['files'])) {
    foreach ($form_state['storage']['files'] as $variable_name => $file) {
      variable_set($variable_name, $file->fid);
    }
  }
}
