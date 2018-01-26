<?php

namespace Drupal\Tests\commerce_pos_receipt\FunctionalJavascript;

use Drupal\FunctionalJavascriptTests\JavascriptTestBase;
use Drupal\Tests\commerce_pos\Functional\CommercePosCreateStoreTrait;

/**
 * @group commerce_pos_receipt
 */
class PosReceiptTest extends JavascriptTestBase {
  use CommercePosCreateStoreTrait;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'commerce_pos_receipt',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->setUpStore();
    // @todo work out the expected permissions to view products etc...
    $this->drupalLogin($this->rootUser);
  }

  /**
   * Tests receipt function.
   */
  public function testReceipt() {
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
    // Click on of the auto-complete.
    $results[0]->click();
    $web_assert->assertWaitOnAjaxRequest();

    // We now have a product selected and current order is in progress.
    $this->drupalGet('admin/commerce/orders');
    $web_assert->elementsCount('css', '.views-view-table tbody tr', 1);
    $web_assert->pageTextContains('Point of Sale');
    $web_assert->pageTextContains('Draft');
    $web_assert->pageTextContains('Edit');
    $web_assert->pageTextNotContains('Show receipt');

    // Go back to the order
    $this->drupalGet('admin/commerce/pos/main');
    // Go to the payment page.
    $this->click('.commerce-pos input[name="op"]');
    $this->click('input[name="commerce-pos-pay-keypad-add"]');
    $this->click('input[name="commerce-pos-finish"]');
    $this->waitForAjaxToFinish();
    // We now have a complete order and new draft order.
    $this->drupalGet('admin/commerce/orders');
    // The first row has a Show receipt action and the second does not.
    $web_assert->elementContains('xpath', '//table[contains(@class, "views-view-table")]/tbody/tr[1]', 'Show receipt');
    $web_assert->elementNotContains('xpath', '//table[contains(@class, "views-view-table")]/tbody/tr[2]', 'Show receipt');
  }

  /**
   * Waits for jQuery to become active and animations to complete.
   */
  protected function waitForAjaxToFinish() {
    $condition = "(0 === jQuery.active && 0 === jQuery(':animated').length)";
    $this->assertJsCondition($condition, 10000);
  }

}