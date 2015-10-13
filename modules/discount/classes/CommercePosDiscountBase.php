<?php

/**
 * @file
 * CommercePosDiscountBase.php class definition.
 *
 * CommercePosDiscountBase is responsible for applying discounts to a POS
 * transaction's order.
 *
 * The majority of this classes' methods have been taken from the
 * commerce_discount module, as unfortunately the original functions could not
 * be used without modifications.
 */

class CommercePosDiscountBase extends CommercePosTransactionBase implements CommercePosTransactionBaseInterface {

  // Used to keep track of whether or not a discount has already been applied
  // to a line item or order.
  const LINE_ITEM_DISCOUNT_NAME = 'pos_line_item_discount';
  const ORDER_DISCOUNT_NAME = 'pos_order_discount';

  public function actions() {
    $actions = parent::actions();

    $actions += array(
      'addOrderDiscount',
    );

    return $actions;
  }

  public function subscriptions() {
    $subscriptions = parent::subscriptions();

    $subscriptions['after'] += array(
      'deleteLineItemAfter',
    );

    return $subscriptions;
  }

  /**
   * Adds a discount to the transaction's order.
   *
   * @param $type
   * @param $amount
   */
  public function addOrderDiscount($type, $amount) {
    if ($wrapper = $this->transaction->getOrderWrapper()) {
      switch ($type) {
        case 'fixed':
          $this->applyFixedDiscount($wrapper, $amount);
          break;

        case 'percent':
          $this->applyPercentDiscount($wrapper, $amount);
          break;
      }

      $wrapper->save();
    }
  }

  /**
   * Act upon a line item being deleted.
   *
   * This will check to see if the only remaining line item in the order is
   * a POS discount and will remove it if needed.
   */
  public function deleteLineItemAfter($line_item_id, $result) {
    if ($wrapper = $this->transaction->getOrderWrapper()) {
      if (count($wrapper->commerce_line_items) == 1) {
        if ($wrapper->commerce_line_items[0]->type->value() == 'commerce_pos_discount') {
          commerce_line_item_delete($wrapper->commerce_line_items[0]->line_item_id);
          $wrapper->commerce_line_items->offsetUnset(0);
        }
      }
    }
  }

  /**
   * Adds a discount to a specific line item in the transaction order.
   */
  public function addLineItemDiscount($type, $line_item_id, $amount) {
    if ($line_item = $this->transaction->doAction('getLineItem', $line_item_id)) {
      $wrapper = entity_metadata_wrapper('commerce_line_item', $line_item);

      switch ($type) {
        case 'fixed':
          $this->applyFixedDiscount($wrapper, $amount);
          break;

        case 'percent':
          $this->applyPercentDiscount($wrapper, $amount);
          break;
      }

      $wrapper->save();
    }
  }

  /**
   * Retrieves the existing amount for a discount on a line item, if one exists.
   */
  public function getExistingLineItemDiscountAmount($line_item_id, $discount_name) {
    if ($line_item = $this->transaction->doAction('getLineItem', $line_item_id)) {

      $line_item_wrapper = entity_metadata_wrapper('commerce_line_item', $line_item);
      $data = (array) $line_item_wrapper->commerce_unit_price->data->value() + array('components' => array());

      // Look for our discount in each of the price components.
      foreach ($data['components'] as $key => $component) {
        if (!empty($component['price']['data']['discount_name'])) {
          if ($component['price']['data']['discount_name'] == $discount_name) {
            // Found our discount, return its amount.
            return number_format(abs($component['price']['amount'] / 100), 2);
          }
        }
      }
    }

    return 0;
  }

  /**
   * Retrieves the existing amount for a transaction order's discount amount.
   */
  public function getExistingOrderDiscountAmount() {
    if ($order_wrapper = $this->transaction->getOrderWrapper()) {
      foreach ($order_wrapper->commerce_line_items as $line_item_wrapper) {
        if ($line_item_wrapper->type->value() == 'commerce_pos_discount') {
          return number_format(abs($line_item_wrapper->commerce_unit_price->amount->value() / 100), 2);
        }
      }
    }

    return 0;
  }

  /**
   * Retrieves a display name for a specific discount type.
   */
  protected function getDiscountComponentTitle($discount_name) {
    switch ($discount_name) {
      case self::LINE_ITEM_DISCOUNT_NAME:
        return t('Line item discount');

      case self::ORDER_DISCOUNT_NAME:
        return t('Order discount');

      default:
        return FALSE;
    }
  }

