<?php

/**
 * @file
 * commerce_pos_report.theme.inc
 */

/**
 * Theme callback for the end of day results table.
 */
function theme_commerce_pos_report_end_of_day_result_table(&$variables) {
  // Get the useful values.
  $form = $variables['form'];
  $rows = $form['rows'];
  $header = $form['#header'];

  // Setup the structure to be rendered and returned.
  $content = array(
    '#theme' => 'table',
    '#header' => $header,
    '#rows' => array(),
  );

  // Traverse each row.  @see element_chidren().
  foreach (element_children($rows) as $row_index) {
    $row = array();
    // Traverse each column in the row.  @see element_children().
    foreach (element_children($rows[$row_index]) as $col_index) {
      // Render the column form element.
      if (is_array($rows[$row_index][$col_index])) {
        $row[] = render($rows[$row_index][$col_index]);
      }
    }
    // Add the row to the table.
    $content['#rows'][] = $row;
  }

  // Render the table and return.
  return render($content);
}

/**
 * Theme callback for the report menu options.
 */
function theme_commerce_pos_report_options(&$variables) {
  return theme('item_list', array(
    'items' => array(
      l('<span class="commerce-pos-report-ico-eod"></span>' . t('EOD'), 'admin/commerce/pos/reports/end-of-day', array(
        'attributes' => array('class' => array('commerce-pos-report-eod')),
        'html' => TRUE,
      )),
      l('<span class="commerce-pos-report-ico-journal-role"></span>' . t('Journal Role'), 'admin/commerce/pos/reports/journal-role', array(
        'attributes' => array('class' => array('commerce-pos-journal-role')),
        'html' => TRUE,
      )),
      l('<span class="commerce-pos-report-ico-sales-report"></span>' . t('Sales Report'), 'admin/commerce/pos/reports/sales-report', array(
        'attributes' => array('class' => array('commerce-pos-sales-report')),
        'html' => TRUE,
      )),
    ),
    'attributes' => array(
      'class' => array('commerce-pos-report-options'),
    ),
  ));
}

/**
 * Implements template_preprocess_commerce_pos_report_receipt().
 */
function commerce_pos_report_preprocess_commerce_pos_report_receipt(&$variables) {
  $date = $variables['date'];

  // Get register name.
  $register_id = $variables['register'];
  $register = commerce_pos_register_load($register_id);
  $variables['register'] = $register->name;

  list($totals, $transaction_counts, $transaction_summary) = commerce_pos_report_get_totals($date, $register_id);
  $results = commerce_pos_report_get_eod_report($date, $register_id);

  $variables['rows'] = array();

  foreach ($totals as $currency_code => $currency_totals) {

    foreach ($currency_totals as $payment_method_id => $amounts) {
      if ($payment_method_id == 'commerce_pos_change') {
        // Change shouldn't be listed as an actual item in this report.
        continue;
      }

      $row = array();

      $is_cash = $payment_method_id == 'commerce_pos_payment_cash';
      $payment_method = commerce_payment_method_load($payment_method_id);

      $expected_amount = $amounts[CommercePosService::TRANSACTION_TYPE_SALE] + $amounts[CommercePosService::TRANSACTION_TYPE_RETURN] + $amounts[CommercePosService::TRANSACTION_TYPE_EXCHANGE];

      if ($is_cash && isset($currency_totals['commerce_pos_change'])) {
        $change_amounts = &$currency_totals['commerce_pos_change'];

        // The change amount reflects the change we GAVE BACK, so we have to add
        // it to the expected amount of cash.
        $expected_amount += ($change_amounts[CommercePosService::TRANSACTION_TYPE_SALE] + $change_amounts[CommercePosService::TRANSACTION_TYPE_RETURN] + $change_amounts[CommercePosService::TRANSACTION_TYPE_EXCHANGE]);
        unset($change_amounts);

        if (module_exists('commerce_pos_register')) {
          // Get the float for the day.
          $floats = commerce_pos_register_get_floats($register_id, strtotime($date));
          foreach ($floats as $float) {
            $expected_amount += $float->amount;
          }
        }
      }

      $declared = $results['data'][$payment_method_id]['declared'];

      $row['title'] = $payment_method['title'];
      $row['total_transactions'] = $transaction_counts[$payment_method_id];
      $row['declared'] = commerce_pos_report_currency_format($declared, $currency_code, FALSE);
      $row['expected'] = commerce_pos_report_currency_format($expected_amount, $currency_code);
      $row['over_short'] = commerce_pos_report_currency_format($declared * 100 - $expected_amount, $currency_code);
      if (isset($results['data'][$payment_method_id]['cash_deposit'])) {
        $row['cash_deposit'] = commerce_pos_report_currency_format($results['data'][$payment_method_id]['cash_deposit'], $currency_code, FALSE);
      }

      $row['transaction_summary'] = $transaction_summary[$payment_method_id];

      $variables['rows'][] = $row;
    }
  }

  drupal_add_css(drupal_get_path('module', 'commerce_pos_report') . '/css/commerce_pos_report_receipt.css', array('media' => 'print'));
}

