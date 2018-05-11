<?php

namespace Drupal\Tests\commerce_pos\FunctionalJavascript;

use Drupal\Core\Url;
use Drupal\FunctionalJavascriptTests\JavascriptTestBase;
use Drupal\Tests\commerce_pos\Functional\CommercePosCreateStoreTrait;
use Drupal\commerce_order\Entity\Order;

/**
 * Tests the Commerce POS form.
 *
 * @group commerce_pos
 */
class ParkOrderTest extends JavascriptTestBase {
  use CommercePosCreateStoreTrait;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'search_api_db',
    'commerce_pos',
    'commerce_pos_keypad',
    'block',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->setUpStore();
    // @todo work out the expected permissions to view products etc...
    $this->drupalLogin($this->rootUser);
    $this->drupalPlaceBlock('local_tasks_block');
  }

  /**
   * Tests park/un-park of order.
   */
  public function testParkOrder() {
    $web_assert = $this->assertSession();
    $this->drupalGet('admin/commerce/pos/main');

    $this->getSession()->getPage()->fillField('register', '1');
    $this->getSession()->getPage()->fillField('float[number]', '10.00');
    $this->getSession()->getPage()->findButton('Open Register')->click();

    // Now we should be able to select order items.
    $autocomplete_field = $this->getSession()->getPage()->findField('order_items[target_id][product_selector]');
    $autocomplete_field->setValue('Jumper X');
    $this->getSession()->getDriver()->keyDown($autocomplete_field->getXpath(), 'L');
    $web_assert->waitOnAutocomplete();

    $results = $this->getSession()->getPage()->findAll('css', '.ui-autocomplete li');
    $this->assertCount(1, $results);
    // Click on of the auto-complete.
    $results[0]->click();
    $web_assert->assertWaitOnAjaxRequest();

    // Assert that the product is listed as expected.
    $web_assert->pageTextContains('Jumper');
    $web_assert->fieldValueEquals('order_items[target_id][order_items][0][quantity]', '1.00');
    $web_assert->fieldValueEquals('order_items[target_id][order_items][0][unit_price][number]', '50.00');
    $web_assert->pageTextContains('Total $50.00');
    $web_assert->pageTextContains('To Pay $50.00');

    // Check if order can be parked.
    $web_assert->buttonExists('Park Order');
    $this->getSession()->getPage()->findButton('Park Order')->click();
    $this->assertSession()->pageTextContains('Order 1 has been parked');

    // Ensure the 'Park Order' button is disabled.
    $button = $this->getSession()->getPage()->findButton('Park Order');
    $this->assertEquals('disabled', $button->getAttribute('disabled'));

    // Check whether an order cannot be retrieved if current order is not empty.
    // Add a T-Shirt.
    $autocomplete_field->setValue('T-shirt X');
    $this->getSession()->getDriver()->keyDown($autocomplete_field->getXpath(), 'L');
    $web_assert->waitOnAutocomplete();
    $results = $this->getSession()->getPage()->findAll('css', '.ui-autocomplete li');
    $this->createScreenshot(\Drupal::root() . '/sites/default/files/simpletest/screen.jpg');

    $this->assertCount(1, $results);
    // Click on of the auto-complete.
    $results[0]->click();
    $web_assert->assertWaitOnAjaxRequest();

    // Set the email for the order.
    $email_field = $this->getSession()->getPage()->findField('mail[0][value]');
    $email_field->setValue('test@test.com');
    $web_assert->waitOnAutocomplete();

    // Our current order shouldn't be order 1 anymore.
    $this->getSession()->getPage()->findButton('Park Order')->click();
    $this->assertSession()->pageTextContains('Order 2 has been parked');

    // Now check if we can see the orders in the list.
    $this->clickLink('Parked Orders');
    $web_assert->elementContains('xpath', '//*[@id="edit-result"]/table/tbody/tr[2]/td[1]/a', 1);
    $web_assert->elementContains('xpath', '//*[@id="edit-result"]/table/tbody/tr[1]/td[6]', 'test@test.com');
    $web_assert->elementContains('xpath', '//*[@id="edit-result"]/table/tbody/tr[2]/td[3]', 'Parked');
    $web_assert->elementContains('xpath', '//*[@id="edit-result"]/table/tbody/tr[2]/td[8]/a', 'Retrieve');
    $web_assert->elementContains('xpath', '//*[@id="edit-result"]/table/tbody/tr[1]/td[1]/a', 2);

    // Retrieve order 1.
    $retrieve_link = $web_assert->elementExists('xpath', '//*[@id="edit-result"]/table/tbody/tr[2]/td[8]/a');
    $retrieve_link_href = $retrieve_link->getAttribute('href');
    $retrieve_link->click();

    // Confirm we are redirected back to the POS.
    $url = Url::fromRoute('commerce_pos.main', ['commerce_order' => 1]);
    $this->assertEquals($this->getAbsoluteUrl($url->toString()), $this->getUrl());
    // Assert that the product is listed as expected.
    $web_assert->pageTextContains('Jumper');
    $web_assert->fieldValueEquals('order_items[target_id][order_items][0][quantity]', '1.00');
    $web_assert->fieldValueEquals('order_items[target_id][order_items][0][unit_price][number]', '50.00');
    $web_assert->pageTextContains('Total $50.00');
    $web_assert->pageTextContains('To Pay $50.00');

    // Order 1 has indeed been set back to 'draft'.
    $order = Order::load(1);
    $this->assertEquals($order->getState()->value, 'draft');

    // Complete the order and edit it to ensure we can not park completed
    // orders.
    $this->getSession()->getPage()->findButton('Pay Now')->click();
    $this->click('#edit-keypad-add');
    $web_assert->waitForButton('commerce-pos-finish');
    $this->click('input[name="commerce-pos-finish"]');
    $this->drupalGet(Url::fromRoute('commerce_pos.main', ['commerce_order' => 1]));
    $web_assert->buttonNotExists('Park Order');
    \Drupal::entityTypeManager()->getStorage('commerce_order')->resetCache([1]);
    $order = Order::load(1);
    $this->assertEquals($order->getState()->value, 'completed');

    // Now check if order 2 is still parked.
    $this->drupalGet(Url::fromRoute('commerce_pos.parked_order_lookup'));
    $web_assert->elementContains('xpath', '//*[@id="edit-result"]/table/tbody/tr[1]/td[1]/a', 2);

    // Ensure that trying to retrieve an order that is not parked fails. Note we
    // can not assert on status code because this is a javascript test.
    $this->drupalGet($retrieve_link_href);
    $web_assert->pageTextContains('Access denied');
  }

}
