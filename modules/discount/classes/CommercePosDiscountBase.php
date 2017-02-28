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
 *
 */
class CommercePosDiscountBase extends CommercePosTransactionBase implements CommercePosTransactionBaseInterface {

  /**
   *
   */
  public function actions() {
    $actions = parent::actions();

    $actions += array(
      'addOrderDiscount',
      'addLineItemDiscount',
      'getExistingLineItemDiscountAmount',
      'getExistingOrderDiscountAmount',
    );

    return $actions;
  }

  /**
   *
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
   * @param $type
   * @param $amount
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
      $pre_discount_amount = $wrapper->commerce_unit_price->amount->raw();

      CommercePosDiscountService::applyDiscount($wrapper, $type, $amount);

      $wrapper->commerce_unit_price->amount->set($pre_discount_amount);
      $wrapper_value = $wrapper->value();
      commerce_line_item_rebase_unit_price($wrapper_value);

      $wrapper->save();

      $this->transaction->invokeEvent('lineItemUpdated');
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
      return $this->getLineItemDiscountData($line_item_wrapper, CommercePosDiscountService::LINE_ITEM_DISCOUNT_NAME);
    }

    return FALSE;
  }

  /**
   *
   */
  protected function getLineItemDiscountData($line_item_wrapper, $discount_name) {
    $data = array(
      'type' => '',
      'amount' => 0,
    );

    if ($component = CommercePosDiscountService::getPosDiscountComponent($line_item_wrapper->commerce_unit_price, $discount_name)) {
      $data['type'] = $component['price']['data']['pos_discount_type'];
      $data['currency_code'] = $component['price']['currency_code'];

      // Found our discount, return its amount.
      if ($component['price']['data']['pos_discount_type'] == 'percent') {
        $data['amount'] = $component['price']['data']['pos_discount_rate'] * 100;
      }
      else {
        $data['amount'] = number_format(abs($component['price']['amount'] / 100), 2);
      }
    }

    return $data;
  }

}
