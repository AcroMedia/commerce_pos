<?php

/**
 * @file
 * Theme callbacks for Commerce POS Cash Input.
 */

/**
 * Implements template_preprocess_commerce_pos_keypad_cash_input_box().
 */
function commerce_pos_keypad_preprocess_commerce_pos_keypad_cash_input_box(&$variables) {
  $variables['inputs'] = array(
    'penny' => array(
      'title' => t('Penny'),
      'amount' => 1,
    ),
    'nickel' => array(
      'title' => t('Nickel'),
      'amount' => 5,
    ),
    'dime' => array(
      'title' => t('Dime'),
      'amount' => 10,
    ),
    'quarter' => array(
      'title' => t('Quarter'),
      'amount' => 25,
    ),
    '1' => array(
      'title' => t('$1'),
      'amount' => 100,
    ),
    '5' => array(
      'title' => t('$5'),
      'amount' => 500,
    ),
    '10' => array(
      'title' => t('$10'),
      'amount' => 1000,
    ),
    '20' => array(
      'title' => t('$20'),
      'amount' => 2000,
    ),
    '50' => array(
      'title' => t('$50'),
      'amount' => 5000,
    ),
    '100' => array(
      'title' => t('$100'),
      'amount' => 10000,
    ),
  );
}
