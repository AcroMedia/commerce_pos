<?php

/**
 * @file
 * Contains preprocess functions for the commerce_pos_keypad module.
 */

/**
 * Implements template_preprocess_commerce_pos_keypad_cash_input_box().
 */
function commerce_pos_keypad_preprocess_commerce_pos_keypad_cash_input_box(&$variables) {
  $variables['inputs'] = [];

  /** @var \Drupal\commerce_pos_currency_denominations\Entity\CurrencyDenominations $denominations */
  $denominations = $variables['denominations'];
  $denoms = $denominations->getDenominations();

  foreach ($denoms as $denomination_id => $denomination) {
    // Not sure why 'actions' is in here.
    if (isset($denomination['amount'])) {
      $variables['inputs'][$denomination_id] = [
        'title' => $denomination['label'],
        'amount' => $denomination['amount'],
      ];
    }
  }
}
