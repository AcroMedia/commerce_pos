<?php

/**
 * @file
 * CommercePosCashierBase.php
 */

class CommercePosCashierBase extends CommercePosTransactionBase implements CommercePosTransactionBaseInterface {

  /**
   * Subscribe to transaction base methods.
   */
  public function subscriptions() {
    $subscriptions = parent::subscriptions();

    // When configured, clear the current cashier after a transaction completes.
    if (variable_get('commerce_pos_cashier_transaction_complete_logout')) {
      $subscriptions['completeTransactionAfter'][] = 'logOutCashier';
    }

    return $subscriptions;
  }

  /**
   * Log out the current cashier.
   */
  public function logOutCashier() {
    commerce_pos_cashier_clear_current_cashier();
  }
}
