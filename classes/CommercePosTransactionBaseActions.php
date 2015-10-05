<?php

/**
 * @file
 * PosTransactionBaseActions class definition.
 *
 * @TODO: most of the methods in the CommercePosTransaction class should be moved into here.
 */

class CommercePosTransactionBaseActions extends CommercePosTransactionBase {

  /**
   * Retrieves a specific line item from the transaction's order.
   *
   * @param $line_item_id
   *   The line ID to look for in the order.
   *
   * @return bool|object
   *   Either the loaded line item, or FALSE if the line item ID does not exist
   *   in the transaction's order.
   */
  public function getLineItem($line_item_id) {
    if ($order_wrapper = $this->transaction->getOrderWrapper()) {
      foreach ($order_wrapper->commerce_line_items->raw() as $order_line_item_id) {
        if ($line_item_id == $order_line_item_id) {
          return commerce_line_item_load($line_item_id);
        }
      }
    }

    return FALSE;
  }

  /**
   * Marks the transaction order's status as parked.
   */
  public function park() {
    if ($order = $this->transaction->getOrder()) {
      $order->status = 'commerce_pos_parked';
      commerce_order_save($order);
    }
  }
}
