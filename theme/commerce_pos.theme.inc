<?php

/**
 * @file
 * Theme callbacks for commerce_pos.
 */


/**
 * Implements template_preprocess_commerce_pos_product_result().
 *
 * Adds additional variables to the template.
 */
function commerce_pos_preprocess_commerce_pos_product_result(&$variables) {
  $product = $variables['product'];

  $variables['image'] = NULL;
  $stock = NULL;

  if (module_exists('commerce_stock') && $stock = field_get_items('commerce_product', $product, 'commerce_stock')) {
    $stock = !empty($stock[0]['value']) ? (int) $stock[0]['value'] : 0;
  }

  $price = commerce_product_calculate_sell_price($product);
  $variables['sell_price'] = commerce_currency_format($price['amount'], $price['currency_code']);

  // Check for an image.
  if ($image_field_name = variable_get('commerce_pos_image_field_' . $product->type, FALSE)) {
    if ($product_images = field_get_items('commerce_product', $product, $image_field_name)) {
      if (!empty($product_images[0]['uri'])) {
        $variables['image'] = theme('image_style', array(
          'style_name' => 'commerce_pos_thumbnail',
          'path' => $product_images[0]['uri'],
          'alt' => $product->title,
        ));
      }
    }
  }

  if ($display_nid = commerce_pos_get_product_display_nid($product->product_id)) {
    $variables['product_display'] = url('node/' . $display_nid);
  }
  else {
    $variables['product_display'] = NULL;
  }

  $variables['stock'] = $stock !== NULL ? t('@count in stock', array('@count' => $stock)) : t('n/a');
}
