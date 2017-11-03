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

  if (module_exists('commerce_stock') && $stock_field = field_get_items('commerce_product', $product, 'commerce_stock')) {
    $stock = !empty($stock_field[0]['value']) ? (int) $stock_field[0]['value'] : 0;
  }

  $price = commerce_product_calculate_sell_price($product);
  $variables['sell_price'] = commerce_currency_format($price['amount'], $price['currency_code']);

  // Check for an image.
  if ($image = _commerce_pos_product_thumbnail($product)) {
    $variables['image'] = $image;
  }

  if ($display_nid = commerce_pos_get_product_display_nid($product->product_id)) {
    $variables['product_display'] = url('node/' . $display_nid);
  }
  else {
    $variables['product_display'] = NULL;
  }

  $variables['stock'] = $stock !== NULL ? t('@count in stock', array('@count' => $stock)) : t('stock unknown');
}

/**
 * Implements hook_preprocess_HOOK().
 */
function commerce_pos_preprocess_commerce_pos_header(&$variables) {
  $variables['links'] = array();

  $links = array(
    'admin/commerce/pos/transaction' => t('Transaction'),
  );

  drupal_alter('commerce_pos_header_links', $links);

  foreach ($links as $path => $title) {
    $link_attributes = array();

    if (commerce_pos_in_active_trail($path)) {
      $link_attributes['class'][] = 'active';
    }

    $variables['links'][] = l($title, $path, array('attributes' => $link_attributes));
  }
}

/**
 * Theme callback for the POS product summary.
 */
function theme_commerce_pos_product_summary(&$variables) {
  $order_wrapper = entity_metadata_wrapper('commerce_order', $variables['order']);
  $rows = array();

  foreach ($order_wrapper->commerce_line_items as $line_item_wrapper) {
    if (($line_item_wrapper->getBundle() == 'product' || $line_item_wrapper->getBundle() == 'commerce_pos_return')
      && isset($line_item_wrapper->commerce_product)
      && $line_item_wrapper->commerce_product->getIdentifier()
    ) {

      $unit_price = $line_item_wrapper->commerce_unit_price->value();
      $total = $line_item_wrapper->commerce_total->value();

      $display_price = commerce_pos_commerce_currency_format($unit_price['amount'], $unit_price['currency_code']);
      $display_total = commerce_pos_commerce_currency_format($total['amount'], $unit_price['currency_code']);

      $description = '<span class="title">' . $line_item_wrapper->commerce_product->title->value(array('sanitize' => TRUE)) . '</span>';
      $description .= '<span class="sku">#' . $line_item_wrapper->commerce_product->sku->value(array('sanitize' => TRUE)) . '</span>';

      $image = _commerce_pos_product_thumbnail($line_item_wrapper->commerce_product->value());

      $row = array(
        'data' => array(
          array(
            'data' => (int) $line_item_wrapper->quantity->raw(),
            'class' => array('qty'),
          ),
          array(
            'data' => $image,
            'class' => array('thumbnail'),
          ),
          array(
            'data' => $description,
            'class' => array('title'),
          ),
          array(
            'data' => t('@ @price', array('@price' => $display_price)),
            'class' => array('base-price'),
          ),
          array(
            'data' => $display_total,
            'class' => array('total'),
          ),
        ),
      );

      $rows[] = $row;
    }
  }

  $attributes = array(
    'class' => array('commerce-pos-product-summary'),
  );

  $attributes = drupal_array_merge_deep($attributes, $variables['attributes']);

  return theme('table', array(
    'rows' => $rows,
    'attributes' => $attributes,
  ));
}

/**
 * Theme callback for the POS Order Balance Summary.
 */
