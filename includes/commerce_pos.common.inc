<?php

/**
 * @file
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

    // Let's return an empty results message back when we get no products.
    if (empty($results)) {
      $products[-1] = _commerce_pos_product_get_empty_results_message();
    }

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
 * Similar to user_autocomplete(), but we also take a look at user emails and
 * phone number.
 * The JSON result is also keyed by email instead of username.
 */
function commerce_pos_user_autocomplete($string = '') {
  $matches = array();

  if ($string) {
    $or = db_or()
      ->condition('name', db_like($string) . '%', 'LIKE')
      ->condition('mail', db_like($string) . '%', 'LIKE')
      ->condition('commerce_pos_phone_number_value', db_like($string) . '%', 'LIKE');

    $query = db_select('users', 'u');
    $query->leftjoin('field_data_commerce_pos_phone_number', 'f', 'f.entity_id = u.uid');
    $result = $query
      ->fields('u', array('name', 'mail'))
      ->fields('f', array('commerce_pos_phone_number_value'))
      ->condition($or)
      ->range(0, 10)->execute();

    $matches = commerce_pos_format_autocomplete_results($result);
  }

  drupal_json_output($matches);
}

/**
 * Formats auto complete search results as a string.
 */
function commerce_pos_format_autocomplete_results($result) {
  $matches = array();
  if ($result) {
    foreach ($result as $row) {
      if (empty($row->commerce_pos_phone_number_value)) {
        $matches[$row->mail] = check_plain($row->name . ' (' . $row->mail . ')');
      }
      else {
        $matches[$row->mail] = check_plain($row->name . ' (' . $row->mail . ')' . ' (' . $row->commerce_pos_phone_number_value . ')');
      }
    }
  }
  return $matches;
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

/**
 * Add a container for a modal dialogue to a form.
 *
 * Add to the 'modal' key to add to the body of the modal.
 *
 * @code
 * $form['some_prompt'] = commerce_pos_form_modal();
 * $form['some_prompt']['modal']['my_text'] = array(
 *   '#markup' => t('This text is in the body of a modal.'),
 * );
 * @endcode
 *
 * If making the modal uncloseable, you should provide your own method of
 * closing the modal.
 *
 * @param array $options
 *   An array of options for the modal:
 *   - closeable (bool): Defaults to TRUE. Set to FALSE to prevent the user from
 *     closing the modal.
 *   - throbber (bool): Defaults to FALSE. Set to TRUE to display a throbber on
 *     the modal.
 *
 * @return array
 *   The Form API elements to create the modal. The 'modal' key of the array is
 *   the body of the modal.
 */
function commerce_pos_form_modal(array $options = array()) {
  $options += array(
    'closeable' => TRUE,
    'throbber' => FALSE,
  );

  $modal = array(
    '#type' => 'container',
    '#attributes' => array(
      'id' => 'commerce-pos-form-modal-overlay',
      'class' => array('commerce-pos-form-modal-overlay'),
    ),
  );

  $modal['modal'] = array(
    '#type' => 'container',
    '#attributes' => array(
      'id' => 'commerce-pos-form-modal-modal',
      'class' => array('commerce-pos-form-modal-modal'),
    ),
  );

  if ($options['closeable']) {
    $modal['modal']['close'] = array(
      '#markup' => '<div id="commerce-pos-form-modal-close" class="commerce-pos-form-modal-close">X</div>',
    );
  }

  if ($options['throbber']) {
    $throbber_source = drupal_get_path('module', 'commerce_pos') . '/images/loader.gif';
    $modal['modal']['throbber'] = array(
      '#markup' => '<div class="commerce-pos-form-modal-throbber"><img src="/' . $throbber_source . '" /></div>',
    );
  }

  return $modal;
}
