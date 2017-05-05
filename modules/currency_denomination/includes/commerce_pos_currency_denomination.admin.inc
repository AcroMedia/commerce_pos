<?php

/**
 * @file
 * Admin-related callbacks and functions for commerce_pos_currency_denomination.
 */

/**
 * The currency denomination admin settings form.
 */
function commerce_pos_currency_denomination_settings_form($form, &$form_state) {
  // Add our add denomination type button.
  $form['commerce_pos_add_denomination_type_button'] = array(
    '#markup' => l(t('Add New Denomination Type'), 'admin/commerce/config/pos/denomination-type/add', array(
      'attributes' => array('class' => array('button')),
    ))
  );

  $denomination_options = array();
  $denomination_types = commerce_pos_currency_denomination_get_currency_types();

  // Format the denomination options for our form element.
  foreach ($denomination_types as $type) {
    $markup = $type->currency_code;
    $markup .= ' ' . l(t('view'), 'admin/commerce/config/pos/denomination-type/' . $type->currency_id . '/denomination/view', array(
        'attributes' => array('class' => array('view-denomination'))
      ));
    $markup .= ' | ' . l(t('add'), 'admin/commerce/config/pos/denomination-type/' . $type->currency_id . '/denomination/add', array(
        'attributes' => array('class' => array('add-denomination'))
      ));
    if ($type->currency_code != 'USD') {
      $markup .= ' | ' . l(t('delete'), 'admin/commerce/config/pos/denomination-type/' . $type->currency_id . '/delete', array(
          'attributes' => array('class' => array('delete-denomination'))
        ));
    }
    $denomination_options[$type->currency_code] = $markup;
  }

  $form['commerce_pos_default_currency_denomination_types'] = array(
    '#title' => t('Set Default Currency Denominations'),
    '#type' => 'checkboxes',
    '#options' => $denomination_options,
    '#required' => TRUE,
    '#default_value' => variable_get('commerce_pos_default_currency_denomination_types', array('USD')),
    '#description' => t('Select the currency denomination types that will be used in the system.'),
  );

  $form['actions']['submit'] = array(
    '#type' => 'submit',
    '#value' => t('Save settings'),
  );

  $form['#attached']['css'][] = drupal_get_path('module', 'commerce_pos_currency_denomination') . '/css/commerce_pos_currency_denomination.css';

  return $form;
}

/**
 * Submit handler for the POS currency denomination settings form.
 */
function commerce_pos_currency_denomination_settings_form_submit($form, &$form_state) {
  if (!empty($form_state['values']['commerce_pos_default_currency_denomination_types'])) {
    variable_set('commerce_pos_default_currency_denomination_types', $form_state['values']['commerce_pos_default_currency_denomination_types']);
  }
}

/**
 * The form for adding new denomination types.
 */
function commerce_pos_currency_add_denomination_type_form($form, &$form_state) {
  $form['commerce_pos_denomination_type_currency_code'] = array(
    '#type' => 'textfield',
    '#size' => 3,
    '#maxlength' => 3,
    '#default_value' => NULL,
    '#title' => t('Denomination Type Currency Code'),
    '#description' => t('Currency code should be in the following format: "USD".'),
    '#required' => TRUE,
  );

  $form['actions']['submit'] = array(
    '#type' => 'submit',
    '#value' => t('Save'),
  );

  return $form;
}

/**
 * Validate handler for adding new denomination types.
 */
function commerce_pos_currency_add_denomination_type_form_validate($form, &$form_state) {
  // Check if the denomination type already exists in the db.
  $result = db_select('commerce_pos_currency_denomination_type', 't')
    ->fields('t', array('currency_id'))
    ->condition('currency_code', $form_state['values']['commerce_pos_denomination_type_currency_code'])
    ->execute()
    ->fetchField();

  if ($result) {
    form_set_error('', t('This denomination type already exists in the system.'));
  }
}

/**
 * Submit handler for adding new denomination types.
 */
function commerce_pos_currency_add_denomination_type_form_submit($form, &$form_state) {
  db_insert('commerce_pos_currency_denomination_type')
    ->fields(array(
      'currency_code' => $form_state['values']['commerce_pos_denomination_type_currency_code']
    ))
    ->execute();

  drupal_set_message(t('The denomination type has been successfully added.'));
  drupal_goto('admin/commerce/config/pos/denomination');
}

