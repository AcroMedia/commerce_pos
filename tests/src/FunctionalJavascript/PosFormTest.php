<?php

namespace Drupal\Tests\commerce_pos\FunctionalJavascript;

use Drupal\commerce_order\Entity\Order;
use Drupal\commerce_pos\Entity\Register;
use Drupal\commerce_price\Price;
use Drupal\commerce_tax\Entity\TaxType;
use Drupal\Core\Entity\Entity\EntityFormDisplay;
use Drupal\Core\Url;
use Drupal\FunctionalJavascriptTests\JavascriptTestBase;
use Drupal\Tests\commerce_pos\Functional\CommercePosCreateStoreTrait;

/**
 * Tests the Commerce POS form.
 *
 * @group commerce_pos
 */
class PosFormTest extends JavascriptTestBase {
  use CommercePosCreateStoreTrait;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'block',
    'search_api_db',
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
  public function testCommercePosForm() {
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

    // Add a T-Shirt.
    $autocomplete_field->setValue('T-Shirt X');
    $this->getSession()->getDriver()->keyDown($autocomplete_field->getXpath(), 'L');
    $web_assert->waitOnAutocomplete();
    $results = $this->getSession()->getPage()->findAll('css', '.ui-autocomplete li');
    $this->assertCount(1, $results);
    // Click on of the auto-complete.
    $results[0]->click();
    $web_assert->assertWaitOnAjaxRequest();

    $web_assert->pageTextContains('T-Shirt');
    $web_assert->fieldValueEquals('order_items[target_id][order_items][1][quantity]', '1.00');
    $web_assert->fieldValueEquals('order_items[target_id][order_items][1][unit_price][number]', '23.20');
    $web_assert->pageTextContains('Total $123.20');
    $web_assert->pageTextContains('To Pay $123.20');

    // Click on the buttons to add another Jumper.
    $this->getSession()->getPage()->fillField('order_items[target_id][order_items][0][quantity]', '3');
    $web_assert->assertWaitOnAjaxRequest();
    $web_assert->pageTextContains('Jumper');
    $web_assert->fieldValueEquals('order_items[target_id][order_items][0][quantity]', '3.00');
    $web_assert->fieldValueEquals('order_items[target_id][order_items][0][unit_price][number]', '50.00');
    $web_assert->pageTextContains('Total $173.20');
    $web_assert->pageTextContains('To Pay $173.20');

    // Change the price of jumpers on the form.
    $this->getSession()->getPage()->fillField('order_items[target_id][order_items][0][unit_price][number]', '40.50');
    $web_assert->assertWaitOnAjaxRequest();
    $web_assert->fieldValueEquals('order_items[target_id][order_items][0][unit_price][number]', '40.50');
    // (3 * 40.5) + (1 * 23.20)
    $web_assert->pageTextContains('To Pay $144.70');
    // Click on the buttons to remove all the jumpers.
    $this->getSession()->getPage()->findButton('remove_order_item_1')->click();
    $web_assert->assertWaitOnAjaxRequest();
    $web_assert->pageTextNotContains('Jumper');
    $web_assert->pageTextContains('T-Shirt');
    $web_assert->fieldValueEquals('order_items[target_id][order_items][0][unit_price][number]', '23.20');
    $web_assert->pageTextContains('Total $23.20');
    $web_assert->pageTextContains('To Pay $23.20');
    $web_assert->pageTextContains('Change $0.00');

    // Set the quantity of t-shirts using the quantity form field.
    $this->getSession()->getPage()->fillField('order_items[target_id][order_items][0][quantity]', '10');
    $web_assert->assertWaitOnAjaxRequest();
    $web_assert->pageTextContains('T-Shirt');
    $web_assert->fieldValueEquals('order_items[target_id][order_items][0][unit_price][number]', '23.20');
    $web_assert->fieldValueEquals('order_items[target_id][order_items][0][quantity]', '10.00');
    $web_assert->pageTextContains('Total $232.00');
    $web_assert->pageTextContains('To Pay $232.00');
    $web_assert->pageTextContains('Change $0.00');

    // Set the quantity to 0 to remove the T-Shirt.
    $this->getSession()->getPage()->fillField('order_items[target_id][order_items][0][quantity]', '0');
    $web_assert->assertWaitOnAjaxRequest();
    $web_assert->pageTextNotContains('T-Shirt');
    $web_assert->pageTextContains('Total $0.00');
    $web_assert->pageTextContains('To Pay $0.00');
    $web_assert->pageTextContains('Change $0.00');

    // Add a jumper and two t-shirts to test payment totals.
    $autocomplete_field->setValue('Jumper X');
    $this->getSession()->getDriver()->keyDown($autocomplete_field->getXpath(), 'L');
    $web_assert->waitOnAutocomplete();
    $results = $this->getSession()->getPage()->findAll('css', '.ui-autocomplete li');
    $results[0]->click();
    $web_assert->assertWaitOnAjaxRequest();
    $autocomplete_field->setValue('T-Shirt X');
    $this->getSession()->getDriver()->keyDown($autocomplete_field->getXpath(), 'L');
    $web_assert->waitOnAutocomplete();
    $results = $this->getSession()->getPage()->findAll('css', '.ui-autocomplete li');
    $results[0]->click();
    $web_assert->assertWaitOnAjaxRequest();

    $this->getSession()->getPage()->fillField('order_items[target_id][order_items][1][quantity]', '2');
    $web_assert->assertWaitOnAjaxRequest();
    // (1 * 50) + (2 * 23.20)
    $web_assert->pageTextContains('Total $96.40');
    $web_assert->pageTextContains('To Pay $96.40');

    // Go to the payment page.
    $this->getSession()->getPage()->findButton('Pay Now')->click();

    $this->getSession()->getPage()->fillField('keypad[amount]', '50');
    $this->click('input[name="commerce-pos-pay-keypad-add"]');
    $web_assert->pageTextContains('Total $96.40');
    $web_assert->pageTextContains('Cash $50.00');
    $web_assert->pageTextContains('Total Paid $50.00');
    $web_assert->pageTextContains('To Pay $46.40');
    $web_assert->pageTextContains('Change $0.00');
    $button = $this->getSession()->getPage()->find('css', 'input[name="commerce-pos-finish"]');
    $this->assertTrue($button->hasAttribute('disabled'), 'Finish button is disabled');

    $this->getSession()->getPage()->fillField('keypad[amount]', '46.40');
    $this->click('input[name="commerce-pos-pay-keypad-add"]');
    $web_assert->pageTextContains('Total $96.40');
    $web_assert->pageTextContains('Cash $50.00');
    $web_assert->pageTextContains('Cash $46.40');
    $web_assert->pageTextContains('Total Paid $96.40');
    $web_assert->pageTextContains('To Pay $0.00');
    $web_assert->pageTextContains('Change $0.00');
    $button = $this->getSession()->getPage()->find('css', 'input[name="commerce-pos-finish"]');
    $this->assertFalse($button->hasAttribute('disabled'), 'Finish button is enabled');

    // Void Payment.
    $void_buttons = $this->getSession()->getPage()->findAll('css', 'input[name="commerce-pos-pay-keypad-remove"]');
    $void_buttons[0]->click();
    $web_assert->pageTextContains('Cash VOID');
    $this->assertTrue($button->hasAttribute('disabled'), 'Finish button is disabled after void');

    // Clicking back to order will take us to order page.
    $this->click('input[name="commerce-pos-back-to-order"]');
    // Add one more T-shirt.
    $this->getSession()->getPage()->fillField('order_items[target_id][order_items][1][quantity]', '3');
    $web_assert->assertWaitOnAjaxRequest();
    $web_assert->pageTextContains('Total $119.60');
    $web_assert->pageTextContains('Cash $50.00');
    $web_assert->pageTextContains('Cash VOID');
    $web_assert->pageTextContains('Total Paid $50.00');
    $web_assert->pageTextContains('To Pay $69.60');
    $web_assert->pageTextContains('Change $0.00');

    // Go to the payment page.
    $this->getSession()->getPage()->findButton('Pay Now')->click();

    $web_assert->pageTextContains('Total $119.60');
    $web_assert->pageTextContains('Cash $50.00');
    $web_assert->pageTextContains('Cash VOID');
    $web_assert->pageTextContains('Total Paid $50.00');
    $web_assert->pageTextContains('To Pay $69.60');
    $web_assert->pageTextContains('Change $0.00');

    $this->getSession()->getPage()->fillField('keypad[amount]', '80');
    $this->click('input[name="commerce-pos-pay-keypad-add"]');
    $web_assert->pageTextContains('Total $119.60');
    $web_assert->pageTextContains('Cash $50.00');
    $web_assert->pageTextContains('Cash VOID');
    $web_assert->pageTextContains('Cash $80.00');
    $web_assert->pageTextContains('Total Paid $130.00');
    $web_assert->pageTextContains('Change $10.40');
    $web_assert->pageTextContains('To Pay $0.00');

    // Clicking finish will bring us back to the order item screen - processing
    // a new order.
    $this->click('input[name="commerce-pos-finish"]');
    $web_assert->pageTextContains('Total $0.00');
    $web_assert->pageTextNotContains('Cash');
    $web_assert->pageTextContains('To Pay $0.00');
    $web_assert->pageTextContains('Change $0.00');
    $web_assert->pageTextNotContains('Jumper');

    // Ensure the order is completed and that payments can no longer be voided.
    $this->assertEquals('completed', Order::load(1)->getState()->value);
    $this->drupalGet(Url::fromRoute('commerce_pos.main', ['commerce_order' => 1]));
    $web_assert->pageTextContains('Total Paid $130.00');
    $void_buttons = $this->getSession()->getPage()->findAll('css', 'input[name="commerce-pos-pay-keypad-remove"]');
    $this->assertCount(0, $void_buttons);
  }

