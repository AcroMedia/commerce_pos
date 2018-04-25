<?php

namespace Drupal\Tests\commerce_pos\FunctionalJavascript;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\FunctionalJavascriptTests\JavascriptTestBase;
use Drupal\Tests\commerce_pos\Functional\CommercePosCreateStoreTrait;

/**
 * Tests the Commerce POS form.
 *
 * @group commerce_pos
 */
class PosFormWithPromotionTest extends JavascriptTestBase {
  use CommercePosCreateStoreTrait;

  /**
   * The test promotion.
   *
   * @var \Drupal\commerce_promotion\Entity\PromotionInterface
   */
  protected $promotion;

  /**
   * The test promotion.
   *
   * @var \Drupal\commerce_promotion\Entity\CouponInterface
   */
  protected $coupon;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'search_api_db',
    'commerce_pos',
    'commerce_promotion',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->setUpStore();

    // Create a promotion with a coupon.
    $this->promotion = $this->createEntity('commerce_promotion', [
      'name' => 'Promotion test',
      'order_types' => ['pos'],
      'stores' => [$this->store->id()],
      'offer' => [
        'target_plugin_id' => 'order_percentage_off',
        'target_plugin_configuration' => [
          'amount' => '0.10',
        ],
      ],
      'start_date' => '2017-01-01',
      'status' => TRUE,
    ]);
    $this->coupon = $this->createEntity('commerce_promotion_coupon', [
      'promotion_id' => $this->promotion->id(),
      'code' => $this->randomMachineName(8),
      'status' => TRUE,
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

    // Add a coupon.
    $this->htmlOutput($this->getRawContent());
    $this->getSession()->getPage()->fillField('coupons[0][target_id]', $this->coupon->getCode());

    // Go to the payment page.
    $this->getSession()->getPage()->findButton('Payments and Completion')->click();
    $this->htmlOutput($this->getRawContent());
    $this->click('input[name="commerce-pos-pay-keypad-add"]');
    $web_assert->pageTextContains('Subtotal $50.00');
    $web_assert->pageTextContains('Discount -$5.00');
    $web_assert->pageTextContains('Cash $45.00');
    $web_assert->pageTextContains('Total $45.00');
    $web_assert->pageTextContains('Total Paid $45.00');
    $web_assert->pageTextContains('Change $0.00');
    $web_assert->pageTextContains('To Pay $0.00');
  }

  /**
   * Creates a new entity.
   *
   * @param string $entity_type
   *   The entity type to be created.
   * @param array $values
   *   An array of settings.
   *   Example: 'id' => 'foo'.
   *
   * @return \Drupal\Core\Entity\EntityInterface
   *   A new entity.
   */
  protected function createEntity($entity_type, array $values) {
    /** @var \Drupal\Core\Entity\EntityStorageInterface $storage */
    $storage = \Drupal::service('entity_type.manager')->getStorage($entity_type);
    $entity = $storage->create($values);
    $status = $entity->save();
    $this->assertEquals(SAVED_NEW, $status, new FormattableMarkup('Created %label entity %type.', [
      '%label' => $entity->getEntityType()->getLabel(),
      '%type' => $entity->id(),
    ]));
    // The newly saved entity isn't identical to a loaded one, and would fail
    // comparisons.
    $entity = $storage->load($entity->id());

    return $entity;
  }

}
