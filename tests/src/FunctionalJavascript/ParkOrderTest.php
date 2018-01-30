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
    'commerce_pos',
    'block'
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
    // There is only one register.
    $web_assert->fieldValueEquals('register', 1);
    $web_assert->pageTextContains('Test register');
    $this->drupalPostForm(NULL, [], 'Select Register');

    // Now we should be able to select order items.
    $autocomplete_field = $this->getSession()->getPage()->findField('order_items[target_id][product_selector]');
    $autocomplete_field->setValue('Jum');
    $this->getSession()->getDriver()->keyDown($autocomplete_field->getXpath(), 'p');
    $web_assert->waitOnAutocomplete();

    $results = $this->getSession()->getPage()->findAll('css', '.ui-autocomplete li');
    $this->assertCount(3, $results);
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
    $this->getSession()->getPage()->findButton('Park Order')->click();
    $this->assertSession()->pageTextContains('Order 1 has been parked');

    // Ensure the 'Park Order' button is disabled.
    $button = $this->getSession()->getPage()->findButton('Park Order');
    $this->assertEquals('disabled', $button->getAttribute('disabled'));

    // Check whether an order cannot be retrieved if current order is not empty.
    $autocomplete_field = $this->getSession()->getPage()->findField('order_items[target_id][product_selector]');
    $autocomplete_field->setValue('Jum');
    $this->getSession()->getDriver()->keyDown($autocomplete_field->getXpath(), 'p');
    $web_assert->waitOnAutocomplete();
    $results = $this->getSession()->getPage()->findAll('css', '.ui-autocomplete li');
    $this->assertCount(3, $results);
    $results[0]->click();
    $web_assert->assertWaitOnAjaxRequest();

    // Our current order shouldn't be order 1 anymore.
    $this->getSession()->getPage()->findButton('Park Order')->click();
    $this->assertSession()->pageTextContains('Order 2 has been parked');

    // Now check if we can see the orders in the list.
    $this->clickLink('Parked Orders');

    $out = $this->getSession()->getPage()->getContent();
    $html_output = 'GET request to: ' . $this->getSession()->getCurrentUrl();
    $html_output .= '<hr />' . $out;
    $html_output .= $this->getHtmlOutputHeaders();
    $this->htmlOutput($html_output);

    $web_assert->elementContains('xpath', '//*[@id="edit-result"]/table/tbody/tr[2]/td[1]/a', 1);
    $web_assert->elementContains('xpath', '//*[@id="edit-result"]/table/tbody/tr[2]/td[3]', 'Parked');
    $web_assert->elementContains('xpath', '//*[@id="edit-result"]/table/tbody/tr[2]/td[7]/a', 'Retrieve');
    $web_assert->elementContains('xpath', '//*[@id="edit-result"]/table/tbody/tr[1]/td[1]/a', 2);

    // Retrieve order 1.
    $retrieve_link = $web_assert->elementExists('xpath', '//*[@id="edit-result"]/table/tbody/tr[2]/td[7]/a');
    $retrieve_link_href = $retrieve_link->getAttribute('href');
    $retrieve_link->click();

    // Confirm we are redirected back to the POS.
    $url = Url::fromRoute('commerce_pos.main');
    $this->assertEquals($this->getAbsoluteUrl($url->toString()), $this->getUrl());

    // Now check if order 2 is still parked
    $this->clickLink('Parked Orders');
    $web_assert->elementContains('xpath', '//*[@id="edit-result"]/table/tbody/tr[1]/td[1]/a', 2);

    // Ensure that trying to retrieve an order that is not parked fails. Note we
    // can not assert on status code because this is a javascript test.
    $this->drupalGet($retrieve_link_href);
    $web_assert->pageTextContains('Access denied');

    // Order 1 has indeed been set back to 'draft'
    $order = Order::load(1);
    $this->assertEquals($order->getState()->value, 'draft');

    // And confirm our current order is Order 1 again.
    $order = \Drupal::service('commerce_pos.current_order')->get();
    $this->assertEquals($order->id(), 1);

  }

  /**
   * Waits for jQuery to become active and animations to complete.
   */
  protected function waitForAjaxToFinish() {
    $condition = "(0 === jQuery.active && 0 === jQuery(':animated').length)";
    $this->assertJsCondition($condition, 10000);
  }

}
