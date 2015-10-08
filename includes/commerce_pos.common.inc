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
 * Similar to user_autocomplete(), but we also take a look at user emails.
 * The JSON result is also keyed by email instead of username.
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
      $matches[$user->mail] = check_plain($user->name . ' (' . $user->mail . ')');
    }
  }

  drupal_json_output($matches);
}
