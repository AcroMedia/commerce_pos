<?php

/**
 * Defines a handler area that provides payment totals and the order balance.
 */
class commerce_pos_handler_area_balance extends views_handler_area {
  function init(&$view, &$options) {
    parent::init($view, $options);

    $this->additional_fields['amount'] = 'amount';
    $this->additional_fields['currency_code'] = 'currency_code';
    $this->additional_fields['status'] = 'status';
  }

  /**
   * Get a value used for rendering.
   *
   * @param $values
   *   An object containing all retrieved values.
   * @param $field
   *   Optional name of the field where the value is stored.
   */
  function get_value($values, $field = NULL) {
    // In this case, a field is required.
    if (!isset($field)) {
      return;
    }
    // Prepare the proper aliases for finding data in the result set.
    $aliases = array(
      'status' => $this->view->query->fields['commerce_payment_transaction_status']['alias'],
      'currency_code' => $this->view->query->fields['commerce_payment_transaction_currency_code']['alias'],
      'amount' => $this->view->query->fields['commerce_payment_transaction_amount']['alias'],
    );

    $alias = $aliases[$field];
    if (isset($values->{$alias})) {
      return $values->{$alias};
    }
  }

  function render($empty = FALSE) {
    // Load an order object for the View if a single order argument is present.
    if (in_array('order_id', array_keys($this->view->argument)) &&
      !in_array('order_id_1', array_keys($this->view->argument)) &&
      !empty($this->view->args[$this->view->argument['order_id']->position])) {

      // Load the specified order.
      $order = commerce_order_load($this->view->args[$this->view->argument['order_id']->position]);
    }
    else {
      // Otherwise indicate a valid order is not present.
      $order = FALSE;
    }

    // Calculate a total of successful payments for each currency.
    $transaction_statuses = commerce_payment_transaction_statuses();
    $totals = array();

    foreach ($this->view->result as $result) {
      $status = $this->get_value($result, 'status');
      $currency_code = $this->get_value($result, 'currency_code');
      $amount = $this->get_value($result, 'amount');

      // If the payment transaction status indicates it should include the
      // current transaction in the total...
      if (!empty($transaction_statuses[$status]) && $transaction_statuses[$status]['total']) {
        // Add the transaction to its currency's running total if it exists...
        if (isset($totals[$currency_code])) {
          $totals[$currency_code] += $amount;
        }
        else {
          // Or begin a new running total for the currency.
          $totals[$currency_code] = $amount;
        }
      }
    }

    // Prepare variables for use in the theme function.
    $variables = array(
      'rows' => commerce_pos_balance_summary_rows($totals, $order),
      'view' => $this->view,
      'totals' => $totals,
      'order' => $order,
    );

    return theme('commerce_pos_transaction_balance', $variables);
  }
}
