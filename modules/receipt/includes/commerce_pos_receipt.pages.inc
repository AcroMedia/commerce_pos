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

  drupal_add_library('system', 'drupal.ajax');
  drupal_add_library('commerce_pos_receipt', 'jquery-print');
  drupal_add_js(drupal_get_path('module', 'commerce_pos_receipt') . '/js/commerce_pos_receipt.js');

  drupal_add_js(array(
    'commercePosReceipt' => array(
      'cssUrl' => url(drupal_get_path('module', 'commerce_pos_receipt') . '/css/commerce_pos_receipt.css', array(
        'absolute' => TRUE,
      )),
    ),
  ), 'setting');

  $transaction = CommercePosService::loadTransaction($transaction_id);
  $receipt = theme('commerce_pos_receipt', array('transaction' => $transaction));

  $commands[] = array(
    'command' => 'printReceipt',
    'content' => $receipt,
  );

  ajax_deliver(array('#type' => 'ajax', '#commands' => $commands));
}