  /**
   * A modified version of commerce_discount_percentage().
   */
  protected function applyPercentDiscount(EntityDrupalWrapper $wrapper, $rate) {
    // Get the line item types to apply the discount to.
    $line_item_types = variable_get('commerce_discount_line_item_types', array('product' => 'product'));

    if ($rate > 1) {
      $rate = $rate / 100;
    }

    switch ($wrapper->type()) {
      case 'commerce_order':

        $discount_name = self::ORDER_DISCOUNT_NAME;

        $calculated_discount = 0;
        // Loop the line items of the order and calculate the total discount.
        foreach ($wrapper->commerce_line_items as $line_item_wrapper) {
          if (!empty($line_item_types[$line_item_wrapper->type->value()])) {
            $line_item_total = commerce_price_wrapper_value($line_item_wrapper, 'commerce_total', TRUE);
            $calculated_discount += $line_item_total['amount'] * $rate;
          }
        }

        if ($calculated_discount) {
          $discount_amount = array(
            'amount' => $calculated_discount * -1,
            'currency_code' => $wrapper->commerce_order_total->currency_code->value(),
          );

          // Modify the existing discount line item or add a new line item
          // if that fails.
          if (!$this->setExistingLineItemPrice($wrapper, $discount_name, $discount_amount)) {
            $this->addLineItem($wrapper, $discount_name, $discount_amount);
          }
        }
        else {
          // Discount amount is 0, make sure we remove any existing POS
          // discounts on the order.
          $this->removeOrderDiscountLineItems();
        }

        break;

      case 'commerce_line_item':
        $discount_name = self::LINE_ITEM_DISCOUNT_NAME;

        // Check if the line item is configured in the settings to apply the
        // discount.
        if (empty($line_item_types[$wrapper->getBundle()])) {
          return;
        }

        // Remove any existing discount components on the line item.
        $this->removeDiscountComponents($wrapper->commerce_unit_price, $discount_name);

        $unit_price = commerce_price_wrapper_value($wrapper, 'commerce_unit_price', TRUE);
        $calculated_discount = $unit_price['amount'] * $rate * -1;

        if ($calculated_discount) {
          $discount_amount = array(
            'amount' => $calculated_discount,
            'currency_code' => $unit_price['currency_code'],
          );

          $this->addPriceComponent($wrapper, $discount_name, $discount_amount);
        }
        break;
    }
  }

  /**
   * A modified version of commerce_discount_fixed_amount().
   */
  protected function applyFixedDiscount(EntityDrupalWrapper $wrapper, $discount_amount) {
    $discount_price['amount'] = -$discount_amount;

    switch ($wrapper->type()) {
      case 'commerce_order':

        $discount_name = self::ORDER_DISCOUNT_NAME;

        if ($discount_amount) {
          $discount_price['currency_code'] = $wrapper->commerce_order_total->currency_code->value();

          // If the discount will bring the order to less than zero, set the
          // discount amount so that it stops at zero.
          $order_amount = $wrapper->commerce_order_total->amount->value();
          if (-$discount_price['amount'] > $order_amount) {
            $discount_price['amount'] = -$order_amount;
          }

          // Modify the existing discount line item or add a new one if that fails.
          if (!$this->setExistingLineItemPrice($wrapper, $discount_name, $discount_price)) {
            $this->addLineItem($wrapper, $discount_name, $discount_price);
          }
        }
        else {
          // Discount amount is 0, make sure we remove any existing POS
          // discounts on the order.
          $this->removeOrderDiscountLineItems();
        }

        break;

      case 'commerce_line_item':

        $discount_name = self::LINE_ITEM_DISCOUNT_NAME;

        // Remove any existing discount components on the line item.
        $this->removeDiscountComponents($wrapper->commerce_unit_price, $discount_name);

        if ($discount_amount) {

          // Do not allow negative line item totals.
          $line_item_amount = $wrapper->commerce_unit_price->amount->value();
          if (-$discount_price['amount'] > $line_item_amount) {
            $discount_price['amount'] = -$line_item_amount;
          }

          $discount_price['currency_code'] = $wrapper->commerce_unit_price->currency_code->value();

          $this->addPriceComponent($wrapper, $discount_name, $discount_price);
        }
        break;
    }
  }