  /**
   * Tests a simple POS flow similar to the main test, but with a tax enabled.
   *
   * Done as a separate test because having taxes make the numbers in the main
   * tests more confusing.
   */
  public function testPosFormWithTaxes() {
    // The default store is US-WI, so imagine that the US has VAT.
    TaxType::create([
      'id' => 'pos_test',
      'label' => 'POS Tax',
      'plugin' => 'custom',
      'configuration' => [
        'display_inclusive' => FALSE,
        'rates' => [
          [
            'id' => 'standard',
            'label' => 'Standard',
            'percentage' => '0.2',
          ],
        ],
        'territories' => [
          ['country_code' => 'US', 'administrative_area' => 'WI'],
          ['country_code' => 'US', 'administrative_area' => 'SC'],
        ],
      ],
    ])->save();

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

    // Assert the product is listed as expected and tax is applied properly.
    $web_assert->pageTextContains('Jumper');
    $web_assert->fieldValueEquals('order_items[target_id][order_items][0][quantity]', '1.00');
    $web_assert->fieldValueEquals('order_items[target_id][order_items][0][unit_price][number]', '50.00');
    $web_assert->pageTextContains('Tax $10.00');
    $web_assert->pageTextContains('Total $60.00');
    $web_assert->pageTextContains('To Pay $60.00');
  }

