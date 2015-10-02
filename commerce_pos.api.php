<?php

/**
 * @file
 * API documentation for commerce_pos.
 */

function hook_commerce_pos_transaction_base_info() {
  return array(
    'commerce_pos_transaction_base_actions' => array(
      'class' => 'PosTransactionBaseActions',
    ),
  );
}