  /**
   * Updates the unit price of an existing discount line item.
   *
   * Non-discount line items are ignored.
   *
   * @param EntityDrupalWrapper $order_wrapper
   *   The wrapped order entity.
   * @param string $discount_name
   *   The name of the discount being applied.
   * @param array $discount_price
   *   The discount amount price array (amount, currency_code).
   *
   * @return bool
   *   TRUE if an existing line item was successfully modified, FALSE otherwise.
   */
  protected function setExistingLineItemPrice(EntityDrupalWrapper $order_wrapper, $discount_name, $discount_price) {
    $modified_existing = FALSE;
    foreach ($order_wrapper->commerce_line_items as $line_item_wrapper) {
      if ($line_item_wrapper->getBundle() == 'commerce_pos_discount') {
        // Add the discount component price if the line item was originally
        // added by discount module.
        $line_item = $line_item_wrapper->value();
        if (isset($line_item->data['discount_name']) && $line_item->data['discount_name'] == $discount_name) {
          $this->setPriceComponent($line_item_wrapper, $discount_name, $discount_price);
          $line_item_wrapper->save();
          $modified_existing = TRUE;
        }
      }
    }

    return $modified_existing;
  }

  /**
   * Creates a discount line item on the provided order.
   *
   * @param EntityDrupalWrapper $order_wrapper
   *   The wrapped order entity.
   * @param string $discount_name
   *   The name of the discount being applied.
   * @param array $discount_amount
   *   The discount amount price array (amount, currency_code).
   */
  protected function addLineItem(EntityDrupalWrapper $order_wrapper, $discount_name, $discount_amount) {
    // Create a new line item.
    $values = array(
      'type' => 'commerce_pos_discount',
      'order_id' => $order_wrapper->order_id->value(),
      'quantity' => 1,
      // Flag the line item.
      'data' => array('discount_name' => $discount_name),
    );
    $discount_line_item = entity_create('commerce_line_item', $values);
    $discount_line_item_wrapper = entity_metadata_wrapper('commerce_line_item', $discount_line_item);

    // Initialize the line item unit price.
    $discount_line_item_wrapper->commerce_unit_price->amount = 0;
    $discount_line_item_wrapper->commerce_unit_price->currency_code = $discount_amount['currency_code'];

    // Reset the data array of the line item total field to only include a
    // base price component, set the currency code from the order.
    $base_price = array(
      'amount' => 0,
      'currency_code' => $discount_amount['currency_code'],
      'data' => array(),
    );
    $discount_line_item_wrapper->commerce_unit_price->data = commerce_price_component_add($base_price, 'base_price', $base_price, TRUE);

    // Add the discount price component.
    $this->addPriceComponent($discount_line_item_wrapper, $discount_name, $discount_amount);

    // Save the line item and add it to the order.
    $discount_line_item_wrapper->save();
    $order_wrapper->commerce_line_items[] = $discount_line_item_wrapper;
  }

  /**
   * Sets a discount price component to the provided line item.
   *
   * @param EntityDrupalWrapper $line_item_wrapper
   *   The wrapped line item entity.
   * @param string $discount_name
   *   The name of the discount being applied.
   * @param array $discount_amount
   *   The discount amount price array (amount, currency_code).
   */
  protected function setPriceComponent(EntityDrupalWrapper $line_item_wrapper, $discount_name, $discount_amount) {
    $unit_price = commerce_price_wrapper_value($line_item_wrapper, 'commerce_unit_price', TRUE);
    // Currencies don't match, abort.
    if ($discount_amount['currency_code'] != $unit_price['currency_code']) {
      return;
    }

    $discount_amount['data'] = array(
      'discount_name' => $discount_name,
      'pos_discount_component_title' => $this->getDiscountComponentTitle($discount_name),
    );

    // Set the new unit price.
    $line_item_wrapper->commerce_unit_price->amount = $discount_amount['amount'];

    // Add the discount amount as a price component.
    $price = $line_item_wrapper->commerce_unit_price->value();
    $type = check_plain('discount|' . $discount_name);
    $line_item_wrapper->commerce_unit_price->data = commerce_price_component_add($price, $type, $discount_amount, TRUE, TRUE);

    // Update the line item total.
    $this->updateLineItemTotal($line_item_wrapper);
  }