  /**
   * Tests the Widget settings.
   */
  public function testWidgetSettings() {
    $web_assert = $this->assertSession();

    // Change some of the settings so we can test.
    $form_display = EntityFormDisplay::load('commerce_order.pos.default');
    $settings = $form_display->getComponent('order_items');
    $settings['settings']['num_results'] = 1;
    $settings['settings']['placeholder'] = $this->randomString(20);
    $form_display->setComponent('order_items', $settings)->save();

    $this->drupalGet('admin/commerce/pos/main');

    $this->getSession()->getPage()->fillField('register', '1');
    $this->getSession()->getPage()->fillField('float[number]', '10.00');
    $this->getSession()->getPage()->findButton('Open Register')->click();

    $autocomplete_field = $this->getSession()
      ->getPage()
      ->findField('order_items[target_id][product_selector]');
    $this->assertEquals($settings['settings']['placeholder'], $autocomplete_field->getAttribute('placeholder'));

    // Ensure that the auto-complete only returns 1 value.
    $autocomplete_field->setValue('T');
    $this->getSession()->getDriver()->keyDown($autocomplete_field->getXpath(), '-');
    $web_assert->waitOnAutocomplete();
    $results = $this->getSession()->getPage()->findAll('css', '.ui-autocomplete li');
    $this->assertCount(1, $results);
  }

