<?php

/**
 * @file
 * Default template for a POS product search result.
 *
 * Available variables:
 * - $product: The commerce product object.
 * - $image: The product's image.
 * - $stock: Text indicating the stock status.
 * - $sell_price: The product's calculated sell price.
 * - $product_display: A URL to the product's display node.
 */
?>

<div class="commerce-pos-product-display">
  <div class="display-image">
    <?php print $image; ?>
  </div>
  <div class="display-details">
    <div class="title">
      <?php print $product->title; ?>
    </div>
    <div class="sku">
      <?php print $product->sku; ?>
      <?php if ($product_display) { ?>
        <?php print ' - ' . $product_display; ?>
      <?php } ?>
    </div>
  </div>
  <div class="display-stock">
    <?php print $stock; ?>
  </div>
  <div class="display-price">
    <?php print $sell_price; ?>
  </div>
  <div class="display-add">
    <?php print l('+', '', array('fragment' => '#', 'external' => TRUE, 'attributes' => array(
      'class' => array('btn-add'),
      'data-product-sku' => $product->sku,
    ))); ?>
  </div>
</div>
