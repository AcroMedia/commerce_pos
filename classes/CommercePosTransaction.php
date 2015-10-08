<?php

/**
 * @file
 * PosTransaction class definition.
 */

class CommercePosTransaction {

  const TABLE_NAME = 'commerce_pos_transaction';

  public $transactionId = 0;
  public $uid = 0;
  public $orderId = 0;
  public $type = 0;
  public $locationId = 0;
  public $data = array();

  protected $bases = array();
  protected $order = FALSE;
  protected $orderWrapper = FALSE;

  /**
   * Constructor.
   *
   * @param null $transaction_id
   * @param null $type
   * @param null $uid
   */
  public function __construct($transaction_id = NULL, $type = NULL, $uid = NULL) {
    if ($transaction_id !== NULL && $type == NULL && $uid == NULL) {
      $this->transactionId = $transaction_id;
      $this->load();
    }
    elseif ($type !== NULL && $uid !== NULL) {
      $this->uid = $uid;
      $this->type = $type;
    }
    else {
      throw new Exception(t('Cannot initialize POS transaction: invalid arguments supplied.'));
    }

    $this->collectBases();
  }

  /**
   * // @TODO: this needs to be documented better.
   *
   * @param $action_name
   * @param ...
   *   any additional arguments will be passed to the method.
   *
   * @return mixed
   *   Whatever the result is of the invoked method.
   * @throws \Exception
   *
   * @TODO: should this become private and just get called via a __call()
   * magic method instead? Also, call_user_func_array is apparently quite
   * expensive, potentially look into a better way, or use an observer pattern?
   */
  public function invokeBaseMethod($action_name) {
    $result = FALSE;
    $called = FALSE;

    static $after_hooks = array();

    $build_after_hooks = !isset($after_hooks[$action_name]);
    $args = array_slice(func_get_args(), 1);

    // @TODO: this should probably be able to handle calling the same method on
    // multiple base classes. Or potentially hook into the result?
    foreach ($this->bases as $base_class) {
      if (!$called && is_callable(array($base_class, $action_name))) {
        $result = call_user_func_array(array($base_class, $action_name), $args);
        $called = TRUE;
      }

      // Look for any methods that should be called AFTER we call the main one.
      if ($build_after_hooks && is_callable(array($base_class, $action_name . 'After'))) {
        $after_hooks[$action_name][] = $base_class;
      }
    }

    if (isset($after_hooks[$action_name])) {
      // Add the result of the initial method call to our arguments so that it's
      // always the last argument passed to any after hooks.
      array_push($args, $result);

      foreach ($after_hooks[$action_name] as $base_class) {
        call_user_func_array(array($base_class, $action_name . 'After'), $args);
      }
    }

    if ($called === FALSE) {
      throw new Exception(t('The transaction base method @name does not exist.', array(
        '@name' => $action_name,
      )));
    }
    else {
      return $result;
    }
  }

  /**
   * Saves the transaction to the database.
   */
  public function save() {
    $transaction_array = array(
      'transaction_id' => $this->transactionId,
      'uid' => $this->uid,
      'order_id' => $this->orderId,
      'type' => $this->type,
      'data' => $this->data,
      'location_id' => $this->locationId,
    );

    if ($this->transactionId) {
      $primary_keys = 'transaction_id';
    }
    else {
      $primary_keys = array();
    }

    drupal_write_record(self::TABLE_NAME, $transaction_array, $primary_keys);
    $this->transactionId = $transaction_array['transaction_id'];
    unset($transaction_array);
  }

  /**
   * Retrieves the commerce order associated with this transaction.
   */
  public function getOrder() {
    if ($this->orderId) {
      return $this->order ? $this->order : $this->loadOrder();
    }
    else {
      return FALSE;
    }
  }

  /**
   * Retrieves the entity metadata wrapper for this transaction's order.
   *
   * The order wrapper is made available as a property on the object because
   * it's used so often by subclasses and other functionality, so there's no
   * point in creating a new wrapper all of the time.
   *
   * @return EntityDrupalWrapper|bool
   */
  public function getOrderWrapper() {
    if ($this->orderId) {
      if (!$this->orderWrapper) {
        $this->loadOrder();
      }

      return $this->orderWrapper;
    }
    else {
      return FALSE;
    }
  }

  /**
   * Loads the associated commerce order from the database.
   */
  public function loadOrder() {
    if ($this->orderId) {
      $this->order = commerce_order_load($this->orderId);

      if ($this->order) {
        $this->orderWrapper = entity_metadata_wrapper('commerce_order', $this->order);
      }

      return $this->order;
    }
    else {
      throw new Exception(t('Cannot load order for POS transaction, it does not have an order ID!'));
    }
  }

  /**
   * Adds the specified product to transaction order.
   */
  public function addProduct($product, $quantity = 1, $combine = TRUE) {
    // First attempt to load the transaction's order.
    // If no order existed, create one now.
    if (empty($this->order)) {
      $order = $this->createNewOrder();
    }
    else {
      $order = $this->order;
    }

    // If the specified product exists...
    // Create a new product line item for it.
    $line_item = commerce_product_line_item_new($product, $quantity, $order->order_id);

    rules_invoke_event('commerce_product_calculate_sell_price', $line_item);

    if (module_exists('commerce_pricing_attributes')) {
      // Hack to prevent the combine logic in addLineItem()
      // from incorrectly thinking that the newly-added line item is different than
      // previously-added line items.
      $line_item->commerce_pricing_attributes = serialize(array());
    }

    return $this->addLineItem($line_item, $combine);
  }

