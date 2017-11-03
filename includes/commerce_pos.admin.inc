<?php

/**
 * @file
 * Admin-related callbacks and functions for commerce_pos.
 */

/**
 * Callback for the POS settings form.
 */
function commerce_pos_settings($form, &$form_state) {
  $form['product_settings'] = array(
    '#type' => 'fieldset',
    '#title' => t('Product Settings'),
  );

  $product_options = array();

  foreach (commerce_product_types() as $name => $type) {
    $product_options[$name] = $type['name'];
  }

  $form['product_settings']['commerce_pos_available_products'] = array(
    '#title' => t('Available products'),
    '#type' => 'checkboxes',
    '#options' => $product_options,
    '#default_value' => variable_get('commerce_pos_available_products', array('product')),
    '#description' => t('Select the types of products that can be added to POS transactions.'),
  );

  $options = array(NULL => t('No field'));

  foreach (field_info_fields() as $field_name => $field) {
    if ($field['type'] == 'image') {
      $options[$field_name] = $field_name;
    }
  }

  foreach (commerce_product_types() as $name => $type) {
    $variable_name = 'commerce_pos_image_field_' . $name;

    $form['product_settings'][$name] = array(
      '#type' => 'fieldset',
      '#title' => $type['name'],
      '#description' => t('Select the field options for this product type within the POS'),
      '#states' => array(
        'invisible' => array(
          ':input[name="commerce_pos_available_products[' . $name . ']"]' => array('checked' => FALSE),
        ),
      ),
    );

    $form['product_settings'][$name][$variable_name] = array(
      '#type' => 'select',
      '#title' => t('Image field(s)'),
      '#description' => t('Select fields that will be used to find an image to display for your products within the POS'),
      '#options' => $options,
      '#default_value' => variable_get($variable_name, NULL),
      '#multiple' => TRUE,
    );

    $variable_name = 'commerce_pos_image_default_' . $name;

    $form['product_settings'][$name][$variable_name] = array(
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

  $form['search'] = array(
    '#type' => 'fieldset',
    '#title' => t('Search'),
  );

  if (module_exists('search_api')) {
    $index_options = array(
      'default' => t('Use default search'),
    );

    foreach (search_api_index_load_multiple(FALSE) as $id => $index) {
      $index_options[$id] = $index->name;
    }

    $form['search']['commerce_pos_search_api_index'] = array(
      '#type' => 'select',
      '#title' => t('Search API Index'),
      '#description' => t('If you would like to use a search API index when searching for products in the POS, you may select one here.<br><strong>NOTE: You must use an index based on the commerce_product entity as it uses the product_id from the results.</strong>'),
      '#options' => $index_options,
      '#default_value' => variable_get('commerce_pos_search_api_index', NULL),
    );
  }
  else {
    $form['search']['commerce_pos_search_api_missing'] = array(
      '#markup' => t(
        'Search API module not available, if you want more robust search functionality, install the <a href="@search_api">search_api</a> module',
        array('@search_api' => 'https://www.drupal.org/project/search_api')
      ),
    );

    $form['search']['commerce_pos_search_sku_matching'] = array(
      '#type' => 'radios',
      '#title' => t('SKU matching options'),
      '#options' => array(
        'exact' => 'Exact - Ex: only AB-123-HA will match AB-123-HA',
        'semi-partial' => 'Semi Partial - Ex: AB- will match, but 123 will not match AB-123-HA',
        'partial' => 'Partial - Ex: AB- and 123 will both match AB-123-HA',
      ),
      '#default_value' => variable_get('commerce_pos_search_sku_matching', 'exact'),
      '#description' => t('Change the type of sku matching depending on your use cases, exact matching is less prone to mistakes, 
      but only works if you know most of your skus and rarely search')
    );
  }

  $form['search']['commerce_pos_search_results_count'] = array(
    '#type' => 'textfield',
    '#title' => t('Number of search results'),
    '#size' => 15,
    '#required' => TRUE,
    '#description' => t('Set the number of search results to be returned when searching for products in the POS.'),
    '#default_value' => variable_get('commerce_pos_search_results_count', 5),
  );

  $form['search']['commerce_pos_search_published_only'] = array(
    '#type' => 'checkbox',
    '#title' => t('Search published products only'),
    '#description' => t('When enabled, the search will only find products that have their status set to published. If using search_api, the \'status\' field will need to be indexed.'),
    '#default_value' => variable_get('commerce_pos_search_published_only', FALSE),
  );

  // Grab a list of payments.
  $payments = array();
  foreach (commerce_pos_get_payment_options() as $payment) {
    $payments[$payment['id']] = $payment['title'];
  }

  $form['payments'] = array(
    '#type' => 'fieldset',
    '#title' => t('Payment Settings'),
  );

  $form['payments']['commerce_pos_default_payment_method'] = array(
    '#type' => 'select',
    '#title' => t('Default Payment Method'),
    '#description' => t('Select the default payment method.'),
    '#empty_option' => t('- None -'),
    '#options' => $payments,
    '#default_value' => variable_get('commerce_pos_default_payment_method', ''),
  );

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

  // Checks that the search results count setting is a positive integer between 1 and 500 inclusive.
  if ($form_state['values']['commerce_pos_search_results_count'] !== NULL) {
    $search_count = (int) $form_state['values']['commerce_pos_search_results_count'];
    if ($search_count < 1 || $search_count > 500) {
      form_error($form['commerce_pos_search_results_count'], t('You must provide a positive integer between 1 and 500.'));
    }
  }

}

/**
 * Submit handler for the POS settings form.
 */
function commerce_pos_settings_submit($form, &$form_state) {
  if (!empty($form_state['values']['commerce_pos_search_api_index'])) {
    variable_set(
      'commerce_pos_search_api_index',
      $form_state['values']['commerce_pos_search_api_index']
    );
  }
  if (!empty($form_state['values']['commerce_pos_search_sku_matching'])) {
    variable_set(
      'commerce_pos_search_sku_matching',
      $form_state['values']['commerce_pos_search_sku_matching']
    );
  }

  variable_set('commerce_pos_search_published_only', $form_state['values']['commerce_pos_search_published_only']);

  variable_set('commerce_pos_search_results_count', $form_state['values']['commerce_pos_search_results_count']);

  variable_set('commerce_pos_available_products', $form_state['values']['commerce_pos_available_products']);

  variable_set('commerce_pos_default_payment_method', $form_state['values']['commerce_pos_default_payment_method']);

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
