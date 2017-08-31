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

/**
 * Base class to use for any CommercePosDiscount classes.
 */
class CommercePosDiscountBase extends CommercePosTransactionBase implements CommercePosTransactionBaseInterface {

  /**
   * Base discount action.
   */
  public function actions() {
    $actions = parent::actions();

    $actions += array(
      'addOrderDiscount',
      'addLineItemDiscount',
      'removeLineItemDiscount',
      'getExistingLineItemDiscountAmount',
      'getExistingOrderDiscountAmount',
      'addOrderCoupon',
      'removeOrderCoupon',
    );

    return $actions;
  }

  /**
   * Base Discount specific subscriptions.
   */
  public function subscriptions() {
    $subscriptions = parent::subscriptions();
    $subscriptions['deleteLineItemAfter'][] = 'afterDeleteLineItem';
    $subscriptions['lineItemUpdated'][] = 'lineItemUpdated';
    return $subscriptions;
  }

  /**
   * Adds a discount to the transaction's order.
   *
   * @param string $type
   *   The type of discount to add, currently flat or percent.
   * @param float|int $amount
   *   The amount to add, either in flat rate cents or percentage, depending on type.
   */
  public function addOrderDiscount($type, $amount) {
    if ($wrapper = $this->transaction->getOrderWrapper()) {
      CommercePosDiscountService::applyDiscount($wrapper, $type, $amount);
      $wrapper->save();
    }
  }

  /**
   * Act upon a line item being updated.
   *
   * When a line item is updated, it generally means that the order total has
   * changed, which is potentially a problem for order-wide discounts.
   *
   * We need to recalculate any order-wide discounts to ensure that they're
   * still valid.
   */
  public function lineItemUpdated() {
    CommercePosDiscountService::updateOrderDiscounts($this->transaction->getOrderWrapper());
  }

