<?php

/**
 * @file
 * CommercePosGratuityBase.php class definition.
 *
 * CommercePosGratuityBase is responsible for applying gratuities to a POS
 * transaction's order.
 */

/**
 * Base class to use for any CommercePosGratuity classes.
 */
class CommercePosGratuityBase extends CommercePosTransactionBase implements CommercePosTransactionBaseInterface {

  /**
   * Base gratuity action.
   */
  public function actions() {
    $actions = parent::actions();

    $actions += array(
      'addOrderGratuity',
      'getExistingOrderGratuityAmount',
    );

    return $actions;
  }

  /**
   * Base Gratuity specific subscriptions.
   */
  public function subscriptions() {
    $subscriptions = parent::subscriptions();
    $subscriptions['deleteLineItemAfter'][] = 'afterDeleteLineItem';
    $subscriptions['lineItemUpdated'][] = 'lineItemUpdated';
    return $subscriptions;
  }

  /**
   * Adds a gratuity to the transaction's order.
   *
   * @param string $type
   *   The type of gratuity to add, currently flat or percent.
   * @param float|int $amount
   *   The amount to add, either in flat rate cents or percentage, depending on type.
   */
  public function addOrderGratuity($type, $amount) {
    if ($wrapper = $this->transaction->getOrderWrapper()) {
      CommercePosGratuityService::applyGratuity($wrapper, $type, $amount);
      $wrapper->save();
    }
  }

  /**
   * Act upon a line item being updated.
   *
   * When a line item is updated, it generally means that the order total has
   * changed, which is potentially a problem for order-wide gratuities.
   *
   * We need to recalculate any order-wide gratuities to ensure that they're
   * still valid.
   */
  public function lineItemUpdated() {
    CommercePosGratuityService::updateOrderGratuities($this->transaction->getOrderWrapper());
  }

  /**
   * Act upon a line item being deleted.
   *
   * This will check to see if the only remaining line item in the order is
   * a POS gratuity and will remove it if needed.
   */
  public function afterDeleteLineItem() {
    if ($wrapper = $this->transaction->getOrderWrapper()) {
      if (count($wrapper->commerce_line_items) == 1) {
        foreach ($wrapper->commerce_line_items as $delta => $line_item_wrapper) {
          if ($line_item_wrapper->type->value() == 'commerce_pos_gratuity') {
            commerce_line_item_delete($line_item_wrapper->line_item_id);
            $wrapper->commerce_line_items->offsetUnset($delta);
          }
        }
      }
    }
  }

  /**
   * Retrieves the existing amount for a transaction order's gratuity amount.
   */
  public function getExistingOrderGratuityAmount() {
    if ($order_wrapper = $this->transaction->getOrderWrapper()) {
      foreach ($order_wrapper->commerce_line_items as $line_item_wrapper) {
        if ($line_item_wrapper->type->value() == 'commerce_pos_gratuity') {
          return $this->getLineItemGratuityData($line_item_wrapper, CommercePosGratuityService::ORDER_GRATUITY_NAME);
        }
      }
    }

    return FALSE;
  }

  /**
   * Loads the data for a specific line item and gratuity combo.
   *
   * @param EntityMetadataWrapper $line_item_wrapper
   *   The line item to check for the specified gratuity.
   * @param string $gratuity_name
   *   The gratuity to check against the line item.
   *
   * @return array
   *   Data of the line item gratuity, will default to blank if can't be found.
   */
  protected function getLineItemGratuityData(EntityMetadataWrapper $line_item_wrapper, $gratuity_name) {
    $data = array(
      'type' => '',
      'amount' => 0,
    );

    if ($component = CommercePosGratuityService::getPosGratuityComponent($line_item_wrapper->commerce_unit_price, $gratuity_name)) {
      $data['type'] = $component['price']['data']['pos_gratuity_type'];
      $data['currency_code'] = $component['price']['currency_code'];

      // Found our gratuity, return its amount.
      if ($component['price']['data']['pos_gratuity_type'] == 'percent') {
        $data['amount'] = $component['price']['data']['pos_gratuity_rate'] * 100;
      }
      else {
        $data['amount'] = number_format(abs($component['price']['amount'] / 100), 2);
      }
    }

    return $data;
  }

}
