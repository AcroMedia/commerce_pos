<?php

/**
 * @file
 * CommercePosDiscountService.php
 */

class CommercePosDiscountService {

  // Used to keep track of whether or not a discount has already been applied
  // to a line item or order.
  const LINE_ITEM_DISCOUNT_NAME = 'pos_line_item_discount';
  const ORDER_DISCOUNT_NAME = 'pos_order_discount';

  /**
   * Retrieves the price component relating to POS discount from a price field.
   *
   * @param $price_wrapper
   *   A metadata wrapper around a commerce price field.
   *
   * @return array|bool
   *   The price component, or FALSE if none was found.
   */
  static function getPosDiscountComponent($price_wrapper, $discount_name) {
    $data = (array) $price_wrapper->data->value() + array('components' => array());

    // Look for our discount in each of the price components.
    foreach ($data['components'] as $key => $component) {
      if (!empty($component['price']['data']['discount_name'])) {
        if ($component['price']['data']['discount_name'] == $discount_name) {
          return $component;
        }
      }
    }

    return FALSE;
  }

  /**
   * Apply a specific type of discount.
   *
   * This simply services as a centralized function to control which discount
   * method(s) to call, rather than each individual piece of coding having to
   * determine where to call applyPercentDiscount or applyFixedDiscount.
   */
  static function applyDiscount($wrapper, $type, $rate) {
    switch ($type) {
      case 'percent':
        CommercePosDiscountService::applyPercentDiscount($wrapper, $rate);
        break;

      case 'fixed':
        CommercePosDiscountService::applyFixedDiscount($wrapper, $rate);
        break;
    }
  }

  /**
   * A modified version of commerce_discount_percentage().
   */
  static function applyPercentDiscount($wrapper, $rate) {
    // Get the line item types to apply the discount to.
    $line_item_types = variable_get('commerce_discount_line_item_types', array('product' => 'product'));

    if ($rate > 1) {
      $rate = $rate / 100;
    }

    $component_data = array(
      'pos_discount_type' => 'percent',
      'pos_discount_rate' => $rate,
    );

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
          if (!self::setExistingLineItemPrice($wrapper, $discount_name, $discount_amount, $component_data)) {
            self::addLineItem($wrapper, $discount_name, $discount_amount, $component_data);
          }
        }
        else {
          // Discount amount is 0, make sure we remove any existing POS
          // discounts on the order.
          self::removeOrderDiscountLineItems();
        }

        break;

      case 'commerce_line_item':
        $discount_name = self::LINE_ITEM_DISCOUNT_NAME;

        // Check if the line item is configured in the settings to apply the
        // discount.
        if (empty($line_item_types[$wrapper->getBundle()])) {
          return;
        }

        $unit_price = commerce_price_wrapper_value($wrapper, 'commerce_unit_price', TRUE);
        $calculated_discount = $unit_price['amount'] * $rate * -1;

