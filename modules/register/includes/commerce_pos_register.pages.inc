<?php

/**
 * @file
 * commerce_pos_register.pages.inc
 */

/**
 * Callback for displaying a POS register entity.
 */
function commerce_pos_register_view_register($register) {
  return entity_view('commerce_pos_register', array($register));
}

/**
 * Callback for the POS register selection form.
 */
function commerce_pos_register_select($form, &$form_state) {
  $current_register = commerce_pos_register_get_current_register();

  $form['register'] = array(
    '#type' => 'select',
    '#title' => t('Please select your register:'),
    '#options' => commerce_pos_register_options(),
    '#default_value' => $current_register ? $current_register : NULL,
    '#required' => TRUE,
  );

  $form['actions'] = array(
    '#type' => 'actions',
  );

  $form['actions']['summit'] = array(
    '#type' => 'submit',
    '#value' => t('Save'),
  );

  return $form;
}

/**
 * Submit handler for the register selection form.
 */
function commerce_pos_register_select_submit($form, &$form_state) {
  commerce_pos_register_set_current_register($form_state['values']['register']);
  $form_state['redirect'] = 'admin/commerce/pos';
}

/**
 * Callback for the POS register float form.
 */
function commerce_pos_register_float_form($form, &$form_state, $register_id = 0) {
  $register = commerce_pos_register_load($register_id);

  $form['#register_id'] = $register_id;
  $default_float = 0;

  if ($field_default_float = field_get_items('commerce_pos_register', $register, 'commerce_pos_default_float')) {
    if (!empty($field_default_float[0]['value'])) {
      $default_float = $field_default_float[0]['value'];
    }
  }

  $form['amount'] = array(
    '#type' => 'textfield',
    '#title' => t('Amount'),
    '#required' => TRUE,
    '#size' => 10,
    '#default_value' => $default_float,
  );

  $form['actions'] = array(
    '#type' => 'actions',
  );

  $form['actions']['save'] = array(
    '#type' => 'submit',
    '#value' => t('Continue')
  );

  // Add module CSS.
  $form['#attached']['css'][] = drupal_get_path('module', 'commerce_pos') . '/css/commerce_pos_style.css';

  return $form;
}

/**
 * Validate handler for the POS register float form.
 */
function commerce_pos_register_float_form_validate($form, &$form_state) {
  $amount = $form_state['values']['amount'];

  if (!(is_numeric($amount) && $amount > 0)) {
    form_set_error('amount', t('Amount must be a number greater than zero.'));
  }

  // Also ensure that no other floats have been submitted for this day and
  // register.
  if (!empty(commerce_pos_register_get_floats($form['#register_id']))) {
    drupal_set_message(t("You have already entered a float for that register today."), 'warning');
    drupal_goto('admin/commerce/pos');
  }
}

/**
 * Submit handler for the POS register float form.
 */
function commerce_pos_register_float_form_submit($form, &$form_state) {
  commerce_pos_register_add_float($form['#register_id'], $form_state['values']['amount'] * 100);
}
