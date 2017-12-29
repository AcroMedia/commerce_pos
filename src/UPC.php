<?php

namespace Drupal\commerce_pos;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\TypedData\Exception\MissingDataException;

/**
 * Get any product variations with the provided UPC.
 */
class UPC {

  /**
   * Entity storage for product variations.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $productVariationStorage;

  /**
   * UPC constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   An entity type manager to get entity storage.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   *   If unable to get the product variation storage.
   */
  public function __construct(EntityTypeManagerInterface $entityTypeManager) {
    $this->productVariationStorage = $entityTypeManager->getStorage('commerce_product_variation');
  }

  /**
   * Look up product variations by UPC.
   *
   * @param string $upc
   *   The UPC to look up.
   *
   * @return \Drupal\Core\Entity\EntityInterface[]
   *   All the product_variations that match the supplied UPC.
   */
  public function lookup($upc) {
    $query = \Drupal::entityQuery('commerce_product_variation')
      ->condition('status', 1)
      ->condition('field_upc', $upc);

    $ids = $query->execute();

    return $this->productVariationStorage->loadMultiple($ids);
  }

  /**
   * Get a product's UPC.
   *
   * @param int $product_id
   *   The ID of a commerce_product_variation entity.
   *
   * @return string|null
   *   The UPC, if there is one.
   */
  public function get($product_id) {
    $upc = NULL;
    /** @var \Drupal\commerce_product\Entity\ProductVariationInterface $product */
    $product = $this->productVariationStorage->load($product_id);
    if ($product->hasField('field_upc')) {
      try {
        /** @var \Drupal\text\Plugin\Field\FieldType\TextItem $upc */
        $upc = $product->get('field_upc')->first();
      }
      catch (MissingDataException $e) {
        return NULL;
      }

      if ($upc) {
        $upc = $upc->getValue()['value'];
      }
    }
    return $upc;
  }

}
