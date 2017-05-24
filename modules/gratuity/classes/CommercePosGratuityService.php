<?php

/**
 * @file
 * CommercePosGratuityService.php
 */

/**
 * Handles applying and managing gratuities within the POS, similar to stock commerce functionality.
 */
class CommercePosGratuityService {

  // Used to keep track of whether or not a gratuity has already been applied
  // to an order.
  const ORDER_GRATUITY_NAME = 'pos_order_gratuity';

  /**
   * Retrieves the price component relating to POS gratuity from a price field.
   *
   * @param EntityMetadataWrapper $price_wrapper
   *   A metadata wrapper around a commerce price field.
   *
   * @return array|bool
   *   The price component, or FALSE if none was found.
   */
  static public function getPosGratuityComponent(EntityMetadataWrapper $price_wrapper, $gratuity_name) {
    $price_entity = $price_wrapper->value();
    if (!isset($price_entity['data'])) {
      $price_entity['data'] = array();
    }
    $data = (array) $price_entity['data'] + array('components' => array());

    // Look for our gratuity in each of the price components.
    foreach ($data['components'] as $key => $component) {
      if (!empty($component['price']['data']['gratuity_name'])) {
        if ($component['price']['data']['gratuity_name'] == $gratuity_name) {
          return $component;
        }
      }
    }

    return FALSE;
  }

  /**
   * Apply a specific type of gratuity.
   *
   * This simply services as a centralized function to control which gratuity
   * method(s) to call, rather than each individual piece of coding having to
   * determine where to call applyPercentGratuity or applyFixedGratuity.
   *
   * @param EntityMetadataWrapper $order_wrapper
   *   The wrapper around an order.
   * @param string $type
   *   Either percent or fixed.
   * @param int|float $rate
   *   The rate to be changed, either a percentage or fixed value in cents.
   */
  static public function applyGratuity(EntityMetadataWrapper $order_wrapper, $type, $rate) {
    switch ($type) {
      case 'percent':
        CommercePosGratuityService::applyPercentGratuity($order_wrapper, $rate);
        break;

      case 'fixed':
        CommercePosGratuityService::applyFixedGratuity($order_wrapper, $rate);
        break;
    }
  }

  /**
   * A modified version of commerce_discount_percentage().
   *
   * @param EntityMetadataWrapper $order_wrapper
   *   Wrapper of an order.
   * @param float $rate
   *   The gratuity percentage in full format (10 not .1).
   */
  static public function applyPercentGratuity(EntityMetadataWrapper $order_wrapper, $rate) {
    // Get the line item types to apply the gratuity to.
    $line_item_types = variable_get('commerce_gratuity_line_item_types', array('product' => 'product'));

    if ($rate > 1) {
      $rate = $rate / 100;
    }

    $component_data = array(
      'pos_gratuity_type' => 'percent',
      'pos_gratuity_rate' => $rate,
    );

    $gratuity_name = self::ORDER_GRATUITY_NAME;

    $calculated_gratuity = 0;
    // Loop the line items of the order and calculate the total gratuity.
    foreach ($order_wrapper->commerce_line_items as $line_item_wrapper) {
      if (!empty($line_item_types[$line_item_wrapper->type->value()])) {
        $line_item_total = commerce_price_wrapper_value($line_item_wrapper, 'commerce_total', TRUE);
        $calculated_gratuity += $line_item_total['amount'] * $rate;
      }
    }

    if ($calculated_gratuity) {
      $gratuity_amount = array(
        'amount' => $calculated_gratuity,
        'currency_code' => $order_wrapper->commerce_order_total->currency_code->value(),
      );

      // Modify the existing gratuity line item or add a new line item
      // if that fails.
      if (!self::setExistingLineItemPrice($order_wrapper, $gratuity_name, $gratuity_amount, $component_data)) {
        self::addLineItem($order_wrapper, $gratuity_name, $gratuity_amount, $component_data);
      }
    }
    else {
      self::removeOrderGratuityLineItems($order_wrapper);
    }
  }

  /**
   * A modified version of commerce_discount_fixed_amount().
   *
   * @param EntityMetadataWrapper $order_wrapper
   *   The wrapper of the order.
   * @param int $gratuity_amount
   *   Amount in cents to be removed.
   */
  static public function applyFixedGratuity(EntityMetadataWrapper $order_wrapper, $gratuity_amount) {
    $gratuity_price['amount'] = $gratuity_amount;

    $component_data = array(
      'pos_gratuity_type' => 'fixed',
      'pos_gratuity_rate' => $gratuity_amount,
    );

    $gratuity_name = self::ORDER_GRATUITY_NAME;

    if ($gratuity_amount) {
      $gratuity_price['currency_code'] = $order_wrapper->commerce_order_total->currency_code->value();

      // Modify the existing gratuity line item or add a new one if that fails.
      if (!self::setExistingLineItemPrice($order_wrapper, $gratuity_name, $gratuity_price, $component_data)) {
        self::addLineItem($order_wrapper, $gratuity_name, $gratuity_price, $component_data);
      }
    }
    else {
      self::removeOrderGratuityLineItems($order_wrapper);
    }
  }

