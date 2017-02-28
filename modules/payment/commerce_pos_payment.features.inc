<?php

/**
 * @file
 * commerce_pos_payment.features.inc
 */

/**
 * Implements hook_commerce_custom_offline_payments().
 */
function commerce_pos_payment_commerce_custom_offline_payments() {
  $items = array(
    'credit' => array(
      'id' => 'commerce_pos_payment_credit',
      'title' => 'Credit',
      'description' => '',
      'information' => '',
      'format' => 'plain_text',
      'status' => 1,
      'checkout' => 0,
      'terminal' => 0,
      'fieldable' => 0,
    ),
    'debit' => array(
      'id' => 'commerce_pos_payment_debit',
      'title' => 'Debit',
      'description' => '',
      'information' => '',
      'format' => 'plain_text',
      'status' => 1,
      'checkout' => 0,
      'terminal' => 0,
      'fieldable' => 0,
    ),
    'gift_card' => array(
      'id' => 'commerce_pos_payment_gift_card',
      'title' => 'Gift Card',
      'description' => '',
      'information' => '',
      'format' => 'plain_text',
      'status' => 1,
      'checkout' => 0,
      'terminal' => 0,
      'fieldable' => 0,
    ),
    'cash' => array(
      'id' => 'commerce_pos_payment_cash',
      'title' => 'Cash',
      'description' => '',
      'information' => '',
      'format' => 'plain_text',
      'status' => 1,
      'checkout' => 0,
      'terminal' => 0,
      'fieldable' => 0,
    ),
  );
  return $items;
}