/**
 * Delete a denomination type.
 */
function commerce_pos_currency_delete_denomination_type_form($form, &$form_state, $currency_id) {
  // Get the denomination type info.
  $form_state['denomination_type_info'] = commerce_pos_currency_denomination_get_currency_types_from_id(array($currency_id));

  // If the denomination type is part of the enabled denomination types, then,
  // don't let the user delete it.
  $configured_denom_types = variable_get('commerce_pos_default_currency_denomination_types');
  if (isset($configured_denom_types[$form_state['denomination_type_info'][$currency_id]->currency_code])
    && $configured_denom_types[$form_state['denomination_type_info'][$currency_id]->currency_code] === $form_state['denomination_type_info'][$currency_id]->currency_code) {
    form_set_error('', t('This denomination type is currently enabled. Please disable it first before deleting.'));
    return;
  }

  if (!empty($form_state['denomination_type_info'])) {
    $form_state['denomination_type_info'] = $form_state['denomination_type_info'][$currency_id];

    return confirm_form(
      $form,
      t('Are you sure you want to delete currency denomination type @type?', array(
        '@type' => $form_state['denomination_type_info']->currency_code,
      )),
      'admin/commerce/config/pos/denomination',
      t('All denominations under this type will be deleted. This action cannot be undone.'),
      t('Delete'),
      t('Cancel')
    );
  }
  else {
    form_set_error('', t('Invalid currency ID.'));
  }

  return $form;
}

/**
 * Submit handler for deleting a denomination type.
 */
function commerce_pos_currency_delete_denomination_type_form_submit($form, &$form_state) {
  if (isset($form_state['denomination_type_info'])) {
    // First delete the denominations for that type.
    db_delete('commerce_pos_currency_denomination')
      ->condition('currency_id', $form_state['denomination_type_info']->currency_id)
      ->execute();

    // Now delete the denomination type.
    db_delete('commerce_pos_currency_denomination_type')
      ->condition('currency_id', $form_state['denomination_type_info']->currency_id)
      ->execute();
  }

  drupal_set_message(t('The denomination type @type has been successfully deleted.', array(
    '@type' => $form_state['denomination_type_info']->currency_code
  )));
  drupal_goto('admin/commerce/config/pos/denomination');
}

/**
 * Displays all the denominations for a specific currency.
 */
function commerce_pos_currency_view_denominations($currency_id) {
  $output = '';

  // Get the denomination type info.
  $denomination_type_info = commerce_pos_currency_denomination_get_currency_types_from_id(array($currency_id));

  if (!empty($denomination_type_info)) {
    $denomination_type_info = $denomination_type_info[$currency_id];

    $output .= l(t('Add Denomination'), 'admin/commerce/config/pos/denomination-type/' . $denomination_type_info->currency_id . '/denomination/add', array(
      'attributes' => array('class' => array('add-denomination', 'button'))
    ));

    // Now, get the denominations for this denomination type.
    $denominations = commerce_pos_currency_denomination_get_denominations(array($denomination_type_info->currency_code));

    $output .= t('<h3>Current Denominations for Type @type</h3>', array(
      '@type' => $denomination_type_info->currency_code
    ));

    $headers = array(
      'Title' => t('Title'),
      'Amount' => t('Amount'),
      'Options' => t('Options'),
    );
    $rows = array();

    if (!empty($denominations)) {
      foreach ($denominations[$denomination_type_info->currency_id] as $denomination) {
        $row = array();
        $row[] = $denomination->denomination_title;
        $row[] = $denomination->denomination_amount;
        $row[] = l(t('delete'), 'admin/commerce/config/pos/denomination-type/' . $currency_id . '/denomination/' . $denomination->denomination_id . '/delete');
        $rows[] = $row;
      }
    }

    $output .= theme('table', array(
      'header' => $headers,
      'rows' => $rows,
      'sticky' => TRUE,
      'attributes' => array(
          'class' => array('commerce-pos-current-denominations')
        ),
      'empty' => t('Currently, no denominations have been added for this type.'),
    )
    );
  }
  else {
    $output .= t('<p>Invalid denomination type.</p>');
  }

  return $output;
}

/**
 * The form for adding a new denomination.
 */
