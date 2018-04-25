<?php

namespace Drupal\Tests\commerce_pos\FunctionalJavascript;

use Drupal\Core\Url;
use Drupal\FunctionalJavascriptTests\JavascriptTestBase;
use Drupal\Tests\commerce_pos\Functional\CommercePosCreateStoreTrait;

/**
 * Tests the Commerce POS return form.
 *
 * @group commerce_pos
 */
class PosReturnFormTest extends JavascriptTestBase {
  use CommercePosCreateStoreTrait;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'search_api_db',
    'commerce_pos',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    /* @var \Drupal\Core\Extension\ModuleInstallerInterface $module_installer */
    parent::setUp();

    $this->setUpStore();

    // @todo work out the expected permissions to view products etc...
    $this->drupalLogin($this->rootUser);
  }

  /**
   * Tests adding and removing products from the POS form.
   */
  public function testCommercePosReturnForm() {
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

    // After selecting something from the autocomplete list the value should be
    // blank again.
    $web_assert->fieldValueEquals('order_items[target_id][product_selector]', '');

    // Add another of the same Jumper.
    $autocomplete_field->setValue('Jumper X');
    $this->getSession()->getDriver()->keyDown($autocomplete_field->getXpath(), 'L');
    $web_assert->waitOnAutocomplete();
    $results = $this->getSession()->getPage()->findAll('css', '.ui-autocomplete li');
    $this->assertCount(1, $results);
    // Click on of the auto-complete.
    $results[0]->click();
    $web_assert->assertWaitOnAjaxRequest();

    $web_assert->pageTextContains('Jumper');
    $web_assert->fieldValueEquals('order_items[target_id][order_items][0][quantity]', '2.00');
    $web_assert->fieldValueEquals('order_items[target_id][order_items][0][unit_price][number]', '50.00');
    $web_assert->pageTextContains('Total $100.00');
    $web_assert->pageTextContains('To Pay $100.00');

    // Go to the payment page.
    $this->getSession()->getPage()->findButton('Payments and Completion')->click();

    $web_assert->pageTextContains('Total $100.00');
    $web_assert->pageTextContains('To Pay $100.00');
    $web_assert->pageTextContains('Change $0.00');

    $this->getSession()->getPage()->fillField('keypad[amount]', '100');
    $this->click('input[name="commerce-pos-pay-keypad-add"]');
    $web_assert->pageTextContains('Total $100.00');
    $web_assert->pageTextContains('Cash $100.00');
    $web_assert->pageTextContains('Total Paid $100.00');
    $web_assert->pageTextContains('To Pay $0.00');
    $web_assert->pageTextContains('Change $0.00');

    // Clicking finish will bring us back to the order item screen - processing
    // a new order.
    $this->click('input[name="commerce-pos-finish"]');
    $this->waitForAjaxToFinish();
    $web_assert->pageTextContains('Total $0.00');
    $web_assert->pageTextNotContains('Cash');
    $web_assert->pageTextContains('To Pay $0.00');
    $web_assert->pageTextContains('Change $0.00');
    $web_assert->pageTextNotContains('Jumper');

    // Now, click on the 'Order Lookup' tab.
    $this->drupalGet('admin/commerce/pos/orders');

    // Click on our newly created order's edit button so we can do a return.
    $web_assert->pageTextContains('edit');
    $this->getSession()->getPage()->clickLink('edit');
    $url = Url::fromRoute('commerce_pos.main', ['commerce_order' => 1]);
    $this->assertEquals($this->getAbsoluteUrl($url->toString()), $this->getUrl());

    // Ensure the totals are correct.
    $web_assert->pageTextContains('Total $100.00');
    $web_assert->pageTextContains('Cash $100.00');
    $web_assert->pageTextContains('Total Paid $100.00');
    $web_assert->pageTextContains('To Pay $0.00');
    $web_assert->pageTextContains('Change $0.00');

    // Ensure we have all the items as expected.
    $web_assert->pageTextContains('Jumper');
    $web_assert->fieldValueEquals('order_items[target_id][order_items][0][quantity]', '2.00');
    $web_assert->fieldValueEquals('order_items[target_id][order_items][0][unit_price][number]', '50.00');

    // Ensure the 'Return' button exists and then click it.
    $return_button_xpath = '//*[@id="edit-order-items-target-id-order-items-0-return-order-item"]';
    $return_link = $web_assert->elementExists('xpath', $return_button_xpath);
    $return_link->click();
    $this->waitForAjaxToFinish();

    // Ensure a return item is created.
    $web_assert->fieldValueEquals('order_items[target_id][order_items][1][quantity]', '1.00');
    $web_assert->fieldValueEquals('order_items[target_id][order_items][1][unit_price][number]', '-50.00');

    // Ensure the totals are correct.
    $web_assert->pageTextContains('Total $50.00');
    $web_assert->pageTextContains('Cash $100.00');
    $web_assert->pageTextContains('Total Paid $100.00');
    $web_assert->pageTextContains('To Pay $0.00');
    $web_assert->pageTextContains('Change $50.00');

    // Go to the payment page.
    $this->getSession()->getPage()->findButton('Payments and Completion')->click();

    // Ensure we don't have to pay anything and the totals are correct.
    $web_assert->pageTextNotContains('Enter Cash Amount');
    $web_assert->pageTextContains('Total $50.00');
    $web_assert->pageTextContains('Cash $100.00');
    $web_assert->pageTextContains('Total Paid $100.00');
    $web_assert->pageTextContains('To Pay $0.00');
    $web_assert->pageTextContains('Change $50.00');
  }

  /**
   * Waits for jQuery to become active and animations to complete.
   */
  protected function waitForAjaxToFinish() {
    $condition = "(0 === jQuery.active && 0 === jQuery(':animated').length)";
    $this->assertJsCondition($condition, 10000);
  }

}
