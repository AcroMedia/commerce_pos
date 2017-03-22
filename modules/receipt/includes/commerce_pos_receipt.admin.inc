<?php

/**
 * @file
 * commerce_pos_receipt.admin.inc
 */

/**
 * Callback for the Commerce POS Receipt settings form.
 */
function commerce_pos_receipt_settings($form, &$form_state) {
  $header = variable_get('commerce_pos_receipt_header', NULL);
  $footer = variable_get('commerce_pos_receipt_footer', NULL);
  $status_options = array();

  foreach (commerce_payment_transaction_statuses() as $name => $info) {
    $status_options[$name] = $info['title'];
  }

  $form['commerce_pos_receipt_visible_payment_status'] = array(
    '#type' => 'checkboxes',
    '#title' => t('Visible payment transaction statuses'),
    '#options' => $status_options,
    '#default_value' => variable_get('commerce_pos_receipt_visible_payment_status', array(COMMERCE_PAYMENT_STATUS_SUCCESS, COMMERCE_PAYMENT_STATUS_VOID)),
    '#multiple' => TRUE,
    '#description' => t('The payment statuses that should show up on a receipt'),
  );

  $form['commerce_pos_receipt_header'] = array(
    '#type' => 'text_format',
    '#title' => t('Header text'),
    '#description' => t('This text will appear at the top of printed receipts.'),
    '#default_value' => $header ? $header['value'] : NULL,
    '#format' => $header ? $header['format'] : NULL,
  );

  $form['commerce_pos_receipt_footer'] = array(
    '#type' => 'text_format',
    '#title' => t('Footer text'),
    '#description' => t('This text will appear at the bottom of printed receipts.'),
    '#default_value' => $footer ? $footer['value'] : NULL,
    '#format' => $footer ? $footer['format'] : NULL,
  );

  return system_settings_form($form);
}
