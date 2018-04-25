<?php

namespace Drupal\Tests\commerce_pos_keypad\FunctionalJavascript;

use Drupal\FunctionalJavascriptTests\JavascriptTestBase;
use Drupal\Tests\commerce_pos\Functional\CommercePosCreateStoreTrait;

/**
 * Tests the Commerce POS Keypad form.
 *
 * @group commerce_pos_keypad
 */
class KeypadTest extends JavascriptTestBase {
  use CommercePosCreateStoreTrait;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'search_api_db',
    'commerce_pos_keypad_test',
    'commerce_pos',
    'commerce_pos_keypad',
    'commerce_pos_reports',
    'commerce_pos_currency_denominations',
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
   * Tests a keypad added to a form the same way as PosForm.
   */
  public function testCommercePosForm() {
    $web_assert = $this->assertSession();
    $this->drupalGet('commerce_pos_keypad_pos_test');
    $web_assert->fieldValueEquals('keypad[amount]', '100');
    $this->getSession()->getPage()->find('xpath', '//*[@id="commerce-pos-sale-keypad-wrapper"]/div/div[2]/div[1]/div[1]');
    // When the first character is typed, the input is automatically cleared,
    // like placeholder text.
    $this->xpath('//div[@class="commerce-pos-keypad-key" and @data-keybind="7"]')[0]->click();
    $web_assert->fieldValueEquals('keypad[amount]', '7');
    $this->xpath('//div[@class="commerce-pos-keypad-key" and @data-keybind="0"]')[0]->click();
    $web_assert->fieldValueEquals('keypad[amount]', '70');
    $this->xpath('//div[@class="commerce-pos-keypad-key" and @data-key-action="backspace"]')[0]->click();
    $web_assert->fieldValueEquals('keypad[amount]', '7');
    $this->xpath('//div[@class="commerce-pos-keypad-key clear-key" and @data-key-action="clear"]')[0]->click();
    $web_assert->fieldValueEquals('keypad[amount]', '');
    $this->xpath('//div[@class="commerce-pos-keypad-key" and @data-keybind="1"]')[0]->click();
    $this->xpath('//div[@class="commerce-pos-keypad-key" and @data-keybind="000"]')[0]->click();
    $web_assert->fieldValueEquals('keypad[amount]', '1000');
    $this->xpath('//div[@class="commerce-pos-keypad-key" and @data-keybind="."]')[0]->click();
    $this->xpath('//div[@class="commerce-pos-keypad-key" and @data-keybind="5"]')[0]->click();
    $this->xpath('//div[@class="commerce-pos-keypad-key" and @data-keybind="2"]')[0]->click();
    $web_assert->fieldValueEquals('keypad[amount]', '1000.52');
  }

  /**
   * Tests a keypad added to text element.
   */
  public function testCommerceTextForm() {
    $web_assert = $this->assertSession();
    $this->drupalGet('commerce_pos_keypad_text_test');
    $this->click('.commerce-pos-keypad-keypad-icon');
    // Press all of the buttons.
    $this->xpath('//div[@class="commerce-pos-keypad-key" and @data-keybind="1"]')[0]->click();
    $this->xpath('//div[@class="commerce-pos-keypad-key" and @data-keybind="2"]')[0]->click();
    $this->xpath('//div[@class="commerce-pos-keypad-key" and @data-keybind="3"]')[0]->click();
    $this->xpath('//div[@class="commerce-pos-keypad-key" and @data-keybind="4"]')[0]->click();
    $this->xpath('//div[@class="commerce-pos-keypad-key" and @data-keybind="5"]')[0]->click();
    $this->xpath('//div[@class="commerce-pos-keypad-key" and @data-keybind="6"]')[0]->click();
    $this->xpath('//div[@class="commerce-pos-keypad-key" and @data-keybind="7"]')[0]->click();
    $this->xpath('//div[@class="commerce-pos-keypad-key" and @data-keybind="8"]')[0]->click();
    $this->xpath('//div[@class="commerce-pos-keypad-key" and @data-keybind="9"]')[0]->click();
    $this->xpath('//div[@class="commerce-pos-keypad-key" and @data-keybind="0"]')[0]->click();
    $this->xpath('//div[@class="commerce-pos-keypad-key" and @data-keybind="."]')[0]->click();
    $this->xpath('//div[@class="commerce-pos-keypad-key" and @data-keybind="1"]')[0]->click();
    // This is the delete button.
    $this->xpath('//div[@class="commerce-pos-keypad-key" and @data-key-action="backspace"]')[0]->click();
    $web_assert->fieldValueEquals('cashier_id', '1234567890.');
    $this->xpath('//div[@class="commerce-pos-keypad-key" and @data-keybind="1"]')[0]->click();

    // TODO Uncomment once popup and close functionality are added back in,
    // right now keypad is always inline
    // $this->click('.commerce-pos-keypad-close');.
  }

