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
  protected $actions = array();

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
   *   Whatever the result of the invoked method is.
   * @throws \Exception
   */
  public function doAction($action_name) {
    if (isset($this->actions[$action_name]['class'])) {
      $args = array_slice(func_get_args(), 1);
      $base_class = $this->bases[$this->actions[$action_name]['class']];

      $this->notifySubscribers($action_name, 'before', $args);

      $result = call_user_func_array(array($base_class, $action_name), $args);

      // Add the result of the initial method call to our arguments so that it's
      // always the last argument passed to any 'after' subscriptions.
      array_push($args, $result);

      $this->notifySubscribers($action_name, 'after', $args);
    }
    else {
      throw new Exception(t('The transaction base method @name does not exist.', array(
        '@name' => $action_name,
      )));
    }

    return $result;
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
   * Sets the transaction's order.
   */
  public function setOrder($order) {
    $this->order = $order;
    $this->orderId = $order->order_id;
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
      if (!$this->order) {
        $this->loadOrder();
      }

      $this->checkOrderWrapper();

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
      return $this->order;
    }
    else {
      return FALSE;
    }
  }

  /**
   * Adds the specified product to transaction order.
   */
  public function addProduct($product, $quantity = 1, $combine = TRUE) {
    // First attempt to load the transaction's order.
    // If no order existed, create one now.
    if (empty($this->order)) {
      $order = $this->doAction('createNewOrder');
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
        $this->doAction('deleteLineItem', $line_item_id);
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
        $unit_price['amount'] = $price * 100;

        $line_item_wrapper->commerce_unit_price->set($unit_price);
        commerce_line_item_rebase_unit_price($line_item_wrapper->value());

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
   * Returns or creates a new order wrapper as necessary.
   *
   * Metadata wrappers lose their reference to the original object when they're
   * loaded from form_state variables. As a result, we need to check and make
   * sure that the order wrapper is still indeed referencing the order.
   */
  protected function checkOrderWrapper() {
    if ($this->order) {
      if (($this->orderWrapper && $this->orderWrapper !== $this->order) ||
        (!$this->orderWrapper)) {
        $this->orderWrapper = entity_metadata_wrapper('commerce_order', $this->order);
      }
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
   * Call the methods if any classes that have been subscribed to this
   * transaction's actions.
   *
   * @param string $action_name
   *   The name of the action being invoked.
   * @param string $position
   *   The position of the subscription. Currently only 'after' and 'before'
   *   are actually used.
   * @param array $arguments
   *   An array of arguments to be passed to the subscription method.
   */
  protected function notifySubscribers($action_name, $position, $arguments) {
    foreach ($this->actions[$action_name][$position] as $base_class_name => $subscriptions) {
      foreach ($subscriptions as $subscription_method) {
        call_user_func_array(array($this->bases[$base_class_name], $subscription_method), $arguments);
      }
    }
  }

  /**
   * Checks for any modules defining additional base classes to be added to this
   * transaction and registers their action and subscriptions.
   *
   * Actions are invoked by calling the transaction's doAction method.
   *
   * Subscription methods are automatically called before and after an action
   * is invoked.
   */
  private function collectBases() {
    foreach (module_invoke_all('commerce_pos_transaction_base_info') as $base_info) {
      // Only add the base class if it belongs to this type, or if it didn't
      // specify any types that it belongs to.
      if (!isset($base_info['types']) || in_array($this->type, $base_info['types'])) {
        $class_name = $base_info['class'];
        $this->bases[$class_name] = new $class_name($this);
        $base_class = &$this->bases[$class_name];

        // Register all actions provided by the Base class.
        foreach ($base_class->actions() as $action_method) {
          if (!isset($this->actions[$action_method])) {
            $this->actions[$action_method] = array(
              'class' => $class_name,
              'before' => array(),
              'after' => array(),
            );
          }
          else {
            throw new Exception(t('Cannot add action @action, it has already been defined!', array(
              '@action' => $action_method,
            )));
          }
        }

        // Register all subscriptions that the Base class is subscribing to.
        foreach ($base_class->subscriptions() as $action_method => $positions) {
          foreach ($positions as $position => $subscription_methods) {
            // $position should either be 'before' or 'after'. Any others are
            // ignored.
            foreach ($subscription_methods as $subscription_method) {
              $this->actions[$action_method][$position][$class_name][] = $subscription_method;
            }
          }
        }
      }
    }
  }
}
