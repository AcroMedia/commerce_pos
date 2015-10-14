<?php

/**
 * @file
 * commerce_pos_receipt.admin.inc
 */

function commerce_pos_receipt_settings($form, &$form_state) {
  $header = variable_get('commerce_pos_receipt_header', NULL);
  $footer = variable_get('commerce_pos_receipt_footer', NULL);

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
