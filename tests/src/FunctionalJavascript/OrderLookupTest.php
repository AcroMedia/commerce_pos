<?php

namespace Drupal\Tests\commerce_pos\FunctionalJavascript;

use Drupal\commerce_pos\Entity\Register;
use Drupal\commerce_price\Price;
use Drupal\FunctionalJavascriptTests\JavascriptTestBase;
use Drupal\Tests\commerce_pos\Functional\CommercePosCreateStoreTrait;
use Drupal\commerce_order\Entity\Order;

/**
 * Tests the Commerce POS form.
 *
 * @group commerce_pos
 */
class OrderLookupTest extends JavascriptTestBase {
  use CommercePosCreateStoreTrait;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'commerce_pos',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    // Initial store set up.
    $test_store = $this->createStore('POS test store', 'pos_test_store@example.com', 'physical');
    $this->store = $test_store;

    $this->adminUser = $this->drupalCreateUser(['access commerce pos order lookup', 'access commerce pos pages']);
    $this->drupalLogin($this->adminUser);
  }

  /**
   * Tests the POS order lookup function when we have an invalid order ID.
   */
  public function testOrderLookup() {
    $web_assert = $this->assertSession();

    $this->drupalGet('admin/commerce/pos/orders');

    // Test the order lookup error message when we can't find an order.
    $this->getSession()->getPage()->fillField('search_box', '-1');
    $this->waitForAjaxToFinish();
    $web_assert->pageTextContains('The order could not be found or does not exist.');

    $web_assert = $this->assertSession();

    /* @var \Drupal\commerce_order\Entity\Order $order */
    $order = Order::create([
      'type' => 'pos',
      'store_id' => $this->store->id(),
    ]);
    $order->save();

    // Now, go to our POS page and test looking up the order ID.
    $this->drupalGet('admin/commerce/pos/orders');

    // Lookup the order ID.
    $this->getSession()->getPage()->fillField('search_box', $order->id());
    $this->waitForAjaxToFinish();
    $web_assert->elementContains('css', '#order-lookup-results > div > table > tbody > tr > td:nth-child(1) > a', $order->id());
    $web_assert->elementContains('css', '#order-lookup-results > div > table > tbody > tr > td:nth-child(2)', 'Draft');
  }

  /**
   * Waits for jQuery to become active and animations to complete.
   */
  protected function waitForAjaxToFinish() {
    $condition = "(0 === jQuery.active && 0 === jQuery(':animated').length)";
    $this->assertJsCondition($condition, 10000);
  }

}
