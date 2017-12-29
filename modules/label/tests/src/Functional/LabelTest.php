<?php

namespace Drupal\Tests\commerce_pos_label\Functional;

use Drupal\commerce_price\Price;
use Drupal\FunctionalJavascriptTests\JavascriptTestBase;
use Drupal\Tests\commerce_pos\Functional\CommercePosCreateStoreTrait;

/**
 * Tests the Labels something something....
 *
 * @group commerce_pos
 */
class LabelTest extends JavascriptTestBase {
  use CommercePosCreateStoreTrait;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'commerce_pos_label',
  ];

  /**
   * {@inheritdoc}
   */
  protected $adminUser;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->adminUser = $this->drupalCreateUser($this->getAdministratorPermissions());
    $this->drupalLogin($this->adminUser);
  }

  /**
   * {@inheritdoc}
   */
  protected function getAdministratorPermissions() {
    return [
      'view the administration theme',
      'access administration pages',
      'access commerce administration pages',
      'commerce pos print labels',
    ];
  }

  public function testLabel() {
    $web_assert = $this->assertSession();

    //Test that the main listing page exists
    $this->drupalGet('admin/commerce/pos/labels');
    $web_assert->pageTextContains(t('Label format'));
    $web_assert->pageTextContains(t('Quantity'));

    $test_store = $this->createStore('POS test store', 'pos_test_store@example.com', 'physical');
    $variation = $this->createProductionVariation(['title' => 'T-shirt XL', 'price' => new Price("23.20", 'USD')]);
    $this->createProduct(['variations' => [$variation], 'title' => 'T-shirt', 'stores' => [$test_store]]);

    $autocomplete_field = $this->getSession()->getPage()->findField('product_search');
    $this->getSession()->getDriver()->keyDown($autocomplete_field->getXpath(), 'T');
    $web_assert->waitOnAutocomplete();
    $results = $this->getSession()->getPage()->findAll('css', '.ui-autocomplete li');
    $web_assert->waitOnAutocomplete();
    $results[0]->click();
    $this->click('#edit-product-search-add');

    $web_assert->responseContains('T-shirt');
    $web_assert->responseContains('23.20');
    $web_assert->addressEquals('admin/commerce/pos/labels');

    // I don't think it is actually possible to test the print functionality,
    // since Mink can't test print dialog as far as I know. So this is as far as the test goes
  }

}
