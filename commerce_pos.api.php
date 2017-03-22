<?php

/**
 * @file
 * API documentation for commerce_pos.
 */

/**
 * Allows modules to define custom POS Transaction Base classes.
 *
 * The base classes will be attached to each POS transaction object so that
 * the base classes' methods can be invoked via the transaction object's
 * doAction method.
 *
 * Modules that define their own base class should extend the
 * CommercePosTransactionBase class.
 *
 * Modules implementing this hook should return an associative array of arrays,
 * keyed by a unique machine name for the Base class.
 *
 * Each Base class array can contain the following key/value pairs:
 *
 * - class: The PHP class name of the Base class.
 * - types: (optional) An array of each type of transaction that the base class
 *   will be attached to.
 */
function hook_commerce_pos_transaction_base_info() {
  return array(
    'commerce_pos_transaction_base_actions' => array(
      'class' => 'CommercePosTransactionBaseActions',
      'types' => array(
        CommercePosService::TRANSACTION_TYPE_SALE,
        CommercePosService::TRANSACTION_TYPE_RETURN,
      ),
    ),
  );
}

/**
 * Allows modules to act upon the main POS sales form AJAX submission.
 *
 * While this is technically possible already through the use of hook_form_alter,
 * this hook allows other modules to set $form_state['transaction_updated'] to
 * TRUE to force the form to reload the transaction and recalculate order
 * totals.
 *
 * @param array $form_state
 *   The Drupal form API form state variable.
 * @param array $triggering_element
 *   The element that triggered the AJAX submission. Available directly in the
 *   $form_state variable, but provided for ease-of-use.
 */
function hook_commerce_pos_sale_form_ajax_alter(array &$form_state, array $triggering_element) {

}

/**
 * Allows modules to specify the default state for a POS transaction's order.
 *
 * The state is used in price calculation rules to determine applicable taxes.
 *
 * The administrative_area of the order's billing information will be set to
 * whatever $administrative_area is set to.
 *
 * @param int $administrative_area
 *   The administrative_area to use on the transaction order.
 * @param CommercePosTransaction $transaction
 *   The POS transaction object containing the order.
 */
function hook_commerce_pos_transaction_state_alter(&$administrative_area, CommercePosTransaction $transaction) {
  if (empty($administrative_area)) {
    $administrative_area = 90;
  }
}

/**
 * Allows modules to define payment options available in the Point of Sale.
 *
 * @return array
 *   An array of payment options.
 *   Keys are arbitrary but module short name is suggested. Values are arrays
 *   with the following key/value pairs:
 *   - id: An identifier for the payment option.
 *   - title: A human friendly name for the option.
 */
function hook_commerce_pos_payment_options_info() {
  $options['commerce_pos_example'] = array(
    'id' => 'commerce_pos_example',
    'title' => t('Example'),
  );
  return $options;
}

/**
 * Allows modules to attempt to act on voiding a transaction.
 *
 * @param CommercePosTransaction $transaction
 *   A commerce payment transaction.
 *
 * @return array
 *   An array with the following keys:
 *   - success: bool indicating the success of the void attempt.
 *   - message: Optional string.
 */
function hook_commerce_pos_void_payment_transaction(CommercePosTransaction $transaction) {
  $voided = commerce_pos_void_transaction_example($transaction);
  return array(
    'success' => $voided,
    'message' => t('It @result!', array(
      '@result' => ($voided) ? t('worked') : t('failed'),
    )),
  );
}

/**
 * Allows modules to change/add to the links output in the POS header.
 *
 * @param array $links
 *   An array of links. The key is the path and the value is the title of the
 *   link.
 */
function hook_commerce_pos_header_links_alter(array &$links) {
  $links['admin/commerce/pos/sales'] = t('Sales');
}
