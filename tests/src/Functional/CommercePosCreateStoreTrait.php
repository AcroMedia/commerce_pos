<?php

namespace Drupal\Tests\commerce_pos\Functional;

use Drupal\commerce_pos\Entity\Register;
use Drupal\commerce_price\Price;
use Drupal\commerce_product\Entity\Product;
use Drupal\commerce_product\Entity\ProductVariation;
use Drupal\commerce_store\StoreCreationTrait;
use Drupal\Tests\RandomGeneratorTrait;

/**
 * Helpers methods for tests to setup a store for commerce_pos testing.
 */
trait CommercePosCreateStoreTrait {
  use RandomGeneratorTrait;
  use StoreCreationTrait;

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
      'title' => $this->randomMachineName(),
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

    return $test_store;
  }

}
