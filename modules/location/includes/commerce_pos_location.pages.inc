<?php

/**
 * @file
 * commerce_pos_location.pages.inc
 */

/**
 * Callback for displaying a POS location entity.
 */
function commerce_pos_location_view_location($location) {
  return entity_view('commerce_pos_location', array($location));
}

/**
 * Callback for the POS location float form.
 */
function commerce_pos_location_float_form($form, &$form_state, $location_id = 0) {
  $form['#location_id'] = $location_id;

  $form['amount'] = array(
    '#type' => 'textfield',
    '#title' => t('Amount'),
    '#required' => TRUE,
    '#size' => 10
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
 * Validate handler for the POS location float form.
 */
function commerce_pos_location_float_form_validate($form, &$form_state) {
  $amount = $form_state['values']['amount'];

  if (!(is_numeric($amount) && $amount > 0)) {
    form_set_error('amount', t('Amount must be a number greater than zero.'));
  }
}

/**
 * Submit handler for the POS location float form.
 */
function commerce_pos_location_float_form_submit($form, &$form_state) {
  commerce_pos_location_add_float($form['#location_id'], $form_state['values']['amount'] * 100);
}
