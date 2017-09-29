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

  public function set($order) {
    $this->tempStore->set('order', $order->id());
  }

  /**
   * @return mixed
   */
  public function get() {
    $order_id = $this->tempStore->get('order');

    return Order::load($order_id);
  }

}
