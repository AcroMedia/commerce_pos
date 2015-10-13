<?php

/**
 * @file
 * PosTransactionBaseActions class definition.
 *
 * @TODO: most of the methods in the CommercePosTransaction class should be moved into here.
 */

class CommercePosTransactionBaseActions extends CommercePosTransactionBase implements CommercePosTransactionBaseInterface {

  public function actions() {
    $actions = parent::actions();

    $actions += array(
      'getLineItem',
      'deleteLineItem',
      'park',
      'save',
      'saveOrder',
      'setOrderCustomer',
      'createNewOrder',
    );

    return $actions;
  }

  /**
   * Saves the transaction to the database.
   */
  public function save() {
    $transaction = $this->transaction;

    $transaction_array = array(
      'transaction_id' => $transaction->transactionId,
      'uid' => $transaction->uid,
      'order_id' => $transaction->orderId,
      'type' => $transaction->type,
      'data' => $transaction->data,
      'location_id' => $transaction->locationId,
    );

    if ($transaction->transactionId) {
      $primary_keys = 'transaction_id';
    }
    else {
      $primary_keys = array();
    }

    drupal_write_record($transaction::TABLE_NAME, $transaction_array, $primary_keys);
    $transaction->transactionId = $transaction_array['transaction_id'];
    unset($transaction_array);
  }

  /**
   * Creates a commerce order for this transaction.
   */
  function createNewOrder() {
    $transaction = $this->transaction;

    if (!empty($transaction->orderId)) {
      throw new Exception(t('Cannot create order for transaction @id, an order with @order_id already exists!', array(
        '@id' => $transaction->transactionId,
        '@order_id' => $transaction->orderId,
      )));
    }
    else {
      $order = commerce_order_new($transaction->uid, 'commerce_pos_in_progress');
      $order->uid = 0;
      $order_wrapper = entity_metadata_wrapper('commerce_order', $order);

      // Create new default billing profile.
      $billing_profile = entity_create('commerce_customer_profile', array('type' => 'billing'));
      $profile_wrapper = entity_metadata_wrapper('commerce_customer_profile', $billing_profile);

      // @TODO: make the state configurable.
      $profile_wrapper->commerce_customer_address->administrative_area->set('CA');
      $profile_wrapper->save();

      $order_wrapper->commerce_customer_billing->set($billing_profile);

      commerce_order_save($order);

      $transaction->orderId = $order->order_id;
      $transaction->setOrder($order);
      $transaction->doAction('save');

      return $transaction->getOrder();
    }
  }

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
