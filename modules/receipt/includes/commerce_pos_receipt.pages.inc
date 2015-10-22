<?php

/**
 * @file
 * Page and form callbacks for commerce_pos_receipt.
 */

/**
 * Callback for printing a transaction receipt.
 *
 * This returns a custom AJAX command to trigger the printing of the receipt
 * via JavaScript. Modules wishing to use this callback will need to make sure
 * that commerce_pos_receipt.js is included on the page.
 *
 * @param int $transaction_id
 *   The ID of the POS transaction to print.
 */
function commerce_pos_receipt_print($transaction_id) {
  $commands = array();

  $transaction = CommercePosService::loadTransaction($transaction_id);
  $receipt = theme('commerce_pos_receipt', array('transaction' => $transaction));

  $commands[] = array(
    'command' => 'printReceipt',
    'content' => $receipt,
  );

  ajax_deliver(array('#type' => 'ajax', '#commands' => $commands));
}
