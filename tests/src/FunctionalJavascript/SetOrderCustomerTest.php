<?php

namespace Drupal\Tests\commerce_pos\FunctionalJavascript;

use Drupal\FunctionalJavascriptTests\JavascriptTestBase;
use Drupal\Tests\commerce_pos\Functional\CommercePosCreateStoreTrait;

/**
 * Tests setting the order customer functionality via the POS form.
 *
 * @group commerce_pos
 */
class SetOrderCustomerTest extends JavascriptTestBase {
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

    $this->setUpStore();

    // @todo work out the expected permissions to view products etc...
    $this->drupalLogin($this->rootUser);
  }

  /**
   * Tests adding and removing products from the POS form.
   */
  public function testCustomerWidget() {
    $web_assert = $this->assertSession();
    $this->drupalGet('admin/commerce/pos/main');

    $this->getSession()->getPage()->fillField('register', '1');
    $this->getSession()->getPage()->fillField('float[number]', '10.00');
    $this->getSession()->getPage()->findButton('Open Register')->click();

    $this->createScreenshot(\Drupal::root() . '/sites/default/files/simpletest/screen.jpg');

    // Confirm we have a customer fieldset with radio buttons.
    $web_assert->fieldExists('uid[0][target_id][order_customer][customer_type]');
    $web_assert->pageTextContains('Existing Customer');
    $web_assert->pageTextContains('New Customer');
    // Confirm we see a textfield to enter the username, name, or email.
    $web_assert->fieldExists('uid[0][target_id][order_customer][user]');

    // ---CONFIRM 'ANON' USER BY COMPLETING ORDER WITHOUT SETTING A CUSTOMER---.
    // Add an order item to the POS order.
    $autocomplete_field = $this->getSession()->getPage()->findField('order_items[target_id][product_selector]');
    $autocomplete_field->setValue('Jum');
    $this->getSession()->getDriver()->keyDown($autocomplete_field->getXpath(), 'p');
    $web_assert->waitOnAutocomplete();
    $results = $this->getSession()->getPage()->findAll('css', '.ui-autocomplete li');
    $this->assertCount(3, $results);
    // Click on the auto-complete.
    $results[0]->click();
    $web_assert->assertWaitOnAjaxRequest();

    // Finish checkout.
    $this->getSession()->getPage()->findButton('Payments and Completion')->click();
    $this->getSession()->getPage()->fillField('keypad[amount]', '50');
    $this->click('input[name="commerce-pos-pay-keypad-add"]');
    $this->waitForAjaxToFinish();
    $this->click('input[name="commerce-pos-finish"]');
    $this->waitForAjaxToFinish();

    // Now, click on the 'Order Lookup' tab and confirm the order user email.
    $this->drupalGet('admin/commerce/pos/orders');
    $web_assert->pageTextContains('Anonymous');

    // ---CONFIRM COMPLETING ORDER BY SETTING A NEW CUSTOMER---.
    $this->drupalGet('admin/commerce/pos/main');

    // Click the 'New Customer' radio button and confirm the different fields.
    $this->getSession()->getPage()->selectFieldOption('uid[0][target_id][order_customer][customer_type]', 'new');
    $this->waitForAjaxToFinish();
    $web_assert->fieldExists('uid[0][target_id][order_customer][email]');
    $web_assert->fieldExists('uid[0][target_id][order_customer][pos_phone_number]');

    // Add an order item to the POS order.
    $autocomplete_field = $this->getSession()->getPage()->findField('order_items[target_id][product_selector]');
    $autocomplete_field->setValue('Jum');
    $this->getSession()->getDriver()->keyDown($autocomplete_field->getXpath(), 'p');
    $web_assert->waitOnAutocomplete();
    $results = $this->getSession()->getPage()->findAll('css', '.ui-autocomplete li');
    $this->assertCount(3, $results);
    // Click on the auto-complete.
    $results[0]->click();
    $web_assert->assertWaitOnAjaxRequest();

    // Now create a new user and set that user as the order customer.
    $user_email_field = $this->getSession()->getPage()->findField('uid[0][target_id][order_customer][email]');
    $user_email_field->setValue('test@test.com');
    $this->getSession()->getPage()->findButton('Set Customer')->click();
    $this->waitForAjaxToFinish();

    // Finish checkout.
    $this->getSession()->getPage()->findButton('Payments and Completion')->click();
    $this->getSession()->getPage()->fillField('keypad[amount]', '50');
    $this->click('input[name="commerce-pos-pay-keypad-add"]');
    $this->waitForAjaxToFinish();
    $this->click('input[name="commerce-pos-finish"]');
    $this->waitForAjaxToFinish();

    // Now, click on the 'Order Lookup' tab and confirm the order user email.
    $this->drupalGet('admin/commerce/pos/orders');
    $web_assert->elementTextContains('xpath', '//*[@id="edit-results"]/table/tbody/tr[1]/td[5]/a', 'test@test.com');

    // ---CONFIRM COMPLETING ORDER BY SETTING AN EXISTING CUSTOMER---.
    $this->drupalGet('admin/commerce/pos/main');

    // Now select an existing user and set that user as the order customer.
    $autocomplete_field = $this->getSession()->getPage()->findField('uid[0][target_id][order_customer][user]');
    $autocomplete_field->setValue('t');
    $this->getSession()->getDriver()->keyDown($autocomplete_field->getXpath(), 'e');
    $web_assert->waitOnAutocomplete();
    $results = $this->getSession()->getPage()->findAll('css', '.ui-autocomplete li');
    // Click on the auto-complete.
    $results[0]->click();
    $web_assert->assertWaitOnAjaxRequest();
    $this->getSession()->getPage()->findButton('Set Customer')->click();
    $this->waitForAjaxToFinish();

    // Add an order item to the POS order.
    $autocomplete_field = $this->getSession()->getPage()->findField('order_items[target_id][product_selector]');
    $autocomplete_field->setValue('Jum');
    $this->getSession()->getDriver()->keyDown($autocomplete_field->getXpath(), 'p');
    $web_assert->waitOnAutocomplete();
    $results = $this->getSession()->getPage()->findAll('css', '.ui-autocomplete li');
    $this->assertCount(3, $results);
    // Click on the auto-complete.
    $results[0]->click();
    $web_assert->assertWaitOnAjaxRequest();

    // Finish checkout.
    $this->getSession()->getPage()->findButton('Payments and Completion')->click();
    $this->getSession()->getPage()->fillField('keypad[amount]', '50');
    $this->click('input[name="commerce-pos-pay-keypad-add"]');
    $this->waitForAjaxToFinish();
    $this->click('input[name="commerce-pos-finish"]');
    $this->waitForAjaxToFinish();

    // Now, click on the 'Order Lookup' tab and confirm the order user email.
    $this->drupalGet('admin/commerce/pos/orders');
    $web_assert->elementTextContains('xpath', '//*[@id="edit-results"]/table/tbody/tr[2]/td[5]/a', 'test@test.com');
  }

  /**
   * Waits for jQuery to become active and animations to complete.
   */
  protected function waitForAjaxToFinish() {
    $condition = "(0 === jQuery.active && 0 === jQuery(':animated').length)";
    $this->assertJsCondition($condition, 10000);
  }

}
