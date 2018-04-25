<?php

namespace Drupal\Tests\commerce_pos_reports\FunctionalJavascript;

use Drupal\commerce_price\Entity\Currency;
use Drupal\FunctionalJavascriptTests\JavascriptTestBase;
use Drupal\commerce_order\Entity\Order;
use Drupal\commerce_payment\Entity\Payment;
use Drupal\commerce_price\Price;
use Drupal\commerce_order\Entity\OrderItem;
use Drupal\commerce_product\Entity\ProductVariation;
use Drupal\Tests\commerce_pos\Functional\CommercePosCreateStoreTrait;

/**
 * Tests the Commerce EOD Report form.
 *
 * @group commerce_pos_reports
 */
class EndOfDayTest extends JavascriptTestBase {
  use CommercePosCreateStoreTrait;

  /**
   * {@inheritdoc}
   */
  protected $adminUser;

  /**
   * {@inheritdoc}
   */
  protected $nonPriviledgedUser;

  /**
   * {@inheritdoc}
   */
  protected $store;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'search_api_db',
    'commerce_price',
    'commerce_pos',
    'commerce_pos_reports',
    'commerce_store',
    'commerce_price',
    'commerce_pos_reports',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->setUpStore();

    $this->adminUser = $this->drupalCreateUser($this->getAdministratorPermissions());
    $this->nonPriviledgedUser = $this->drupalCreateUser([]);

    $this->register->open();
    $this->register->setOpeningFloat($this->register->getDefaultFloat());
    $this->register->save();
  }

  /**
   * {@inheritdoc}
   */
  protected function getAdministratorPermissions() {
    return [
      'view the administration theme',
      'access administration pages',
      'access commerce administration pages',
      'administer commerce_currency',
      'administer commerce_store',
      'administer commerce_store_type',
      'access commerce pos administration pages',
      'access commerce pos reports',
    ];
  }

  /**
   * Tests that all the menu hooks return pages for priviledged users.
   */
  public function testCommercePosReportsEndOfDayMenu() {
    $web_assert = $this->assertSession();

    // Confirm priviledged user can access the report pages.
    $this->drupalLogin($this->adminUser);
    $this->drupalGet('admin/commerce/pos/reports');
    $web_assert->statusCodeEquals(200);

    // Confirm unpriviledged user cannot access the report pages.
    $this->drupalLogin($this->nonPriviledgedUser);
    $this->drupalGet('admin/commerce/pos/reports');
    $web_assert->statusCodeEquals(403);
  }

  /**
   * Tests that the end of day report runs and has correct values.
   */
  public function testCommercePosReportsEndOfDayForm() {
    $web_assert = $this->assertSession();

    $this->drupalLogin($this->adminUser);

    \Drupal::service('commerce_pos.current_register')->set($this->register);

    // Let's create some sales transactions.
    $transaction_summary = $this->generateTransactions();

    // Now, go to the EOD reports form and verify the values.
    $this->drupalGet('admin/commerce/pos/reports/end-of-day');
    $web_assert->statusCodeEquals(200);

    $this->getSession()->getPage()->fillField('register_id', $this->register->id());
    $this->waitForAjaxToFinish();

    // Check the EOD report's expected amounts to make sure they match up with
    // the transactions we generated.
    foreach ($transaction_summary as $payment_method => $totals) {
      $expected_amount_element = $this->xpath('(//div[@class="commerce-pos-report-expected-amount" and @data-payment-method-id="' . $payment_method . '"])[1]/text()');

      // Casting the xpath element to a string gets us the element's inner HTML.
      $expected_amount = (string) $expected_amount_element[0]->getText();

      $this->assertSame($expected_amount, $totals['amount_total_formatted'], FALSE, 'Expected amount for' . $payment_method . ' is correct.');
    }
  }

  /**
   * Generates POS transactions and payments.
   *
   * @return array
   *   An associative array of generated totals, keyed by the payment method.
   *
   *   array(
   *     'pos_cash' => ['total_amount' => 550],
   *     'pos_credit' => ['total_amount' => 450.50],
   *   );
   */
  private function generateTransactions() {
    // Initialize the pos_cash with the register float value.
    $transaction_summary = [
      'pos_cash' => [
        'amount_total' => $this->register->getOpeningFloat()->getNumber(),
      ],
    ];

    $payment_methods = [
      ['pos_cash' => '55.99'],
      ['pos_cash' => '19.99'],
      ['pos_credit' => '15.99'],
      ['pos_debit' => '35.99'],
      ['pos_gift_card' => '16.99'],
    ];
    $currency_code = 'USD';
    $number_formatter_factory = \Drupal::service('commerce_price.number_formatter_factory');
    $number_formatter = $number_formatter_factory->createInstance();
    $currency = Currency::load($currency_code);

    foreach ($payment_methods as $payment_method) {
      $payment_method_id = key($payment_method);
      $amount = $payment_method[$payment_method_id];

      /** @var \Drupal\commerce_product\Entity\Product $variation */
      $variation = ProductVariation::create([
        'type' => 'default',
        'sku' => 'prod-test',
        'title' => 'Test Product',
        'status' => 1,
        'price' => new Price($amount, $currency_code),
      ]);
      $variation->save();

      /** @var \Drupal\commerce_order\Entity\OrderItem $order_item */
      $order_item = OrderItem::create([
        'type' => 'default',
        'quantity' => 1,
        'unit_price' => new Price($amount, $currency_code),
        'purchasable_entity' => $variation,
      ]);

      /* @var \Drupal\commerce_order\Entity\Order $order */
      $order = Order::create([
        'type' => 'pos',
        'state' => 'completed',
        'store_id' => $this->store->id(),
        'field_cashier' => \Drupal::currentUser()->id(),
        'field_register' => $this->register->id(),
        'order_items' => [$order_item],
      ]);
      $order->save();

      /** @var \Drupal\commerce_payment\Entity\PaymentInterface $payment */
      $payment = Payment::create([
        'type' => 'payment_manual',
        'payment_gateway' => $payment_method_id,
        'order_id' => $order,
        'amount' => new Price($amount, $currency_code),
        'state' => 'completed',
      ]);
      $payment->save();

      $amount_total = isset($transaction_summary[$payment_method_id]['amount_total']) ? $transaction_summary[$payment_method_id]['amount_total'] + (float) $amount : (float) $amount;
      $transaction_summary[$payment_method_id]['amount_total'] = $amount_total;
      $transaction_summary[$payment_method_id]['amount_total_formatted'] = $number_formatter->formatCurrency($amount_total, $currency);
      $transaction_summary[$payment_method_id]['orders'][$order->id()] = $payment;
    }

    return $transaction_summary;
  }

  /**
   * Waits for jQuery to become active and animations to complete.
   */
  protected function waitForAjaxToFinish() {
    $condition = "(0 === jQuery.active && 0 === jQuery(':animated').length)";
    $this->assertJsCondition($condition, 10000);
  }

}
