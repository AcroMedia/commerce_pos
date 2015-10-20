<?php

/**
 * @file
 * CommercePosReceiptBase.php
 */

class CommercePosReceiptBase extends CommercePosTransactionBase implements CommercePosTransactionBaseInterface {

  /**
   * Subscribe to transaction base methods.
   */
  public function subscriptions() {
    $subscriptions = parent::subscriptions();
    $subscriptions['completeTransactionAfter'][] = 'storePreviousTransaction';
    return $subscriptions;
  }

  /**
   * Stores the transaction's ID in the session.
   *
   * This is used so that we can add "Print previous transaction receipt" links
   * on various forms.
   */
  public function storePreviousTransaction() {
    $_SESSION['commerce_pos_discount_previous_transaction'] = $this->transaction->transactionId;
  }
}
