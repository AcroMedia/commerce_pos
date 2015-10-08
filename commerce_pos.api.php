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
 * invokeBaseMethod method.
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
function hook_commerce_pos_sale_form_ajax_alter(&$form_state, $triggering_element) {

}

/**
 * Allows modules to find products being searched for in the POS.
 *
 * Modules should return one of the following:
 *
 * 1) An array of product IDs. It is then assumed that the POS users should be
 * presented with these products and potential options for them to add to the
 * transaction order.
 *
 * 2) A product ID integer. It is then assumed that the product ID is an
 * exact match and will automatically be added to the transaction order.
 *
 * @param $result
 *   Any prior result that another module implementing this hook has found.
 *   If no results have been found yet, this will be NULL.
 * @param $keywords
 *   The keywords for the search
 */
function hook_commerce_pos_product_lookup_alter(&$result, $keywords) {

}
