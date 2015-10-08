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
   * Removes a line item from the transaction order.
   */
  public function deleteLineItem($line_item_id, $skip_save = FALSE) {
    if ($order_wrapper = $this->transaction->getOrderWrapper()) {
      foreach ($order_wrapper->commerce_line_items as $delta => $line_item_wrapper) {
        if ($line_item_wrapper->line_item_id->raw() == $line_item_id) {
          $order_wrapper->commerce_line_items->offsetUnset($delta);
          break;
        }
      }

      if (commerce_line_item_delete($line_item_id) && !$skip_save) {
        $order_wrapper->save();
      }
    }
    else {
      throw new Exception(t('Cannot remove line item, transaction does not have an order created for it.'));
    }
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

  /**
   * Saves the transaction's order.
   */
  public function saveOrder() {
    if ($order = $this->transaction->getOrder()) {
      commerce_order_save($order);
    }
  }

  /**
   * Assigns the transaction's order to a specific user.
   *
   * @param object $user
   *   The Drupal user to associate with the order.
   *
   * @return bool
   *   TRUE or FALSE, depending on whether the order's user was actually
   *   updated.
   */
  public function setOrderCustomer($user) {
    if ($order_wrapper = $this->transaction->getOrder()) {
      if ($order_wrapper->uid != $user->uid) {
        $order_wrapper->uid = $user->uid;
        return TRUE;
      }
    }

    return FALSE;
  }
}
