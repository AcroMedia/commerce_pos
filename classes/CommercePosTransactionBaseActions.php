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
      'completeTransaction',
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

  /**
   * Completes a transaction by updating its order status and make any other
   * adjustments as needed.
   */
  public function completeTransaction() {
    // @TODO: this gets messed up because $order_wrapper no longer updates
    // the actual order object in the transaction...
    if ($order_wrapper = $this->transaction->getOrderWrapper()) {
      $this->checkPaymentTransactions($order_wrapper);

      $order_wrapper->status->set('completed');

      if (empty($order_wrapper->uid->value())) {
        $this->createNewUser($order_wrapper);
      }

      $this->transaction->doAction('save');
      $this->transaction->doAction('saveOrder');
    }
  }

  /**
   * Creates a new user for a customer
   */
  protected function createNewUser(EntityDrupalWrapper $order_wrapper) {
    $customer_email = $this->transaction->data['customer email'];

    $order_wrapper->mail->set($customer_email);

    // Have Commerce create a username for us.
    $new_username = commerce_order_get_properties($order_wrapper->value(), array(), 'mail_username');

    $account = entity_create('user', array(
      'name' => $new_username,
      'mail' => $customer_email,
      'status' => 1,
    ));

    user_save($account);

    $order_wrapper->uid->set($account->uid);
    drupal_mail('user', 'register_admin_created', $account->mail, user_preferred_language($account));
  }

  /**
   * Ensures that the transaction order's balance is zero'd out.
   *
   * If the balance is negative, then it means that changed is owed to the
   * customer and we need to create a separate transaction for it.
   */
  protected function checkPaymentTransactions(EntityDrupalWrapper $order_wrapper) {
    $balance = commerce_payment_order_balance($order_wrapper->value());

    if ($balance['amount'] > 0) {
      throw new Exception(t('POS transaction order @order_id cannot be finalized, it has a balance owing', array(
        '@order_id' => $order_wrapper->order_id,
      )));
    }
    elseif ($balance['amount'] < 0) {
      // Change is owed, record it as a separate payment transaction.
      $payment_method = commerce_payment_method_load('commerce_pos_change');
      $transaction = commerce_payment_transaction_new('commerce_pos_change', $order_wrapper->order_id->value());
      $transaction->instance_id = $payment_method['method_id'] . '|commerce_pos';
      $transaction->amount = $balance['amount'];
      $transaction->currency_code = $order_wrapper->commerce_order_total->currency_code->value();
      $transaction->status = COMMERCE_PAYMENT_STATUS_SUCCESS;
      $transaction->message = '';
      commerce_payment_transaction_save($transaction);
    }
  }
}