function commerce_pos_currency_add_denomination_form($form, &$form_state, $currency_id) {
  $form_state['currency_id'] = $currency_id;

  // Get the denomination info for this type.
  $form_state['denomination_type_info'] = commerce_pos_currency_denomination_get_currency_types_from_id(array($currency_id));

  if (!empty($form_state['denomination_type_info'])) {
    $form_state['denomination_type_info'] = $form_state['denomination_type_info'][$currency_id];

    drupal_set_title(t('Add Denomination for Type @type', array(
      '@type' => $form_state['denomination_type_info']->currency_code
    )));

    $form['commerce_pos_denomination_title'] = array(
      '#type' => 'textfield',
      '#size' => 50,
      '#maxlength' => 50,
      '#default_value' => NULL,
      '#title' => t('Denomination Title'),
      '#description' => t('Enter the denomination title eg. "Penny".'),
      '#required' => TRUE,
    );

    $form['commerce_pos_denomination_name'] = array(
      '#type' => 'machine_name',
      '#title' => t('Denomination Name'),
      '#default_value' => '',
      '#maxlength' => 25,
      '#description' => t('A unique denomination name eg. "penny".'),
      '#machine_name' => array(
        'exists' => 'commerce_pos_currency_denomination_name_exists',
        'source' => array('commerce_pos_denomination_title'),
      ),
    );

    $form['commerce_pos_denomination_amount'] = array(
      '#type' => 'textfield',
      '#size' => 10,
      '#maxlength' => 10,
      '#default_value' => NULL,
      '#title' => t('Denomination Amount'),
      '#description' => t('Enter the denomination amount eg. "1".'),
      '#required' => TRUE,
      '#element_validate' => array('element_validate_integer_positive'),
    );

    $form['actions']['submit'] = array(
      '#type' => 'submit',
      '#value' => t('Save'),
    );
  }
  else {
    form_set_error('', t('Invalid currency ID.'));
  }

  return $form;
}

/**
 * Submit handler for adding a new denomination.
 */
function commerce_pos_currency_add_denomination_form_submit($form, &$form_state) {
  db_insert('commerce_pos_currency_denomination')
    ->fields(array(
      'currency_id' => $form_state['currency_id'],
      'denomination_name' => $form_state['values']['commerce_pos_denomination_name'],
      'denomination_title' => $form_state['values']['commerce_pos_denomination_title'],
      'denomination_amount' => $form_state['values']['commerce_pos_denomination_amount'],
    ))
    ->execute();

  drupal_set_message(t('The denomination has been successfully added for type @type.', array(
    '@type' => $form_state['denomination_type_info']->currency_code
  )));
  drupal_goto('admin/commerce/config/pos/denomination-type/' . $form_state['currency_id'] . '/denomination/view');
}

/**
 * Delete a denomination.
 */
function commerce_pos_currency_delete_denomination_form($form, &$form_state, $currency_id, $denomination_id) {
  $form_state['currency_id'] = $currency_id;

  // Fetch the denomination info for this id.
  $form_state['denomination_info'] = commerce_pos_currency_denomination_get_denomination($denomination_id);

  if (!empty($form_state['denomination_info'])) {
    $form_state['denomination_info'] = $form_state['denomination_info'][$denomination_id];

    return confirm_form(
      $form,
      t('Are you sure you want to delete denomination @denomination?', array(
        '@denomination' => $form_state['denomination_info']->denomination_title
      )),
      'admin/commerce/config/pos/denomination-type/' . $currency_id . '/denomination/view',
      t('This action cannot be undone.'),
      t('Delete'),
      t('Cancel')
    );
  }
  else {
    form_set_error('', t('Invalid denomination ID.'));
  }

  return $form;
}

/**
 * Submit handler for deleting a denomination.
 */
function commerce_pos_currency_delete_denomination_form_submit($form, &$form_state) {
  if (isset($form_state['denomination_info'])) {
    // Delete the denomination.
    db_delete('commerce_pos_currency_denomination')
      ->condition('denomination_id', $form_state['denomination_info']->denomination_id)
      ->execute();
  }

  drupal_set_message(t('The denomination @denomination has been successfully deleted.', array(
    '@denomination' => $form_state['denomination_info']->denomination_title
  )));
  drupal_goto('admin/commerce/config/pos/denomination-type/' . $form_state['currency_id'] . '/denomination/view');
}