        if ($calculated_discount) {
          $discount_amount = array(
            'amount' => $calculated_discount,
            'currency_code' => $unit_price['currency_code'],
          );

          self::addPriceComponent($wrapper, $discount_name, $discount_amount, $component_data);
        }
        break;
    }
  }

  /**
   * A modified version of commerce_discount_fixed_amount().
   */
  static function applyFixedDiscount(EntityMetadataWrapper $wrapper, $discount_amount) {
    $discount_price['amount'] = -$discount_amount;

    $component_data = array(
      'pos_discount_type' => 'fixed',
    );

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
          if (!self::setExistingLineItemPrice($wrapper, $discount_name, $discount_price, $component_data)) {
            self::addLineItem($wrapper, $discount_name, $discount_price, $component_data);
          }
        }
        else {
          // Discount amount is 0, make sure we remove any existing POS
          // discounts on the order.
          self::removeOrderDiscountLineItems($wrapper);
        }

        break;

      case 'commerce_line_item':

        $discount_name = self::LINE_ITEM_DISCOUNT_NAME;

        if ($discount_amount) {

          // Do not allow negative line item totals.
          $line_item_amount = $wrapper->commerce_unit_price->amount->value();
          if (-$discount_price['amount'] > $line_item_amount) {
            $discount_price['amount'] = -$line_item_amount;
          }

          $discount_price['currency_code'] = $wrapper->commerce_unit_price->currency_code->value();

          self::addPriceComponent($wrapper, $discount_name, $discount_price, $component_data);
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
   * @param array $component_data
   *   Any price data to merge into the component.
   *
   * @return bool
   *   TRUE if an existing line item was successfully modified, FALSE otherwise.
   */
  static function setExistingLineItemPrice(EntityDrupalWrapper $order_wrapper, $discount_name, $discount_price, $component_data = array()) {
    $modified_existing = FALSE;
    foreach ($order_wrapper->commerce_line_items as $line_item_wrapper) {
      if ($line_item_wrapper->getBundle() == 'commerce_pos_discount') {
        // Add the discount component price if the line item was originally
        // added by discount module.
        $line_item = $line_item_wrapper->value();
        if (isset($line_item->data['discount_name']) && $line_item->data['discount_name'] == $discount_name) {
          self::setPriceComponent($line_item_wrapper, $discount_name, $discount_price, $component_data);
          $modified_existing = TRUE;
          $line_item_wrapper->save();
        }
      }
    }

    return $modified_existing;
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
   * @param array $component_data
   *   Any price data to merge into the component.
   */
  static function setPriceComponent(EntityDrupalWrapper $line_item_wrapper, $discount_name, $discount_amount, $component_data = array()) {
    $unit_price = commerce_price_wrapper_value($line_item_wrapper, 'commerce_unit_price', TRUE);
    // Currencies don't match, abort.
    if ($discount_amount['currency_code'] != $unit_price['currency_code']) {
      return;
    }

    $discount_amount['data'] = array(
      'discount_name' => $discount_name,
      'pos_discount_component_title' => self::getDiscountComponentTitle($discount_name),
    );

    $discount_amount['data'] += $component_data;

    // Set the new unit price.
    $line_item_wrapper->commerce_unit_price->amount = $discount_amount['amount'];
    $line_item_wrapper->commerce_unit_price->data = commerce_price_component_delete($line_item_wrapper->commerce_unit_price->value(), 'discount|pos_order_discount');

    // Add the discount amount as a price component.
    $price = $line_item_wrapper->commerce_unit_price->value();
    $type = check_plain('discount|' . $discount_name);
    $line_item_wrapper->commerce_unit_price->data = commerce_price_component_add($price, $type, $discount_amount, TRUE, TRUE);

    self::calculateTaxes($line_item_wrapper);

    // Update the line item total.
    self::updateLineItemTotal($line_item_wrapper);
  }

  /**
   * Retrieves a display name for a specific discount type.
   */
  static function getDiscountComponentTitle($discount_name) {
    switch ($discount_name) {
      case self::LINE_ITEM_DISCOUNT_NAME:
        return t('Product discount');

      case self::ORDER_DISCOUNT_NAME:
        return t('Order discount');

      default:
        return FALSE;
    }
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
   * @param array $data
   *   Any additional data to be added to the price component.
   */
  static function addLineItem(EntityDrupalWrapper $order_wrapper, $discount_name, $discount_amount, $data) {
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
    self::addPriceComponent($discount_line_item_wrapper, $discount_name, $discount_amount, $data);

    self::calculateTaxes($discount_line_item_wrapper);

    // Save the line item and add it to the order.
    $discount_line_item_wrapper->save();
    $order_wrapper->commerce_line_items[] = $discount_line_item_wrapper;
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
   * @param array $data
   *   Any additional data to be merged into the new price component's data
   *   array.
   */
  static function addPriceComponent(EntityDrupalWrapper $line_item_wrapper, $discount_name, $discount_amount, $data) {
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
        'pos_discount_component_title' => self::getDiscountComponentTitle($discount_name),
      ),
    );

    $difference['data'] += $data;

    // Set the new unit price.
    $line_item_wrapper->commerce_unit_price->amount = $updated_amount;

    // Add the discount amount as a price component.
    $price = $line_item_wrapper->commerce_unit_price->value();
    $type = check_plain('discount|' . $discount_name);
    $line_item_wrapper->commerce_unit_price->data = commerce_price_component_add($price, $type, $difference, TRUE, TRUE);

    // Update the line item total.
    self::updateLineItemTotal($line_item_wrapper);
  }

  /**
   * Update commerce_total without saving line item.
   *
   * To have the order total refreshed without saving the line item.
   * Taken from CommerceLineItemEntityController::save().
   */
  static function updateLineItemTotal($line_item_wrapper) {
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
  static function removeOrderDiscountLineItems($order_wrapper) {
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

  /**
   * Updates any order discounts that need to have their amounts adjusted.
   *
   * @param $order_wrapper
   *   A metadata wrapper of the commerce_order to update the discounts of.
   */
  public static function updateOrderDiscounts($order_wrapper) {
    foreach ($order_wrapper->commerce_line_items as $line_item_wrapper) {
      if ($line_item_wrapper->getBundle() == 'commerce_pos_discount') {
        $price_components = commerce_price_wrapper_value($line_item_wrapper, 'commerce_unit_price');

        foreach ($price_components as $price_component) {
          if (isset($price_component['price']['data']['pos_discount_type'])) {
            self::applyDiscount($order_wrapper, $price_component['price']['data']['pos_discount_type'], $price_component['price']['data']['pos_discount_rate']);
          }
        }
      }
    }
  }

  /**
   * Remove discount components from a given price and recalculate the total.
   *
   * @param object $price_wrapper
   *   Wrapped commerce price.
   */
  static function removeDiscountComponents($price_wrapper, $discount_name_to_remove) {
    $discount_amounts = 0;

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
        $discount_amounts += $component['price']['amount'];

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
    $new_total = $price_wrapper->amount->raw() - $discount_amounts;
    $price_wrapper->amount->set($new_total);
  }

  /**
   * Calculate the taxes on a given line item.
   *
   * @param $line_item_wrapper
   *   A metadata wrapper representing the line item.
   */
  protected static function calculateTaxes($line_item_wrapper) {
    if (module_exists('commerce_tax')) {
      module_load_include('inc', 'commerce_tax', 'commerce_tax.rules');

      // First remove all existing tax components from the line item if any
      // exist.
      commerce_tax_remove_taxes($line_item_wrapper, FALSE, array_keys(commerce_tax_rates()));

      foreach (commerce_tax_types() as $name => $type) {
        commerce_tax_calculate_by_type($line_item_wrapper->value(), $name);
      }
    }
  }
}