  /**
   * Act upon a line item being deleted.
   *
   * This will check to see if the only remaining line item in the order is
   * a POS discount and will remove it if needed.
   */
  public function afterDeleteLineItem() {
    if ($wrapper = $this->transaction->getOrderWrapper()) {
      if (count($wrapper->commerce_line_items) == 1) {
        foreach ($wrapper->commerce_line_items as $delta => $line_item_wrapper) {
          if ($line_item_wrapper->type->value() == 'commerce_pos_discount') {
            commerce_line_item_delete($line_item_wrapper->line_item_id);
            $wrapper->commerce_line_items->offsetUnset($delta);
          }
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

      // Remove any existing discount components on the line item.
      CommercePosDiscountService::removeDiscountComponents($wrapper->commerce_unit_price, CommercePosDiscountService::LINE_ITEM_DISCOUNT_NAME);
      $pre_discount_amount = $this->getPreDiscountAmount($wrapper);
      CommercePosDiscountService::applyDiscount($wrapper, $type, $amount);

      $wrapper->commerce_unit_price->amount->set($pre_discount_amount);
      $wrapper_value = $wrapper->value();
      commerce_line_item_rebase_unit_price($wrapper_value);

      $wrapper->save();

      $this->transaction->invokeEvent('lineItemUpdated');
    }
  }

  /**
   * Remove a line item discount from a pos order.
   */
  public function removeLineItemDiscount($discount_name, $line_item_id) {
    if ($discount_name == CommercePosDiscountService::LINE_ITEM_DISCOUNT_NAME) {
      $this->addLineItemDiscount('fixed', $line_item_id, 0);
    }
    else {
      $this->removeOrderCoupon($discount_name);
    }

  }

  /**
   * Retrieves the existing amount for a transaction order's discount amount.
   */
  public function getExistingOrderDiscountAmount() {
    if ($order_wrapper = $this->transaction->getOrderWrapper()) {
      foreach ($order_wrapper->commerce_line_items as $line_item_wrapper) {
        if ($line_item_wrapper->type->value() == 'commerce_pos_discount') {
          return $this->getLineItemDiscountData($line_item_wrapper, CommercePosDiscountService::ORDER_DISCOUNT_NAME);
        }
      }
    }

    return FALSE;
  }

  /**
   * Retrieves the existing amount for a discount on a line item, if one exists.
   */
  public function getExistingLineItemDiscountAmount($line_item_id) {
    if ($line_item = $this->transaction->doAction('getLineItem', $line_item_id)) {
      $line_item_wrapper = entity_metadata_wrapper('commerce_line_item', $line_item);
      return $this->getLineItemDiscountData($line_item_wrapper);
    }

    return FALSE;
  }

  /**
   * Loads the data for a specific line item and discount combo.
   *
   * @param EntityMetadataWrapper $line_item_wrapper
   *   The line item to check for the specified discount.
   *
   * @return array
   *   Data of the line item discount, will default to blank if can't be found.
   */
  protected function getLineItemDiscountData(EntityMetadataWrapper $line_item_wrapper) {
    $data = array();
    $order_wrapper = $this->transaction->getOrderWrapper();
    if ($components = CommercePosDiscountService::getCommerceDiscountComponents($line_item_wrapper->commerce_unit_price, $order_wrapper)) {
      foreach ($components as $discount_name => $component) {
        $pos_discount_type = isset($component['price']['data']['pos_discount_type']) ? $component['price']['data']['pos_discount_type'] : FALSE;
        $data[$discount_name]['type'] = (!empty($pos_discount_type)) ? $pos_discount_type : 'commerce_discount';
        $data[$discount_name]['currency_code'] = $component['price']['currency_code'];

        // Found our discount, return its amount.
        if (!empty($pos_discount_type) && $pos_discount_type == 'percent') {
          $data[$discount_name]['amount'] = $component['price']['data']['pos_discount_rate'] * 100;
        }
        else {
          $data[$discount_name]['amount'] = number_format(abs($component['price']['amount'] / 100), 2);
        }
      }
    }

    return $data;
  }

  /**
   * Get Pre-discount line item total amount.
   */
  public function getPreDiscountAmount($wrapper) {
    $pre_discount_amount = $wrapper->commerce_unit_price->amount->raw();
    $order_wrapper = $this->transaction->getOrderWrapper();
    $price_data = $wrapper->commerce_unit_price->data->value();
    foreach ($price_data['components'] as $component) {
      if (isset($component['price']['data']['discount_name']) && $component['included']) {
        $discount_name = $component['price']['data']['discount_name'];
        if (CommercePosDiscountService::discountGrantedByCoupon($order_wrapper, $discount_name)) {
          $pre_discount_amount -= $component['price']['amount'];
        }
      }
    }
    return $pre_discount_amount;
  }

  /**
   * Apply a coupon code.
   *
   * @param string $code
   *   The coupon code.
   */
  public function addOrderCoupon($code) {
    $order_wrapper = $this->transaction->getOrderWrapper();
    $order = $order_wrapper->value();
    $error = '';

    commerce_coupon_redeem_coupon_code($code, $order, $error);

    if (!empty($error)) {
      // If there was an error display it.
      drupal_set_message($error);
    }
    else {
      // Need to invoke the calculate sell price event for coupons to appear on
      // any elligible line items.
      foreach ($order_wrapper->commerce_line_items as $delta => $line_item_wrapper) {
        $line_item = $line_item_wrapper->value();
        // Remove discount line items, they will be added again later.
        if ($line_item->type == 'commerce_discount') {
          $order_wrapper->commerce_line_items->offsetUnset($delta);
          continue;
        }
        rules_invoke_all('commerce_product_calculate_sell_price', $line_item);
        CommercePosDiscountService::updateLineItemTotal($line_item_wrapper);
        $line_item_wrapper->save();
      }
      $this->transaction->invokeEvent('lineItemUpdated');
      // Remove all applicable discount components before recalculating them.
      foreach ($order_wrapper->commerce_discounts as $delta => $discount_wrapper) {
        $discount_name = $discount_wrapper->name->value();
        if (CommercePosDiscountService::discountGrantedByCoupon($order_wrapper, $discount_name)) {
          $order_wrapper->commerce_discounts->offsetUnset($delta);
          CommercePosDiscountService::removeDiscountComponents($order_wrapper->commerce_order_total, $discount_name);
        }
      }

      // Re-add all applicable discount price components and/or line items.
      rules_invoke_event('commerce_discount_order', $order_wrapper);
    }
  }

  /**
   * Given the discount name remove a coupon from the order.
   */
  public function removeOrderCoupon($discount_name) {
    $order_wrapper = $this->transaction->getOrderWrapper();
    $order = $order_wrapper->value();
    $coupon = CommercePosDiscountService::discountGrantedByCoupon($order_wrapper, $discount_name);
    if ($coupon) {
      commerce_coupon_remove_coupon_from_order($order, $coupon);
      $discounts = commerce_coupon_load_coupon_code_discounts($coupon->code);
      foreach ($discounts as $discount) {
        // Remove the discount from all line items.
        foreach ($order_wrapper->commerce_line_items as $line_item_wrapper) {
          CommercePosDiscountService::removeDiscountComponents($line_item_wrapper->commerce_unit_price, $discount->name);
          $line_item_wrapper->save();
        }
        foreach ($order_wrapper->commerce_discounts as $delta => $discount_wrapper) {
          if ($discount_wrapper->name->value() == $discount->name) {
            $order_wrapper->commerce_discounts->offsetUnset($delta);
          }
        }
        // Remove the discount from the order total.
        CommercePosDiscountService::removeDiscountComponents($order_wrapper->commerce_order_total, $discount->name);
      }
      $order_wrapper->save();
      $this->transaction->invokeEvent('lineItemUpdated');
    }
  }

}