  /**
   * Updates the unit price of an existing gratuity line item.
   *
   * Non-gratuity line items are ignored.
   *
   * @param EntityDrupalWrapper $order_wrapper
   *   The wrapped order entity.
   * @param string $gratuity_name
   *   The name of the gratuity being applied.
   * @param array $gratuity_price
   *   The gratuity amount price array (amount, currency_code).
   * @param array $component_data
   *   Any price data to merge into the component.
   *
   * @return bool
   *   TRUE if an existing line item was successfully modified, FALSE otherwise.
   */
  static public function setExistingLineItemPrice(EntityDrupalWrapper $order_wrapper, $gratuity_name, array $gratuity_price, array $component_data = array()) {
    $modified_existing = FALSE;
    foreach ($order_wrapper->commerce_line_items as $line_item_wrapper) {
      if ($line_item_wrapper->getBundle() == 'commerce_pos_gratuity') {
        // Add the gratuity component price if the line item was originally
        // added by gratuity module.
        $line_item = $line_item_wrapper->value();
        if (isset($line_item->data['gratuity_name']) && $line_item->data['gratuity_name'] == $gratuity_name) {
          self::setPriceComponent($line_item_wrapper, $gratuity_name, $gratuity_price, $component_data);
          $modified_existing = TRUE;
          $line_item_wrapper->save();
        }
      }
    }

    return $modified_existing;
  }

  /**
   * Sets a gratuity price component to the provided line item.
   *
   * @param EntityDrupalWrapper $line_item_wrapper
   *   The wrapped line item entity.
   * @param string $gratuity_name
   *   The name of the gratuity being applied.
   * @param array $gratuity_amount
   *   The gratuity amount price array (amount, currency_code).
   * @param array $component_data
   *   Any price data to merge into the component.
   */
  static public function setPriceComponent(EntityDrupalWrapper $line_item_wrapper, $gratuity_name, array $gratuity_amount, array $component_data = array()) {
    $unit_price = commerce_price_wrapper_value($line_item_wrapper, 'commerce_unit_price', TRUE);
    // Currencies don't match, abort.
    if ($gratuity_amount['currency_code'] != $unit_price['currency_code']) {
      return;
    }

    $gratuity_amount['data'] = array(
      'gratuity_name' => $gratuity_name,
      'pos_gratuity_component_title' => self::getGratuityComponentTitle($gratuity_name),
    );

    $gratuity_amount['data'] += $component_data;

    // Set the new unit price.
    $line_item_wrapper->commerce_unit_price->amount = $gratuity_amount['amount'];
    $line_item_wrapper->commerce_unit_price->data = commerce_price_component_delete($line_item_wrapper->commerce_unit_price->value(), 'gratuity|pos_order_gratuity');

    // Add the gratuity amount as a price component.
    $price = $line_item_wrapper->commerce_unit_price->value();
    $type = check_plain('gratuity|' . $gratuity_name);
    $line_item_wrapper->commerce_unit_price->data = commerce_price_component_add($price, $type, $gratuity_amount, TRUE, TRUE);

    // Update the line item total.
    self::updateLineItemTotal($line_item_wrapper);
  }

  /**
   * Retrieves a display name for a specific gratuity type.
   */
  static public function getGratuityComponentTitle($gratuity_name) {
    switch ($gratuity_name) {
      case self::ORDER_GRATUITY_NAME:
        return t('Order Gratuity');

      default:
        return FALSE;
    }
  }

