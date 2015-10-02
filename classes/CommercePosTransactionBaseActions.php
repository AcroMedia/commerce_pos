<?php

/**
 * @file
 * PosTransactionBaseActions class definition.
 */

class CommercePosTransactionBaseActions extends CommercePosTransactionBase {

  /**
   * Marks the transaction order's status as parked.
   */
  public function park() {
    $order = $this->transaction->getOrder();

    if ($order) {
      $order->status = 'commerce_pos_parked';
      commerce_order_save($order);
    }
  }
}
