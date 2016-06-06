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

function theme_commerce_pos_report_end_of_day_receipt(&$variables) {
  $form = $variables;
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

  $totals = commerce_pos_report_get_totals($date, $register_id);
  $results = commerce_pos_report_get_eod_report($register_id, $date);

  // Get the float for the day.
  if (module_exists('commerce_pos_register')) {
    $float = commerce_pos_register_get_float($register_id, strtotime($date));
  }
  else {
    $float = FALSE;
  }

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

      $expected_amount = $amounts[CommercePosService::TRANSACTION_TYPE_SALE] - $amounts[CommercePosService::TRANSACTION_TYPE_RETURN];

      if ($is_cash && isset($currency_totals['commerce_pos_change'])) {
        $change_amounts = &$currency_totals['commerce_pos_change'];

        // The change amount reflects the change we GAVE BACK, so we have to add
        // it to the expected amount of cash.
        $expected_amount += ($change_amounts[CommercePosService::TRANSACTION_TYPE_SALE] - $change_amounts[CommercePosService::TRANSACTION_TYPE_RETURN]);
        unset($change_amounts);

        if ($float) {
          $expected_amount += $float->amount;
        }

      }

      $declared = $results['data'][$payment_method_id]['declared'];

      $row['title'] = $payment_method['title'];
      $row['declared'] = commerce_pos_report_currency_format($declared, $currency_code, FALSE);
      $row['expected'] = commerce_pos_report_currency_format($expected_amount, $currency_code);
      $row['over_short'] = commerce_pos_report_currency_format($declared * 100 - $expected_amount, $currency_code);
      if (isset($results['data'][$payment_method_id]['cash_deposit'])) {
        $row['cash_deposit'] = commerce_pos_report_currency_format($results['data'][$payment_method_id]['cash_deposit'], $currency_code, FALSE);
      }

      $variables['rows'][] = $row;
    }

  }




  drupal_add_css(drupal_get_path('module', 'commerce_pos_report') . '/css/commerce_pos_report_receipt.css', array('media' => 'print'));
}
