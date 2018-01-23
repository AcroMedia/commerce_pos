<?php

namespace Drupal\Tests\commerce_pos\FunctionalJavascript;

use Drupal\commerce_pos\Entity\Register;
use Drupal\commerce_price\Price;
use Drupal\Core\Entity\Entity\EntityFormDisplay;
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
    'commerce_pos',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    // Initial store set up.
    $test_store = $this->createStore('POS test store', 'pos_test_store@example.com', 'physical');

    $register = Register::create([
      'store_id' => $test_store->id(),
      'name' => 'Test register',
      'cash' => new Price('1000.00', 'USD'),
    ]);
    $register->save();

    $variations = [
      $this->createProductionVariation([
        'title' => 'T-shirt XL',
        'price' => new Price("23.20", 'USD'),
      ]),
      $this->createProductionVariation(['title' => 'T-shirt L']),
      $this->createProductionVariation(['title' => 'T-shirt M']),
    ];

    $this->createProduct([
      'variations' => $variations,
      'title' => 'T-shirt',
      'stores' => [$test_store],
    ]);

    $variations = [
      $this->createProductionVariation([
        'title' => 'Jumper XL',
        'price' => new Price("50", 'USD'),
      ]),
      $this->createProductionVariation(['title' => 'Jumper L']),
      $this->createProductionVariation(['title' => 'Jumper M']),
    ];

    $this->createProduct([
      'variations' => $variations,
      'title' => 'Jumper',
      'stores' => [$test_store],
    ]);

    // @todo work out the expected permissions to view products etc...
    $this->drupalLogin($this->rootUser);
  }

  /**
   * Tests adding and removing products from the POS form.
   */
  public function testCommercePosForm() {
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

    // After selecting something from the autocomplete list the value should be
    // blank again.
    $web_assert->fieldValueEquals('order_items[target_id][product_selector]', '');

    // Add another of the same Jumper.
    $autocomplete_field->setValue('Jum');
    $this->getSession()->getDriver()->keyDown($autocomplete_field->getXpath(), 'p');
    $web_assert->waitOnAutocomplete();
    $results = $this->getSession()->getPage()->findAll('css', '.ui-autocomplete li');
    $this->assertCount(3, $results);
    // Click on of the auto-complete.
    $results[0]->click();
    $web_assert->assertWaitOnAjaxRequest();

    $web_assert->pageTextContains('Jumper');
    $web_assert->fieldValueEquals('order_items[target_id][order_items][0][quantity]', '2.00');
    $web_assert->fieldValueEquals('order_items[target_id][order_items][0][unit_price][number]', '50.00');
    $web_assert->pageTextContains('Total $100.00');
    $web_assert->pageTextContains('To Pay $100.00');

    // Add a T-Shirt.
    $autocomplete_field->setValue('T');
    $this->getSession()->getDriver()->keyDown($autocomplete_field->getXpath(), '-');
    $web_assert->waitOnAutocomplete();
    $results = $this->getSession()->getPage()->findAll('css', '.ui-autocomplete li');
    $this->assertCount(3, $results);
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
    $autocomplete_field->setValue('Jum');
    $this->getSession()->getDriver()->keyDown($autocomplete_field->getXpath(), 'p');
    $web_assert->waitOnAutocomplete();
    $results = $this->getSession()->getPage()->findAll('css', '.ui-autocomplete li');
    $results[0]->click();
    $web_assert->assertWaitOnAjaxRequest();
    $autocomplete_field->setValue('T');
    $this->getSession()->getDriver()->keyDown($autocomplete_field->getXpath(), '-');
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
    $this->click('.commerce-pos input[name="op"]');

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

    // Clicking back to order will take us to order page.
    $this->click('input[name="commerce-pos-back-to-order"]');
    // Add one more T-shirt.
    $this->getSession()->getPage()->fillField('order_items[target_id][order_items][1][quantity]', '3');
    $web_assert->assertWaitOnAjaxRequest();

    $web_assert->pageTextContains('Total $119.60');
    $web_assert->pageTextContains('Cash $50.00');
    $web_assert->pageTextContains('Cash $46.40');
    $web_assert->pageTextContains('Total Paid $96.40');
    $web_assert->pageTextContains('To Pay $23.20');
    $web_assert->pageTextContains('Change $0.00');

    // Go to the payment page.
    $this->click('.commerce-pos input[name="op"]');

    $web_assert->pageTextContains('Total $119.60');
    $web_assert->pageTextContains('Cash $50.00');
    $web_assert->pageTextContains('Cash $46.40');
    $web_assert->pageTextContains('Total Paid $96.40');
    $web_assert->pageTextContains('To Pay $23.20');
    $web_assert->pageTextContains('Change $0.00');

    $this->getSession()->getPage()->fillField('keypad[amount]', '30');
    $this->click('input[name="commerce-pos-pay-keypad-add"]');
    $web_assert->pageTextContains('Total $119.60');
    $web_assert->pageTextContains('Cash $50.00');
    $web_assert->pageTextContains('Cash $46.40');
    $web_assert->pageTextContains('Cash $30.00');
    $web_assert->pageTextContains('Total Paid $126.40');
    $web_assert->pageTextContains('Change $6.80');
    $web_assert->pageTextContains('To Pay $0.00');

    // Clicking finish will bring us back to the order item screen - processing
    // a new order.
    $this->click('input[name="commerce-pos-finish"]');
    $this->waitForAjaxToFinish();
    $web_assert->pageTextContains('Total $0.00');
    $web_assert->pageTextNotContains('Cash');
    $web_assert->pageTextContains('To Pay $0.00');
    $web_assert->pageTextContains('Change $0.00');
    $web_assert->pageTextNotContains('Jumper');
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

    // Get to the order form.
    $this->drupalPostForm('admin/commerce/pos/main', [], 'Select Register');

    $autocomplete_field = $this->getSession()
      ->getPage()
      ->findField('order_items[target_id][product_selector]');
    $this->assertEquals($settings['settings']['placeholder'], $autocomplete_field->getAttribute('placeholder'));

    // Ensure that the auto-complete only returns 1 value.
    $this->drupalGet('admin/commerce/pos/main');
    $autocomplete_field->setValue('T');
    $this->getSession()->getDriver()->keyDown($autocomplete_field->getXpath(), '-');
    $web_assert->waitOnAutocomplete();
    $results = $this->getSession()->getPage()->findAll('css', '.ui-autocomplete li');
    $this->assertCount(1, $results);
  }

  /**
   * Waits for jQuery to become active and animations to complete.
   */
  protected function waitForAjaxToFinish() {
    $condition = "(0 === jQuery.active && 0 === jQuery(':animated').length)";
    $this->assertJsCondition($condition, 10000);
  }

}
