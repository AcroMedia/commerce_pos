<?php

namespace Drupal\Tests\commerce_pos_keypad\FunctionalJavascript;

use Drupal\FunctionalJavascriptTests\JavascriptTestBase;

/**
 * Tests the Commerce POS Keypad form.
 *
 * @group commerce_pos_keypad
 */
class KeypadTest extends JavascriptTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'commerce_pos_keypad_test',
  ];

  /**
   * Tests a keypad added to a form the same way as PosForm.
   */
  public function testCommercePosForm() {
    $web_assert = $this->assertSession();
    $this->drupalGet('commerce_pos_keypad_pos_test');
    $web_assert->fieldValueEquals('keypad[amount]', '100');
    $this->getSession()->getPage()->find('xpath', '//*[@id="commerce-pos-sale-keypad-wrapper"]/div/div[2]/div[1]/div[1]');
    // When the first character is typed, the input is automatically cleared, like placeholder text.
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

    // TODO Uncomment once popup and close functionality are added back in, right now keypad is always inline
    // $this->click('.commerce-pos-keypad-close');.
  }

}