  /**
   * Creates a gratuity line item on the provided order.
   *
   * @param EntityDrupalWrapper $order_wrapper
   *   The wrapped order entity.
   * @param string $gratuity_name
   *   The name of the gratuity being applied.
   * @param array $gratuity_amount
   *   The gratuity amount price array (amount, currency_code).
   * @param array $data
   *   Any additional data to be added to the price component.
   */
  static public function addLineItem(EntityDrupalWrapper $order_wrapper, $gratuity_name, array $gratuity_amount, array $data) {
    // Create a new line item.
    $values = array(
      'type' => 'commerce_pos_gratuity',
      'order_id' => $order_wrapper->order_id->value(),
      'quantity' => 1,
      // Flag the line item.
      'data' => array('gratuity_name' => $gratuity_name),
    );
    $gratuity_line_item = entity_create('commerce_line_item', $values);
    $gratuity_line_item_wrapper = entity_metadata_wrapper('commerce_line_item', $gratuity_line_item);

    // Initialize the line item unit price.
    $gratuity_line_item_wrapper->commerce_unit_price->amount = 0;
    $gratuity_line_item_wrapper->commerce_unit_price->currency_code = $gratuity_amount['currency_code'];

    // Reset the data array of the line item total field to only include a
    // base price component, set the currency code from the order.
    $base_price = array(
      'amount' => 0,
      'currency_code' => $gratuity_amount['currency_code'],
      'data' => array(),
    );

    $gratuity_line_item_wrapper->commerce_unit_price->data = commerce_price_component_add($base_price, 'base_price', $base_price, TRUE);

    // Add the gratuity price component.
    self::addPriceComponent($gratuity_line_item_wrapper, $gratuity_name, $gratuity_amount, $data);

    // Save the line item and add it to the order.
    $gratuity_line_item_wrapper->save();
    $order_wrapper->commerce_line_items[] = $gratuity_line_item_wrapper;
  }

  /**
   * Adds a gratuity price component to the provided line item.
   *
   * @param EntityDrupalWrapper $line_item_wrapper
   *   The wrapped line item entity.
   * @param string $gratuity_name
   *   The name of the gratuity being applied.
   * @param array $gratuity_amount
   *   The gratuity amount price array (amount, currency_code).
   * @param array $data
   *   Any additional data to be merged into the new price component's data
   *   array.
   */
  static public function addPriceComponent(EntityDrupalWrapper $line_item_wrapper, $gratuity_name, array $gratuity_amount, array $data) {
    $unit_price = commerce_price_wrapper_value($line_item_wrapper, 'commerce_unit_price', TRUE);
    $current_amount = $unit_price['amount'];
    // Currencies don't match, abort.
    if ($gratuity_amount['currency_code'] != $unit_price['currency_code']) {
      return;
    }

    // Calculate the updated amount and create a price array representing the
    // difference between it and the current amount.
    $updated_amount = commerce_round(COMMERCE_ROUND_HALF_UP, $current_amount + $gratuity_amount['amount']);

    $difference = array(
      'amount' => commerce_round(COMMERCE_ROUND_HALF_UP, $gratuity_amount['amount']),
      'currency_code' => $gratuity_amount['currency_code'],
      'data' => array(
        'gratuity_name' => $gratuity_name,
        'pos_gratuity_component_title' => self::getGratuityComponentTitle($gratuity_name),
      ),
    );

    $difference['data'] += $data;

    // Set the new unit price.
    $line_item_wrapper->commerce_unit_price->amount = $updated_amount;

    // Add the gratuity amount as a price component.
    $price = $line_item_wrapper->commerce_unit_price->value();
    $type = check_plain('gratuity|' . $gratuity_name);
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
  static public function updateLineItemTotal($line_item_wrapper) {
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
   * Removes all POS gratuity line items from an order.
   */
  static public function removeOrderGratuityLineItems($order_wrapper) {
    $line_items_to_delete = array();

    foreach ($order_wrapper->commerce_line_items as $delta => $line_item_wrapper) {
      if ($line_item_wrapper->type->value() == 'commerce_pos_gratuity') {
        $order_wrapper->commerce_line_items->offsetUnset($delta);
        $line_items_to_delete[] = $line_item_wrapper->line_item_id;
      }
    }

    if ($line_items_to_delete) {
      commerce_line_item_delete_multiple($line_items_to_delete);
    }
  }

  /**
   * Updates any order gratuities that need to have their amounts adjusted.
   *
   * @param object $order_wrapper
   *   A metadata wrapper of the commerce_order to update the gratuities of.
   */
  public static function updateOrderGratuities($order_wrapper) {
    foreach ($order_wrapper->commerce_line_items as $line_item_wrapper) {
      if ($line_item_wrapper->getBundle() == 'commerce_pos_gratuity') {
        $price_wrapper = commerce_price_wrapper_value($line_item_wrapper, 'commerce_unit_price');

        foreach ($price_wrapper['data']['components'] as $price_component) {
          if (isset($price_component['price']['data']['pos_gratuity_type'])) {
            self::applyGratuity($order_wrapper, $price_component['price']['data']['pos_gratuity_type'], $price_component['price']['data']['pos_gratuity_rate']);
          }
        }
      }
    }
  }

}