function theme_commerce_pos_order_balance_summary(&$variables) {
  $order = $variables['order'];
  $pos_transaction = $variables['pos_transaction'];
  $transaction_statuses = commerce_payment_transaction_statuses();

  $transaction_ids = commerce_pos_get_order_transactions($order->order_id);
  $transactions = commerce_payment_transaction_load_multiple($transaction_ids);

  $rows = array();
  $totals = array();
  foreach ($transactions as $transaction) {
    $payment_action = "";
    $payment_method = commerce_payment_method_load($transaction->payment_method);
    $status = $transaction->status;
    $currency_code = $transaction->currency_code;
    $amount = $transaction->amount;

    if ($status === COMMERCE_PAYMENT_STATUS_SUCCESS) {
      $payment_action = commerce_pos_commerce_currency_format($amount, $currency_code) . '<br>' . theme('commerce_pos_transaction_actions', array('transaction' => $transaction, 'pos_transaction' => $pos_transaction));
      $action_class = "amount";
    }
    elseif ($status == 'void') {
      $payment_action = t('VOID');
      $action_class = "transaction-voided";
    }

    // If a payment transaction doesn't count toward the total paid, show its
    // status in the title.
    $title = $payment_method['title'];
    $title = '<div class="commerce-pos-payment-transaction-title">' . $title . '</div>';

    // Omit buttons from this summary in reports.
    $actions = '';
    if (empty($variables['display_only'])) {
      $actions = theme('commerce_pos_transaction_actions', array('transaction' => $transaction, 'pos_transaction' => $pos_transaction));
    }

    // Add a separator between remote ID and actions, if there are actions.
    if (isset($transaction->remote_id)) {
      $remote_id = !empty($actions) ? ' - ' . $transaction->remote_id : $transaction->remote_id;
    }
    // Don't add any markup if the transaction doesn't have a remote ID.
    $remote_id = $remote_id ? '<span class="commerce-pos-payment-transaction-remote-id small">' . $remote_id . '</span>' : '';

    $row_classes = array('payment-method');
    if (!empty($actions) || !empty($remote_id)) {
      $row_classes[] = 'has-second-row';
    }

    $row = array(
      'data' => array(
        array(
          'data' => $title . $remote_id,
          'class' => $row_classes,
        ),
      ),
    );

    $row['data'][] = array(
      'data' => $payment_action,
      'class' => array($action_class),
    );

    $rows[] = $row;

    // If the payment transaction status indicates it should include the
    // current transaction in the total...
    if (!empty($transaction_statuses[$status]) && $transaction_statuses[$status]['total']) {
      // Add the transaction to its currency's running total if it exists...
      if (isset($totals[$currency_code])) {
        $totals[$currency_code] += $amount;
      }
      else {
        // Or begin a new running total for the currency.
        $totals[$currency_code] = $amount;
      }
    }
  }

  $transaction_summary = theme('table', array(
    'rows' => $rows,
    'attributes' => array(
      'class' => array('commerce-pos-sidebar-table', 'commerce-pos-order-balance-summary'),
    ),
  ));

  $output = $transaction_summary;

  if (!empty($variables['include_balance'])) {
    $transaction_balance = theme('commerce_pos_transaction_balance', array(
      'rows' => commerce_pos_balance_summary_rows($totals, $order),
    ));

    $output .= $transaction_balance;
  }

  return $output;
}

/**
 * Theme callback for POS Transaction Actions.
 */
function theme_commerce_pos_transaction_actions($variables) {
  $transaction = $variables['transaction'];
  $pos_transaction = $variables['pos_transaction'];
  $output = array();

  // Determine which actions are available based on the transaction's status
  // and the pos transaction being completed or not.
  $actions = ($transaction->status === COMMERCE_PAYMENT_STATUS_SUCCESS && empty($pos_transaction->completed)) ? array('Void') : array();

  foreach ($actions as $action) {
    $output[] = l($action, '#', array(
      'attributes' => array(
        'class' => array(
          'commerce-pos-' . strtolower($action) . '-payment',
          'button-link',
        ),
        'data-transaction-id' => $transaction->transaction_id,
      ),
    ));
  }

  return implode(', ', $output);
}