/**
 * Implements template_preprocess_commerce_pos_report_order_details().
 */
function commerce_pos_report_preprocess_commerce_pos_report_order_details(&$variables) {
  $transaction = commerce_pos_get_pos_transaction_by_order_id($variables['order']->order_id);
  $variables['order_wrapper'] = $order_wrapper = entity_metadata_wrapper('commerce_order', $variables['order']);
  $line_item_rows = array();

  $variables['balance_summary'] = commerce_pos_balance_summary($variables['order'], TRUE, FALSE);

  foreach ($order_wrapper->commerce_line_items as $line_item_wrapper) {
    if (isset($line_item_wrapper->commerce_product) && $line_item_wrapper->commerce_product->getIdentifier()) {
      $unit_price = $line_item_wrapper->commerce_unit_price->value();
      $currency_code = $unit_price['currency_code'];
      $base_price = commerce_price_component_total($unit_price, 'base_price');
      $discount_price = commerce_price_component_total($unit_price, 'discount|pos_line_item_discount');
      $row = array();

      $row[] = $line_item_wrapper->quantity->value();
      $row[] = $line_item_wrapper->commerce_product->sku->value();
      $row[] = $line_item_wrapper->commerce_product->title->value();
      $row[] = commerce_currency_format($base_price['amount'], $currency_code);
      $row[] = array(
        'data' => commerce_currency_format($discount_price['amount'], $currency_code),
        'class' => array('commerce-pos-report-negative'),
      );
      $row[] = commerce_currency_format($line_item_wrapper->commerce_total->amount->value(), $line_item_wrapper->commerce_total->currency_code->value());

      $line_item_rows[] = $row;
    }
  }

  $variables['line_items'] = array(
    '#theme' => 'table',
    '#header' => array(
      t('Quantity'),
      t('SKU'),
      t('Item'),
      t('Price'),
      t('Discount'),
      t('Total'),
    ),
    '#rows' => $line_item_rows,
  );

  $order_total = $order_wrapper->commerce_order_total->value();
  $variables['order_total'] = commerce_pos_report_build_price_array($order_total, $transaction->type);

  if (module_exists('commerce_pos_receipt')) {
    $variables['print_link'] = l(t('Print View'), 'admin/commerce/pos/' . $transaction->transactionId . '/print-receipt', array(
      'attributes' => array(
        'class' => array('use-ajax'),
      ),
    ));
  }
  else {
    $variables['print_link'] = FALSE;
  }

  if (module_exists('commerce_pos_messages')) {
    $variables['messages'] = commerce_pos_messages_transaction_messages($variables['order']);
  }
  else {
    $variables['messages'] = FALSE;
  }

  if (!empty($transaction->cashier) && $cashier = commerce_pos_cashier_load($transaction->cashier)) {
    $variables['cashier'] = $cashier->name;
  }
  else {
    $variables['cashier'] = t('None');
  }

  $variables['payment_type'] = commerce_pos_report_payment_types($variables['order']->order_id);
  $variables['transaction_date'] = format_date($transaction->completed, 'custom', 'F j, Y, g:i a');

  if (!empty($variables['order']->uid) && $account = user_load($variables['order']->uid)) {
    $variables['customer'] = l($account->mail, 'user/' . $account->uid);
  }
  else {
    $variables['customer'] = t('Anonymous');
  }
}
