<?php

namespace Drupal\Tests\commerce_pos\Functional;

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

}
