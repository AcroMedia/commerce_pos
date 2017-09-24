<?php

namespace Drupal\commerce_pos;

/**
 * Get any product variations with the provided UPC.
 */
class UPC {

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

    $variations = \Drupal::entityTypeManager()->getStorage('commerce_product_variation')->loadMultiple($ids);

    return $variations;
  }

}
