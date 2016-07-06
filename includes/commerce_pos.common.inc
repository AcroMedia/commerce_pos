<?php

/**
 * Common callbacks and functionality for commerce_pos.
 */

/**
 * Callback for the product autocomplete.
 */
function commerce_pos_product_autocomplete() {
  $params = drupal_get_query_parameters();
  $products = array();

  if (!empty($params['term'])) {
    $results = commerce_pos_product_search($params['term']);

    foreach ($results as $product_id) {
      if ($data = _commerce_pos_product_autocomplete_build($product_id)) {
        $products[$product_id] = $data;
      }
    }
  }

  drupal_json_output($products);
}

/**
 * Searches for products via a keyword search.
 */
function commerce_pos_product_search($keywords) {
  // First try and perform a search through the Search API.
  if (!($results = commerce_pos_product_search_api_search($keywords))) {
    $results = commerce_pos_product_search_basic($keywords);
  }

  return $results;
}

/**
 * Uses the Search API to perform a product keyword search.
 */
function commerce_pos_product_search_api_search($keywords) {
  $results = array();

  // Check if an index has been selected.
  if ($index_id = variable_get('commerce_pos_search_api_index')) {
    if ($index_id != 'default') {
      $index = search_api_index_load($index_id);
      $query = new SearchApiQuery($index);
      $query->keys($keywords);
      $query->range(0, variable_get('commerce_pos_search_results_count', 5));
      $query_results = $query->execute();

      if (!empty($query_results['results'])) {
        $results = array_keys($query_results['results']);
      }
    }
  }
  return $results;
}

/**
 * Perform a very basic product search via the database.
 */
function commerce_pos_product_search_basic($keywords) {
  $allowed_product_types = CommercePosService::allowedProductTypes();
  $results = array();

  if (!empty($allowed_product_types)) {
    $query = db_select('commerce_product', 'cp');
    $query->fields('cp', array('product_id', 'title', 'type'));
    $or = db_or();
    $or->condition('title', db_like($keywords) . '%', 'LIKE')
      ->condition('sku', $keywords, '=');
    $query->condition($or);
    $query->condition('type', $allowed_product_types, 'IN')
      ->range(0, variable_get('commerce_pos_search_results_count', 5))
      ->orderBy('title', 'ASC');
    $result = $query->execute();

    foreach ($result as $row) {
      $results[] = $row->product_id;
    }
  }

  return $results;
}

/**
 * Autocomplete callback for the POS customer textfield.
 *
 * Similar to user_autocomplete(), but we also take a look at user emails.
 * The JSON result is also keyed by email instead of username.
 */
function commerce_pos_user_autocomplete($string = '') {
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

/**
 * Helper function to build a "Parked Transactions" section.
 */
function commerce_pos_parked_transactions_section($transaction_type, $ajax, $has_active_transaction) {
  global $user;

  $parked_transactions_section = array();

  if ($parked_transactions = CommercePosService::getParkedTransactions($transaction_type, $user->uid)) {
    $parked_transactions_section = array(
      '#type' => 'container',
      '#tree' => TRUE,
      '#attributes' => array(
        'class' => array('parked-transactions-wrapper'),
      ),
    );

    $description = format_plural(count($parked_transactions), '1 Transaction Parked', '@count Transactions Parked') . ' - ';

    $parked_transactions_section['description'] = array(
      '#markup' => $description,
    );

    foreach ($parked_transactions as $transaction_id) {
      if (count($parked_transactions) == 1) {
        $button_text = t('Retrieve');
      }
      else {
        $button_text = t('Retrieve Transaction #@id', array(
          '@id' => $transaction_id,
        ));
      }

      $parked_transactions_section[$transaction_id] = array(
        '#type' => 'button',
        '#value' => $button_text,
        '#attributes' => array(
          'class' => array('commerce-pos-btn-retrieve-transaction'),
        ),
        '#ajax' => $ajax,
        '#name' => 'retrieve-parked-transaction-' . $transaction_id,
        '#transaction_id' => $transaction_id,
        '#disabled' => $has_active_transaction,
        '#element_key' => 'retrieve-parked-transaction',
      );
    }
  }

  return $parked_transactions_section;
}

/**
 * AJAX callback for the customer field.
 */
function commerce_pos_customer_js($form, &$form_state) {
  return $form['customer'];
}

