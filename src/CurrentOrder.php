<?php

namespace Drupal\commerce_pos;

use Drupal\commerce_order\Entity\Order;
use Drupal\user\PrivateTempStoreFactory;

/**
 * Get any product variations with the provided UPC.
 */
class CurrentOrder {

  /**
   * The tempstore object.
   *
   * @var \Drupal\user\SharedTempStore
   */
  protected $tempStore;

  /**
   * Constructs a new POS object.
   *
   * @param \Drupal\user\PrivateTempStoreFactory $temp_store_factory
   *   The tempstore factory.
   */
  public function __construct(PrivateTempStoreFactory $temp_store_factory) {
    $this->tempStore = $temp_store_factory->get('commerce_pos');
  }

  /**
   * Takes a provided order and sets its ID as the current one.
   *
   * @param \Drupal\commerce_order\Entity\Order $order
   *   The order object you wish to save the ID from.
   */
  public function set(Order $order) {
    $this->tempStore->set('order', $order->id());
  }

  /**
   * Gets the active order_id stored in the session and loads it.
   *
   * @return \Drupal\commerce_order\Entity\Order|null
   *   An entity object. NULL if no matching entity is found.
   */
  public function get() {
    $order_id = $this->tempStore->get('order');

    return Order::load($order_id);
  }

}