  /**
   * Creates a commerce order for this transaction.
   */
  function createNewOrder() {
    if (!empty($this->orderId)) {
      throw new Exception(t('Cannot create order for transaction @id, an order with @order_id already exists!', array(
        '@id' => $this->transactionId,
        '@order_id' => $this->orderId,
      )));
    }
    else {
      if (empty($this->transactionId)) {
        $this->save();
      }

      $order = commerce_order_new($this->uid, 'commerce_pos_in_progress');
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

      $this->orderId = $order->order_id;
      $this->order = $order;

      $this->save();

      return $this->order;
    }
  }

  /**
   * Updates the quantity of a line item in the transactions' order.
   */
  function updateLineItemQuantity($line_item_id, $qty, $method = 'replace') {
    $this->getOrder();
    if (!empty($this->order)) {
      $line_item = commerce_line_item_load($line_item_id);
      $existing_qty = $line_item->quantity;

      if ($method == 'update') {
        $new_qty = $existing_qty + $qty;
      }
      else {
        $new_qty = $qty;
      }

      // Make sure the line item actually belongs to the order.
      if ($new_qty > 0 && ($line_item->order_id == $this->order->order_id) && ((int) $existing_qty != $new_qty)) {
        $line_item->quantity = $new_qty;
        commerce_line_item_save($line_item);
      }
      elseif ($new_qty == 0) {
        $this->invokeBaseMethod('deleteLineItem', $line_item_id);
      }
    }
    else {
      throw new Exception(t('Cannot update line item @id quantity, the transaction does not have an order created.', array(
        '@id' => $line_item_id,
      )));
    }
  }

  /**
   * Sets the price of a line item in the transaction's order to a specific price.
   *
   * @param int $line_item_id
   *   The ID of the line item in the transaction order to change the price of.
   * @param int $price
   *   The new price, in dollars. This function will convert the price to cents.
   */
  public function setLineItemPrice($line_item_id, $price) {
    foreach ($this->getLineItems() as $order_line_item) {
      if ($order_line_item['line_item_id'] == $line_item_id) {
        $line_item = commerce_line_item_load($line_item_id);
        $line_item_wrapper = entity_metadata_wrapper('commerce_line_item', $line_item);
        $unit_price = commerce_price_wrapper_value($line_item_wrapper, 'commerce_unit_price', TRUE);
        $currency_code = $unit_price['currency_code'];

        $unit_price['data'] = commerce_price_component_delete($unit_price, 'base_price');
        $unit_price['data'] = commerce_price_component_add($unit_price, 'base_price', array(
          'amount' => $price * 100,
          'currency_code' => $currency_code,
          'data' => array(),
        ), FALSE);

        $new_total = commerce_price_component_total($unit_price);
        $unit_price['amount'] = $new_total['amount'];
        $line_item_wrapper->commerce_unit_price->set($unit_price);

        $line_item_wrapper->save();
        break;
      }
    }
  }

  /**
   * Retrieves the line items from this transaction's order, if it has any.
   */
  public function getLineItems() {
    $line_items = array();

    if (!empty($this->order)) {
      $line_items = field_get_items('commerce_order', $this->order, 'commerce_line_items');
    }

    return $line_items;
  }

  /**
   * Switches the transaction order's status back from parked to being created.
   */
  public function unpark() {
    $this->getOrder();
    if (!empty($this->order)) {
      $this->order->status = 'commerce_pos_in_progress';
      commerce_order_save($this->order);
    }
  }

  /**
   * Voids a transaction.
   */
  public function void() {
    $this->getOrder();
    if (!empty($this->order)) {
      $this->order->status = 'commerce_pos_voided';
      commerce_order_save($this->order);
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
   * @return
   *   The new or updated line item object or FALSE on failure.
   */
  protected function addLineItem($line_item, $combine) {
    // Do not add the line item if it doesn't have a unit price.
    $line_item_wrapper = entity_metadata_wrapper('commerce_line_item', $line_item);

    if (is_null($line_item_wrapper->commerce_unit_price->value())) {
      return FALSE;
    }

    // First attempt to load the customer's shopping cart order.
    // If no order existed, create one now.
    if (empty($this->order)) {
      throw new Exception(t('Cannot add line item to transaction, it does not have an order created.'));
    }
    else {
      $order = $this->order;
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
            $matching_line_item_wrapper->{$property}->raw() != $line_item_wrapper->{$property}->raw()) {
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
    commerce_order_save($order);

    // Invoke the product add event with the newly saved or updated line item.
    // @TODO: should we invoke this?
    //rules_invoke_all('commerce_cart_product_add', $order, $product, $quantity, $line_item);

    // Return the line item.
    return $line_item;
  }

  /**
   * Loads the transaction from the database.
   */
  protected function load() {
    if ($this->transactionId) {
      $result = db_select(self::TABLE_NAME, 't')
        ->fields('t')
        ->condition('transaction_id', $this->transactionId)
        ->execute()
        ->fetchAssoc();

      if ($result) {
        $this->uid = $result['uid'];
        $this->orderId = $result['order_id'];
        $this->type = $result['type'];
        $this->locationId = $result['location_id'];

        if (empty($result['data'])) {
          $this->data = array();
        }
        else {
          $this->data = unserialize($result['data']);
        }

        return $this;
      }
      else {
        $this->transactionId = 0;
        return FALSE;
      }
    }
    else {
      throw new Exception(t('Cannot load POS transaction: it does not have a transaction ID!'));
    }
  }

  /**
   * Checks for any modules defining additional base classes to be added to this
   * transaction.
   */
  private function collectBases() {
    foreach (module_invoke_all('commerce_pos_transaction_base_info') as $base_info) {
      // Only add the base class if it belongs to this type, or if it didn't
      // specify any types that it belongs to.
      if (!isset($base_info['types']) || in_array($this->type, $base_info['types'])) {
        $this->bases[] = new $base_info['class']($this);
      }
    }
  }
}