  /**
   * Tests the current order logic on the POS form.
   */
  public function testCommercePosFormCurrentOrder() {
    $this->drupalPlaceBlock('local_tasks_block');
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

    // Test the current order persistence. We should be able to navigate away
    // from the page and return to it.
    $this->drupalGet('');
    $this->drupalGet('admin/commerce/pos/main');

    // Assert that the product is listed as expected.
    $web_assert->pageTextContains('Jumper');
    $web_assert->fieldValueEquals('order_items[target_id][order_items][0][quantity]', '1.00');
    $web_assert->fieldValueEquals('order_items[target_id][order_items][0][unit_price][number]', '50.00');
    $web_assert->pageTextContains('Total $50.00');
    $web_assert->pageTextContains('To Pay $50.00');

    // Close the register and re-open it.
    $this->clickLink('Close Register');
    $web_assert->pageTextContains('Register Test register has been closed.');
    $this->getSession()->getPage()->findButton('Open Register')->click();

    // We've opened the register, we should not have the previous order.
    $web_assert->pageTextNotContains('Jumper');
    $web_assert->pageTextContains('Total $0.00');
    $web_assert->pageTextContains('To Pay $0.00');

    // Create a new register and close the current one.
    $register = Register::create([
      'store_id' => $this->store->id(),
      'name' => 'Another register',
      'default_float' => new Price('100.00', 'USD'),
    ]);
    $register->save();
    $this->clickLink('Close Register');

    $web_assert->pageTextContains('Register Test register has been closed.');

    // Open the new register. The current order will be reset.
    $this->getSession()->getPage()->fillField('register', $register->id());
    $this->getSession()->getPage()->findButton('Open Register')->click();
    $web_assert->pageTextContains('Total $0.00');
    $web_assert->pageTextNotContains('Cash');
    $web_assert->pageTextContains('To Pay $0.00');
    $web_assert->pageTextContains('Change $0.00');
    $web_assert->pageTextNotContains('Jumper');

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
    $web_assert->pageTextContains('Total $50.00');

    // Park the order and force the register to change. This should change the
    // register on the order.
    $this->getSession()->getPage()->findButton('Park Order')->click();
    $this->assertEquals(2, Order::load(3)->field_register->entity->id());
    $this->clickLink('Close Register');
    $this->clickLink('Parked Orders');
    $this->clickLink('Retrieve');
    $this->getSession()->getPage()->selectFieldOption('register', '1');
    $this->getSession()->getPage()->findButton('Open Register')->click();

    // Assert that the product is listed as expected and the order has moved to
    // the correct register.
    $web_assert->pageTextContains('Jumper');
    $web_assert->pageTextContains('Total $50.00');
    \Drupal::entityTypeManager()->getStorage('commerce_order')->resetCache();
    $this->assertEquals(1, Order::load(3)->field_register->entity->id());

    // Complete the order, open the other register and ensure that editing the
    // completed order does not change the register.
    $this->getSession()->getPage()->findButton('Pay Now')->click();
    $this->click('input[name="commerce-pos-pay-keypad-add"]');
    $this->click('input[name="commerce-pos-finish"]');
    $this->clickLink('Close Register');
    // Edit the completed order.
    $this->drupalGet(Url::fromRoute('commerce_pos.main', ['commerce_order' => 3]));
    $this->getSession()->getPage()->selectFieldOption('register', '2');
    $this->getSession()->getPage()->findButton('Open Register')->click();
    $web_assert->pageTextContains('Jumper');
    $web_assert->pageTextContains('Total $50.00');
    \Drupal::entityTypeManager()->getStorage('commerce_order')->resetCache();
    // The register should not have changed for a completed order.
    $this->assertEquals(1, Order::load(3)->field_register->entity->id());
  }

  /**
   * Waits for jQuery to become active and animations to complete.
   */
  protected function waitForAjaxToFinish() {
    $condition = "(0 === jQuery.active && 0 === jQuery(':animated').length)";
    $this->assertJsCondition($condition, 10000);
  }

}
