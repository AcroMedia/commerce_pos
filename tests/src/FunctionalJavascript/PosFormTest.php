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
    // @todo commerce_pos has a circular dependency on commerce_pos_keypad
    'commerce_pos_keypad',
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
      $this->createProductionVariation(['title' => 'T-shirt XL', 'price' => new Price("23.20", 'USD')]),
      $this->createProductionVariation(['title' => 'T-shirt L']),
      $this->createProductionVariation(['title' => 'T-shirt M']),
    ];

    $this->createProduct(['variations' => $variations, 'title' => 'T-shirt', 'stores' => [$test_store]]);

    $variations = [
      $this->createProductionVariation(['title' => 'Jumper XL', 'price' => new Price("50", 'USD')]),
      $this->createProductionVariation(['title' => 'Jumper L']),
      $this->createProductionVariation(['title' => 'Jumper M']),
    ];

    $this->createProduct(['variations' => $variations, 'title' => 'Jumper', 'stores' => [$test_store]]);

    // @todo work out the expected permissions to view products etc...
    $this->drupalLogin($this->rootUser);
  }

  /**
   * Tests adding and removing products from the POS form.
   */
  public function testCommercePosForm() {
    $web_assert = $this->assertSession();
    $this->drupalGet('admin/commerce/pos');
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
    // @todo Once Once https://www.drupal.org/project/commerce/issues/2923388 is
    //   fixed, uncomment.
    // $web_assert->fieldValueEquals('order_items[target_id][order_items][0][unit_price][number]', '50.00');
    $web_assert->pageTextContains('Remaining Balance $50.00');

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
    // @todo Once Once https://www.drupal.org/project/commerce/issues/2923388 is
    //   fixed, uncomment.
    // $web_assert->fieldValueEquals('order_items[target_id][order_items][0][unit_price][number]', '50.00');
    $web_assert->pageTextContains('Remaining Balance $100.00');

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
    // @todo Once Once https://www.drupal.org/project/commerce/issues/2923388 is
    //   fixed, uncomment.
    // $web_assert->fieldValueEquals('order_items[target_id][order_items][1][unit_price][number]', '23.20');
    $web_assert->pageTextContains('Remaining Balance $123.20');

    // Click on the buttons to add another Jumper.
    $this->getSession()->getPage()->fillField('order_items[target_id][order_items][0][quantity]', '3');
    $web_assert->assertWaitOnAjaxRequest();
    $web_assert->pageTextContains('Jumper');
    $web_assert->fieldValueEquals('order_items[target_id][order_items][0][quantity]', '3.00');
    // @todo Once Once https://www.drupal.org/project/commerce/issues/2923388 is
    //   fixed, uncomment.
    // $web_assert->fieldValueEquals('order_items[target_id][order_items][0][unit_price][number]', '50.00');
    $web_assert->pageTextContains('Remaining Balance $173.20');

    // Change the price of jumpers on the form.
    // @todo Once https://www.drupal.org/project/commerce/issues/2923388 is
    //   fixed, add test back.
    // $this->getSession()->getPage()->fillField('order_items[target_id][order_items][0][unit_price][number]', '40.50');
    // $web_assert->assertWaitOnAjaxRequest();
    // $web_assert->fieldValueEquals('order_items[target_id][order_items][0][unit_price][number]', '40.50');
    // // (3 * 40.5) + (1 * 23.20)
    // $web_assert->pageTextContains('Remaining Balance $144.70');

    // Click on the buttons to remove all the jumpers.
    $this->getSession()->getPage()->findButton('remove_order_item_1')->click();
    $web_assert->assertWaitOnAjaxRequest();
    $web_assert->pageTextNotContains('Jumper');
    $web_assert->pageTextContains('T-Shirt');
    // @todo Once Once https://www.drupal.org/project/commerce/issues/2923388 is
    //   fixed, uncomment.
    // $web_assert->fieldValueEquals('order_items[target_id][order_items][0][unit_price][number]', '23.20');
    $web_assert->pageTextContains('Remaining Balance $23.20');

    // Set the quantity of t-shirts using the quantity form field.
    $this->getSession()->getPage()->fillField('order_items[target_id][order_items][0][quantity]', '10');
    $web_assert->assertWaitOnAjaxRequest();
    $web_assert->pageTextContains('T-Shirt');
    // @todo Once Once https://www.drupal.org/project/commerce/issues/2923388 is
    //   fixed, uncomment.
    // $web_assert->fieldValueEquals('order_items[target_id][order_items][0][unit_price][number]', '23.20');
    $web_assert->fieldValueEquals('order_items[target_id][order_items][0][quantity]', '10.00');
    $web_assert->pageTextContains('Remaining Balance $232.00');

    // Set the quantity to 0 to remove the T-Shirt.
    $this->getSession()->getPage()->fillField('order_items[target_id][order_items][0][quantity]', '0');
    $web_assert->assertWaitOnAjaxRequest();
    $web_assert->pageTextNotContains('T-Shirt');
    $web_assert->pageTextContains('Remaining Balance $0.00');
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
    $this->drupalPostForm('admin/commerce/pos', [], 'Select Register');

    $autocomplete_field = $this->getSession()
      ->getPage()
      ->findField('order_items[target_id][product_selector]');
    $this->assertEquals($settings['settings']['placeholder'], $autocomplete_field->getAttribute('placeholder'));

    // Ensure that the auto-complete only returns 1 value.
    $this->drupalGet('admin/commerce/pos');
    $autocomplete_field->setValue('T');
    $this->getSession()->getDriver()->keyDown($autocomplete_field->getXpath(), '-');
    $web_assert->waitOnAutocomplete();
    $results = $this->getSession()->getPage()->findAll('css', '.ui-autocomplete li');
    $this->assertCount(1, $results);
  }

}
