<?php

/**
 * Common callbacks and functionality for commerce_pos.
 */

/**
 * Callback for the product autocomplete.
 */
function commerce_pos_product_autocomplete() {
  $params = drupal_get_query_parameters();
  $results = array();

  if (!empty($params['term'])) {
    $results = _commerce_pos_product_autocomplete_search($params['term']);
  }

  drupal_json_output($results);
}


/**
 * Searches for products containing the provided keywords.
 */
function _commerce_pos_product_autocomplete_search($term) {
  $products = array();

  $query = 'SELECT product_id FROM {commerce_product}
    WHERE sku = :term
    OR title LIKE :term_like
    ORDER BY title ASC
    LIMIT 5';

  $result = db_query($query, array(
    ':term' => $term,
    ':term_like' => db_like($term) . '%',
  ));

  foreach ($result as $row) {
    if ($data = _commerce_pos_product_autocomplete_build($row->product_id)) {
      $products[$row->product_id] = $data;
    }
  }

  return $products;
}

/**
 * Autocomplete callback for the POS customer textfield.
 *
 * Similar to user_autocomplete() except we also take a look at user emails.
 */
function commerce_pos_user_autocomplete($string  = '') {
  $matches = array();

  if ($string) {
    $or = db_or()
      ->condition('name', db_like($string) . '%', 'LIKE')
      ->condition('mail', db_like($string) . '%', 'LIKE');

    $result = db_select('users')
      ->fields('users', array('name', 'mail'))
      ->condition($or)
      ->range(0, 10)->execute();

    foreach ($result as $user) {
      $matches[$user->name] = check_plain($user->name . ' (' . $user->mail . ')');
    }
  }

  drupal_json_output($matches);
}

/**
 * Builds an array of product data for the product autocomplete.
 */
function _commerce_pos_product_autocomplete_build($product_id) {
  $data = FALSE;

  if ($product = commerce_product_load($product_id)) {
    $stock = NULL;
    $image = NULL;

    // Note: an entity_metadata_wrapper is not used here for the sake of speed.
    if ($stock = field_get_items('commerce_product', $product, 'commerce_stock')) {
      $stock = !empty($stock[0]['value']) ? $stock[0]['value'] : 0;
    }

    if ($variation_images = field_get_items('commerce_product', $product, 'field_variation_images')) {
      $image = !empty($variation_images[0]['uri']) ? file_create_url($variation_images[0]['uri']) : NULL;
    }

    $price = commerce_product_calculate_sell_price($product);
    $price = commerce_currency_format($price['amount'], $price['currency_code']);

    $data = array(
      'title' => $product->title,
      'sku' => $product->sku,
      'price' => $price,
      'stock' => (int) $stock,
      'image' => $image,
    );
  }

  return $data;
}