  /**
   * Adds a discount price component to the provided line item.
   *
   * @param EntityDrupalWrapper $line_item_wrapper
   *   The wrapped line item entity.
   * @param string $discount_name
   *   The name of the discount being applied.
   * @param array $discount_amount
   *   The discount amount price array (amount, currency_code).
   */
  protected function addPriceComponent(EntityDrupalWrapper $line_item_wrapper, $discount_name, $discount_amount) {
    $unit_price = commerce_price_wrapper_value($line_item_wrapper, 'commerce_unit_price', TRUE);
    $current_amount = $unit_price['amount'];
    // Currencies don't match, abort.
    if ($discount_amount['currency_code'] != $unit_price['currency_code']) {
      return;
    }

    // Calculate the updated amount and create a price array representing the
    // difference between it and the current amount.
    $updated_amount = commerce_round(COMMERCE_ROUND_HALF_UP, $current_amount + $discount_amount['amount']);

    $difference = array(
      'amount' => commerce_round(COMMERCE_ROUND_HALF_UP, $discount_amount['amount']),
      'currency_code' => $discount_amount['currency_code'],
      'data' => array(
        'discount_name' => $discount_name,
        'pos_discount_component_title' => $this->getDiscountComponentTitle($discount_name),
      ),
    );

    // Set the new unit price.
    $line_item_wrapper->commerce_unit_price->amount = $updated_amount;
    // Add the discount amount as a price component.
    $price = $line_item_wrapper->commerce_unit_price->value();
    $type = check_plain('discount|' . $discount_name);
    $line_item_wrapper->commerce_unit_price->data = commerce_price_component_add($price, $type, $difference, TRUE, TRUE);

    // Update the line item total.
    $this->updateLineItemTotal($line_item_wrapper);
  }

  /**
   * Update commerce_total without saving line item.
   *
   * To have the order total refreshed without saving the line item.
   * Taken from CommerceLineItemEntityController::save().
   */
  protected function updateLineItemTotal($line_item_wrapper) {
    $quantity = $line_item_wrapper->quantity->value();

    // Update the total of the line item based on the quantity and unit price.
    $unit_price = commerce_price_wrapper_value($line_item_wrapper, 'commerce_unit_price', TRUE);

    $line_item_wrapper->commerce_total->amount = $quantity * $unit_price['amount'];
    $line_item_wrapper->commerce_total->currency_code = $unit_price['currency_code'];

    // Add the components multiplied by the quantity to the data array.
    if (empty($unit_price['data']['components'])) {
      $unit_price['data']['components'] = array();
    }
    else {
      foreach ($unit_price['data']['components'] as $key => &$component) {
        $component['price']['amount'] *= $quantity;
      }
    }

    // Set the updated data array to the total price.
    $line_item_wrapper->commerce_total->data = $unit_price['data'];
    // Reset the cache because we aren't saving it.
    entity_get_controller('commerce_line_item')->resetCache(array($line_item_wrapper->getIdentifier()));
  }

  /**
   * Removes all POS discount line items from an order.
   */
  protected function removeOrderDiscountLineItems() {
    if ($order_wrapper = $this->transaction->getOrderWrapper()) {
      $line_items_to_delete = array();

      foreach ($order_wrapper->commerce_line_items as $delta => $line_item_wrapper) {
        if ($line_item_wrapper->type->value() == 'commerce_pos_discount') {
          $order_wrapper->commerce_line_items->offsetUnset($delta);
          $line_items_to_delete[] = $line_item_wrapper->line_item_id->value();
        }
      }

      if ($line_items_to_delete) {
        commerce_line_item_delete_multiple($line_items_to_delete);
      }
    }
  }

  /**
   * Remove discount components from a given price and recalculate the total.
   *
   * @param object $price_wrapper
   *   Wrapped commerce price.
   */
  protected function removeDiscountComponents($price_wrapper, $discount_name_to_remove) {
    $data = (array) $price_wrapper->data->value() + array('components' => array());
    $component_removed = FALSE;
    // Remove price components belonging to order discounts.
    foreach ($data['components'] as $key => $component) {
      $remove = FALSE;

      // Remove all discount components.
      if (!empty($component['price']['data']['discount_name'])) {
        $discount_name = $component['price']['data']['discount_name'];

        if ($discount_name_to_remove == $discount_name) {
          $remove = TRUE;
        }
      }

      if ($remove) {
        unset($data['components'][$key]);
        $component_removed = TRUE;
      }
    }
    // Don't alter the price components if no components were removed.
    if (!$component_removed) {
      return;
    }

    // Re-save the price without the discounts (if existed).
    $price_wrapper->data->set($data);

    // Re-set the total price.
    $total = commerce_price_component_total($price_wrapper->value());
    $price_wrapper->amount->set($total['amount']);
  }
}
