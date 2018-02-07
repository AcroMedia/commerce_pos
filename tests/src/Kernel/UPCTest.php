<?php

namespace Drupal\Tests\commerce_pos\Kernel;

use Drupal\Tests\commerce\Kernel\CommerceKernelTestBase;
use Drupal\commerce_product\Entity\ProductVariation;

/**
 * Tests the UPC lookup.
 *
 * @coversDefaultClass \Drupal\commerce_pos\UPC
 * @group commerce_pos
 */
class UPCTest extends CommerceKernelTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'entity_reference_revisions',
    'path',
    'telephone',
    'profile',
    'state_machine',
    'commerce_product',
    'commerce_payment',
    'commerce_order',
    'commerce_pos',
    'commerce_tax',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->installEntitySchema('commerce_product_variation');
    $this->installEntitySchema('commerce_product');
    $this->installEntitySchema('commerce_payment');
    $this->installEntitySchema('commerce_order');
    $this->installConfig(['commerce_product']);
    $this->installConfig(['commerce_order']);
    $this->installConfig(['commerce_pos']);
  }

  /**
   * Test that the UPC lookup service will return the correct value.
   */
  public function testUpcLookup() {
    /** @var \Drupal\commerce_product\Entity\ProductVariationInterface $variation */
    $variation_plain = ProductVariation::create([
      'type' => 'default',
    ]);
    $variation_plain->save();

    /** @var \Drupal\commerce_product\Entity\ProductVariationInterface $variation */
    $variation_upc = ProductVariation::create([
      'type' => 'default',
      'field_upc' => '12345',
    ]);
    $variation_upc->save();

    $upc = $this->container->get('commerce_pos.upc');

    $variations = $upc->lookup('12345');
    // Check that we only get the variation we want, not the one without a UPC.
    foreach ($variations as $variation) {
      $this->assertEquals($variation->get('field_upc')->getValue()[0]['value'], '12345');
    }

    // Check that if we try and load a upc that doesn't exist
    // we don't get anything.
    $variations = $upc->lookup('77777');
    $this->assertEmpty($variations);
  }

}
