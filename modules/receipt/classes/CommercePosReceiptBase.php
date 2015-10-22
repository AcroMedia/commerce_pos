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
   * This is used so that we can add "Print previous transaction receipt" links
   * on various forms.
   */
  public function storePreviousTransaction() {
    $_SESSION['commerce_pos_receipt_previous_transaction'] = $this->transaction->transactionId;

    // Unfortunately a bug with FireFox and the jQuery print plugin prevents us
    // from triggering a receipt print and redirect via AJAX commands, so we
    // pass this session variable along to the next page instead. A bad hack,
    // but apparently a necessary one.
    $_SESSION['commerce_pos_print_transaction'] = $this->transaction->transactionId;
  }
}
