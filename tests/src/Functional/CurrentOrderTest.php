<?php

namespace Drupal\Tests\commerce_pos\Functional;

use Drupal\Tests\commerce\Functional\CommerceBrowserTestBase;
use Drupal\commerce_order\Entity\Order;

/**
 * Tests the CurrentOrder get and set.
 *
 * @coversDefaultClass \Drupal\commerce_pos\CurrentOrder
 * @group commerce_pos
 */
class CurrentOrderTest extends CommerceBrowserTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'commerce_pos',
  ];

  /**
   * Test an order can get set and retrieved correctly.
   */
  public function testSetGet() {
    // Ensure that before an order is set the current order is null.
    $this->assertNull($this->container->get('commerce_pos.current_order')->get());

    $order = Order::create([
      'type' => 'pos',
    ]);

    $order->save();

    $this->container->get('commerce_pos.current_order')->set($order);

    $retrieved_order = $this->container->get('commerce_pos.current_order')->get();

    $this->assertEquals($order->id(), $retrieved_order->id());
  }

}
