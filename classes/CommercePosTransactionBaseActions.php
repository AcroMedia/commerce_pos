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
      'setLineItemPrice',
      'updateLineItemQuantity',
      'addProduct',
      'getLineItems',
    );

    return $actions;
  }

  public function events() {
    $events = parent::events();
    $events += array(
      'lineItemUpdated',
    );
    return $events;
  }

  /**
   * Saves the transaction to the database.
   */
  public function save() {
    $transaction = $this->transaction;
    $transaction->changed = REQUEST_TIME;

    $transaction_array = array(
      'transaction_id' => $transaction->transactionId,
      'uid' => $transaction->uid,
      'cashier' => $transaction->cashier,
      'order_id' => $transaction->orderId,
      'type' => $transaction->type,
      'data' => $transaction->data,
      'register_id' => $transaction->registerId,
      'changed' => $transaction->changed,
      'created' => $transaction->created,
      'completed' => $transaction->completed,
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
   * Marks the transaction order's status as parked.
   */
  public function park() {
    if ($order = $this->transaction->getOrder()) {
      $order->status = 'commerce_pos_parked';
      $this->transaction->doAction('saveOrder');
    }
  }

  /**
   * Creates a commerce order for this transaction.
   */
  function createNewOrder() {
    $transaction = $this->transaction;

    if (!empty($transaction->orderId)) {
      throw new Exception(t('Cannot create order for transaction @id, it is already associated with order #@order_id.', array(
        '@id' => $transaction->transactionId,
        '@order_id' => $transaction->orderId,
      )));
    }
    else {
      $order = commerce_order_new($transaction->uid, 'commerce_pos_in_progress');
      $order->uid = 0;
      $order_wrapper = entity_metadata_wrapper('commerce_order', $order);

      $administrative_area = NULL;

      // Let other modules decide what the default province/state should be.
      drupal_alter('commerce_pos_transaction_state', $administrative_area, $this->transaction);

      if (!empty($administrative_area)) {
        // Create new default billing profile.
        $billing_profile = entity_create('commerce_customer_profile', array('type' => 'billing'));
        $profile_wrapper = entity_metadata_wrapper('commerce_customer_profile', $billing_profile);

        $profile_wrapper->commerce_customer_address->set(array(
          'administrative_area' => $administrative_area,
        ));

        $profile_wrapper->save();
        $order_wrapper->commerce_customer_billing->set($billing_profile);
      }

      commerce_order_save($order);

      $transaction->orderId = $order->order_id;
      $transaction->setOrder($order);
      $transaction->doAction('save');

      return $transaction->getOrder();
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
   * Adds the specified product to transaction order.
   */
  public function addProduct($product, $quantity = 1, $combine = TRUE) {
    if (!in_array($product->type, CommercePosService::allowedProductTypes())) {
      return FALSE;
    }

    // First attempt to load the transaction's order.
    // If no order existed, create one now.
    if (!($order = $this->transaction->getOrder())) {
      $order = $this->transaction->doAction('createNewOrder');
    }

    // If the specified product exists...
    // Create a new product line item for it.
    $line_item = commerce_product_line_item_new($product, $quantity, $order->order_id);
    $line_item_wrapper = entity_metadata_wrapper('commerce_line_item', $line_item);

    rules_invoke_event('commerce_product_calculate_sell_price', $line_item);

    $amount = $line_item_wrapper->commerce_unit_price->amount->raw();
    $currency = $line_item_wrapper->commerce_unit_price->currency_code->raw();

    // We "snapshot" the calculated sell price and use it as the line item's
    // base price.
    $unit_price = array(
      'amount' => $amount,
      'currency_code' => $currency,
    );

    $unit_price['data'] = commerce_price_component_add($unit_price, 'base_price', array(
      'amount' => $amount,
      'currency_code' => $currency,
      'data' => array(),
    ), TRUE, FALSE);

    $line_item_wrapper->commerce_unit_price->set($unit_price);

    if (module_exists('commerce_pricing_attributes')) {
      // Hack to prevent the combine logic in addLineItem()
      // from incorrectly thinking that the newly-added line item is different than
      // previously-added line items.
      $line_item->commerce_pricing_attributes = serialize(array());
    }

    if (module_exists('commerce_tax')) {
      foreach (commerce_tax_types() as $name => $type) {
        commerce_tax_calculate_by_type($line_item, $name);
      }
    }

    return $this->addLineItem($line_item, $combine);
  }

  /**
   * Retrieves the line items from this transaction's order, if it has any.
   */
  public function getLineItems() {
    $line_items = array();

    if ($order = $this->transaction->getOrder()) {
      $line_items = field_get_items('commerce_order', $order, 'commerce_line_items');
    }

    return $line_items;
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
   * Sets the price of a line item in the transaction's order to a specific price.
   *
   * @param int $line_item_id
   *   The ID of the line item in the transaction order to change the price of.
   * @param int $price
   *   The new price in cents.
   */
  public function setLineItemPrice($line_item_id, $price) {
    foreach ($this->transaction->getLineItems() as $order_line_item) {
      if ($order_line_item['line_item_id'] == $line_item_id) {
        $line_item = commerce_line_item_load($line_item_id);
        $line_item_wrapper = entity_metadata_wrapper('commerce_line_item', $line_item);
        $unit_price = commerce_price_wrapper_value($line_item_wrapper, 'commerce_unit_price', TRUE);

        // Change the base_price
        $unit_price['amount'] = $price;
        $unit_price['data']['components'][0]['amount'] = $price;

        $line_item_wrapper->commerce_unit_price->set($unit_price);
        commerce_line_item_rebase_unit_price($line_item);
        $line_item_wrapper->save();

        $this->transaction->invokeEvent('lineItemUpdated');
        break;
      }
    }
  }

  /**
   * Updates the quantity of a line item in the transactions' order.
   */
  function updateLineItemQuantity($line_item_id, $qty, $method = 'replace') {
    if ($order = $this->transaction->getOrder()) {
      $line_item = commerce_line_item_load($line_item_id);
      $existing_qty = $line_item->quantity;

      if ($method == 'update') {
        $new_qty = $existing_qty + $qty;
      }
      else {
        $new_qty = $qty;
      }

      // Make sure the line item actually belongs to the order.
      if ($new_qty > 0 && ($line_item->order_id == $order->order_id) && ((int) $existing_qty != $new_qty)) {
        $line_item->quantity = $new_qty;
        commerce_line_item_save($line_item);
        $this->transaction->invokeEvent('lineItemUpdated');
      }
      elseif ($new_qty == 0) {
        $this->transaction->doAction('deleteLineItem', $line_item_id);
      }
    }
    else {
      throw new Exception(t('Cannot update line item @id quantity, the transaction does not have an order created.', array(
        '@id' => $line_item_id,
      )));
    }
  }

  /**
   * Removes a line item from the transaction order.
   */
  public function deleteLineItem($line_item_id, $skip_save = FALSE) {
    if ($order_wrapper = $this->transaction->getOrderWrapper()) {
      $line_item_found = FALSE;

      foreach ($order_wrapper->commerce_line_items as $delta => $line_item_wrapper) {
        if ($line_item_wrapper->line_item_id->raw() == $line_item_id) {
          $order_wrapper->commerce_line_items->offsetUnset($delta);
          $line_item_found = TRUE;
          break;
        }
      }

      if ($line_item_found) {
        if (commerce_line_item_delete($line_item_id) && !$skip_save) {
          $this->transaction->doAction('saveOrder');
        }

        $this->transaction->invokeEvent('lineItemUpdated');
      }
      else {
        throw new Exception(t('Cannot remove line item, the order does not have a line item with ID @id', array(
          '@id' => $line_item_id,
        )));
      }
    }
    else {
      throw new Exception(t('Cannot remove line item, transaction does not have an order created for it.'));
    }
  }

  /**
   * Completes a transaction by updating its order status and make any other
   * adjustments as needed.
   */
  public function completeTransaction() {
    if ($order_wrapper = $this->transaction->getOrderWrapper()) {
      $this->checkPaymentTransactions($order_wrapper);

      if ($this->transaction->type == CommercePosService::TRANSACTION_TYPE_RETURN) {
        $order_wrapper->status->set('commerce_pos_returned');
      }
      else {
        $order_wrapper->status->set('completed');
      }

      $order_uid = $order_wrapper->uid->value();
      if (empty($order_uid)) {
        if ($account = $this->createNewUser($order_wrapper)) {
          $order_wrapper->uid->set($account->uid);
        }
      }

      $this->transaction->completed = REQUEST_TIME;
      $this->transaction->doAction('save');
      $this->transaction->doAction('saveOrder');

      rules_invoke_event('commerce_pos_transaction_completed', $this->transaction->getOrder(), $this->transaction->type);
    }
  }

  /**
   * Creates a new user for a customer.
   */
  protected function createNewUser(EntityDrupalWrapper $order_wrapper, $send_email = TRUE) {
    if (!empty($this->transaction->data['customer email'])) {
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

      if ($send_email) {
        drupal_mail('user', 'register_admin_created', $account->mail, user_preferred_language($account));
      }

      return $account;
    }
    else {
      return FALSE;
    }
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

  /**
   * Adds the specified product to the transaction's order.
   *
   * @param $line_item
   *   An unsaved product line item to be added to the cart with the following data
   *   on the line item being used to determine how to add the product to the cart:
   *   - $line_item->commerce_product: reference to the product to add to the cart.
   *   - $line_item->quantity: quantity of this product to add to the cart.
   *   - $line_item->data: data array that is saved with the line item if the line
   *     item is added to the cart as a new item; merged into an existing line
   *     item if combination is possible.
   *   - $line_item->order_id: this property does not need to be set when calling
   *     this function, as it will be set to the specified user's current cart
   *     order ID.
   *   Additional field data on the line item may be considered when determining
   *   whether or not line items can be combined in the cart. This includes the
   *   line item type, referenced product, and any line item fields that have been
   *   exposed on the Add to Cart form.
   * @param $combine
   *   Boolean indicating whether or not to combine like products on the same line
   *   item, incrementing an existing line item's quantity instead of adding a
   *   new line item to the cart order. When the incoming line item is combined
   *   into an existing line item, field data on the existing line item will be
   *   left unchanged. Only the quantity will be incremented and the data array
   *   will be updated by merging the data from the existing line item onto the
   *   data from the incoming line item, giving precedence to the most recent data.
   *
   * @return null The new or updated line item object or FALSE on failure.
   * The new or updated line item object or FALSE on failure.
   *
   * @throws \EntityMetadataWrapperException
   * @throws \Exception
   */
  protected function addLineItem($line_item, $combine) {
    // Do not add the line item if it doesn't have a unit price.
    $line_item_wrapper = entity_metadata_wrapper('commerce_line_item', $line_item);

    if (is_null($line_item_wrapper->commerce_unit_price->value())) {
      return FALSE;
    }

    // First attempt to load the customer's shopping cart order.
    // If no order existed, create one now.
    if (!($order = $this->transaction->getOrder())) {
      throw new Exception(t('Cannot add line item to transaction, it does not have an order created.'));
    }

    // Set the incoming line item's order_id.
    $line_item->order_id = $order->order_id;

    // Wrap the order for easy access to field data.
    $order_wrapper = entity_metadata_wrapper('commerce_order', $order);

    // Extract the product and quantity we're adding from the incoming line item.
    $product = $line_item_wrapper->commerce_product->value();
    $quantity = $line_item->quantity;

    // Invoke the product prepare event with the shopping cart order.
    // @TODO: do the same for POS?
    rules_invoke_all('commerce_cart_product_prepare', $order, $product, $line_item->quantity);

    // Determine if the product already exists on the order and increment its
    // quantity instead of adding a new line if it does.
    $matching_line_item = NULL;

    // If we are supposed to look for a line item to combine into...
    if ($combine) {
      // Generate an array of properties and fields to compare.
      $comparison_properties = array('type', 'commerce_product');

      // Add any field that was exposed on the Add to Cart form to the array.
      // TODO: Bypass combination when an exposed field is no longer available but
      // the same base product is added to the cart.
      foreach (field_info_instances('commerce_line_item', $line_item->type) as $info) {
        if (!empty($info['commerce_cart_settings']['field_access'])) {
          $comparison_properties[] = $info['field_name'];
        }
      }

      // Allow other modules to specify what properties should be compared when
      // determining whether or not to combine line items.
      drupal_alter('commerce_cart_product_comparison_properties', $comparison_properties, clone($line_item));

      // Loop over each line item on the order.
      foreach ($order_wrapper->commerce_line_items as $delta => $matching_line_item_wrapper) {
        // Examine each of the comparison properties on the line item.
        foreach ($comparison_properties as $property) {
          // If the property is not present on either line item, bypass it.
          if (!isset($matching_line_item_wrapper->value()->{$property}) && !isset($line_item_wrapper->value()->{$property})) {
            continue;
          }

          // If any property does not match the same property on the incoming line
          // item or exists on one line item but not the other...
          if ((!isset($matching_line_item_wrapper->value()->{$property}) && isset($line_item_wrapper->value()->{$property})) ||
            (isset($matching_line_item_wrapper->value()->{$property}) && !isset($line_item_wrapper->value()->{$property})) ||
            $matching_line_item_wrapper->{$property}->raw() != $line_item_wrapper->{$property}->raw()
          ) {
            // Continue the loop with the next line item.
            continue 2;
          }
        }

        // If every comparison line item matched, combine into this line item.
        $matching_line_item = $matching_line_item_wrapper->value();
        break;
      }
    }

    // If no matching line item was found...
    if (empty($matching_line_item)) {
      // Save the incoming line item now so we get its ID.
      commerce_line_item_save($line_item);

      // Add it to the order's line item reference value.
      $order_wrapper->commerce_line_items[] = $line_item;
    }
    else {
      // Increment the quantity of the matching line item, update the data array,
      // and save it.
      $matching_line_item->quantity += $quantity;
      $matching_line_item->data = array_merge($line_item->data, $matching_line_item->data);

      commerce_line_item_save($matching_line_item);

      // Clear the line item cache so the updated quantity will be available to
      // the ensuing load instead of the original quantity as loaded above.
      entity_get_controller('commerce_line_item')->resetCache(array($matching_line_item->line_item_id));

      // Update the line item variable for use in the invocation and return value.
      $line_item = $matching_line_item;
    }

    // Save the updated order.
    $this->transaction->doAction('saveOrder');

    // Invoke the product add event with the newly saved or updated line item.
    // @TODO: should we invoke this? Thinking no.
    //rules_invoke_all('commerce_cart_product_add', $order, $product, $quantity, $line_item);

    // Return the line item.
    $this->transaction->invokeEvent('lineItemUpdated');
    return $line_item;
  }
}
