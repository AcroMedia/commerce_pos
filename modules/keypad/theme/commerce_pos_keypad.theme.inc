<?php

/**
 * @file
 * Theme callbacks for Commerce POS Cash Input.
 */

/**
 * Implements template_preprocess_commerce_pos_keypad_cash_input_box().
 */
function commerce_pos_keypad_preprocess_commerce_pos_keypad_cash_input_box(&$variables) {
  $variables['inputs'] = array();
  foreach ($variables['denominations'] as $currency_id => $result) {
    foreach ($result as $denomination_id => $denomination) {
      $variables['inputs'][$denomination->denomination_name] = array(
        'title' => $denomination->denomination_title,
        'amount' => $denomination->denomination_amount,
      );
    }
  }
}