  /**
   * Tests a keypad input box added to text element.
   *
   * @todo Figure out why this is failing.
   */
  public function testInputBoxForm() {
    $this->markTestSkipped('Figure out why this is failing');
    $web_assert = $this->assertSession();
    $this->drupalGet('admin/commerce/pos/main');

    $this->getSession()->getPage()->fillField('register', '1');
    $this->getSession()->getPage()->fillField('float[number]', '10.00');
    $this->getSession()->getPage()->findButton('Open Register')->click();

    $this->drupalGet('commerce_pos_keypad_input_box_test');
    $this->getSession()->getDriver()->wait(1000, 'jQuery(".commerce-pos-keypad-cash-input-icon").length > 0');
    $this->click('.commerce-pos-keypad-cash-input-icon');

    // Set values in all the denomination fields.
    $this->xpath('//*[@id="commerce-pos-keypad-cash-input-box"]/div/div/div/table/tbody/tr[0]/td[2]/input')[0]->setValue(40);
    $this->xpath('//*[@id="commerce-pos-keypad-cash-input-box"]/div/div/div/table/tbody/tr[1]/td[2]/input')[0]->setValue(30);
    $this->xpath('//*[@id="commerce-pos-keypad-cash-input-box"]/div/div/div/table/tbody/tr[2]/td[2]/input')[0]->setValue(20);
    $this->xpath('//*[@id="commerce-pos-keypad-cash-input-box"]/div/div/div/table/tbody/tr[3]/td[2]/input')[0]->setValue(10);
    $this->xpath('//*[@id="commerce-pos-keypad-cash-input-box"]/div/div/div/table/tbody/tr[4]/td[2]/input')[0]->setValue(5);
    $this->xpath('//*[@id="commerce-pos-keypad-cash-input-box"]/div/div/div/table/tbody/tr[5]/td[2]/input')[0]->setValue(4);
    $this->xpath('//*[@id="commerce-pos-keypad-cash-input-box"]/div/div/div/table/tbody/tr[6]/td[2]/input')[0]->setValue(3);
    $this->xpath('//*[@id="commerce-pos-keypad-cash-input-box"]/div/div/div/table/tbody/tr[7]/td[2]/input')[0]->setValue(2);
    $this->xpath('//*[@id="commerce-pos-keypad-cash-input-box"]/div/div/div/table/tbody/tr[8]/td[2]/input')[0]->setValue(1);

    // Confirm the total value.
    $total_row = $this->xpath('//*[@id="commerce-pos-keypad-cash-input-box"]/div/div/div/table/tbody/tr[9]/td[2]/span')[0];
    $this->assertTrue($total_row->getText() == '34500');

    // Now, click the 'Add' button to add up all the values.
    $this->xpath('//*[@id="commerce-pos-keypad-cash-input-box"]/div/div/div/table/tbody/tr[10]/td[2]/a')[0]->click();

    // Make sure the correct value has been inserted into the amount field.
    $web_assert->fieldValueEquals('amount', '345');
  }

}
