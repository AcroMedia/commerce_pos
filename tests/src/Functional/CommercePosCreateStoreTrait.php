<?php

namespace Drupal\Tests\commerce_pos\Functional;

use Drupal\commerce_pos\Entity\Register;
use Drupal\commerce_price\Price;
use Drupal\commerce_product\Entity\Product;
use Drupal\commerce_product\Entity\ProductAttribute;
use Drupal\commerce_product\Entity\ProductAttributeValue;
use Drupal\commerce_product\Entity\ProductVariation;
use Drupal\commerce_store\StoreCreationTrait;
use Drupal\search_api\Entity\Index;
use Drupal\Tests\RandomGeneratorTrait;

/**
 * Helpers methods for tests to setup a store for commerce_pos testing.
 *
 * @var register
 */
trait CommercePosCreateStoreTrait {
  use RandomGeneratorTrait;
  use StoreCreationTrait;

  /**
   * @var \Drupal\commerce_store\Entity\StoreInterface
   */
  protected $store;

  /**
   * @var \Drupal\commerce_pos\Entity\RegisterInterface
   */
  protected $register;

  /**
   * Creates a ProductVariation entity.
   *
   * @param array $values
   *   Values to supply ProductVariation::create().
   *
   * @return \Drupal\commerce_product\Entity\ProductVariation
   *   The ProductVariation entity.
   */
  protected function createProductionVariation(array $values = []) {
    $variation = ProductVariation::create($values + [
      'type' => 'default',
      'sku' => strtolower($this->randomMachineName()),
      'status' => 1,
      'price' => new Price(mt_rand(10, 100), 'USD'),
    ]);
    $variation->save();
    return $variation;
  }

  /**
   * Creates a Product entity.
   *
   * @param array $values
   *   Values to supply Product::create().
   *
   * @return \Drupal\commerce_product\Entity\Product
   *   The Product entity.
   */
  protected function createProduct(array $values = []) {
    $product = Product::create($values + [
      'type' => 'default',
      'title' => $this->randomMachineName(),
    ]);
    $product->save();
    return $product;
  }

  /**
   * Creates a store with some products.
   *
   * @return \Drupal\commerce_store\Entity\StoreInterface
   *   The store.
   */
  protected function setUpStore() {
    // Initial store set up.
    $test_store = $this->createStore('POS test store', 'pos_test_store@example.com', 'physical');

    $register = Register::create([
      'store_id' => $test_store->id(),
      'name' => 'Test register',
      'default_float' => new Price('100.00', 'USD'),
    ]);
    $register->save();

    $attribute = ProductAttribute::create([
      'id' => 'size',
      'label' => 'Size',
    ]);
    $attribute->save();
    $this->container->get('commerce_product.attribute_field_manager')->createField($attribute, 'default');

    $attribute_values = [
      's' => ProductAttributeValue::create([
        'attribute' => $attribute->id(),
        'name' => 'S',
      ]),
      'm' => ProductAttributeValue::create([
        'attribute' => $attribute->id(),
        'name' => 'M',
      ]),
      'l' => ProductAttributeValue::create([
        'attribute' => $attribute->id(),
        'name' => 'L',
      ]),
      'xl' => ProductAttributeValue::create([
        'attribute' => $attribute->id(),
        'name' => 'XL',
      ]),
    ];

    $attribute_values['s']->save();
    $attribute_values['m']->save();
    $attribute_values['l']->save();
    $attribute_values['xl']->save();

    $variations = [
      $this->createProductionVariation([
        'price' => new Price("23.20", 'USD'),
        'attribute_size' => $attribute_values['xl'],
      ]),
      $this->createProductionVariation([
        'attribute_size' => $attribute_values['m'],
      ]),
      $this->createProductionVariation([
        'attribute_size' => $attribute_values['l'],
      ]),
    ];

    $this->createProduct([
      'variations' => $variations,
      'title' => 'T-shirt',
      'stores' => [$test_store],
    ]);

    $variations = [
      $this->createProductionVariation([
        'price' => new Price("50", 'USD'),
        'attribute_size' => $attribute_values['xl'],
      ]),
      $this->createProductionVariation([
        'attribute_size' => $attribute_values['m'],
      ]),
      $this->createProductionVariation([
        'attribute_size' => $attribute_values['l'],
      ]),
    ];

    $this->createProduct([
      'variations' => $variations,
      'title' => 'Jumper',
      'stores' => [$test_store],
    ]);

    $this->store = $test_store;
    $this->register = $register;

    // We need to run the index so these items can be searched.
    $index = Index::load('commerce_pos');
    $index->indexItems();

    return $test_store;
  }

}
