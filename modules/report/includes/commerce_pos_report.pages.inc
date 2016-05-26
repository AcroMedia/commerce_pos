<?php

/**
 * @file
 */

/**
 * Form callback for the end of day report.
 */
function commerce_pos_report_end_of_day($form, &$form_state) {
  $default_date = format_date(REQUEST_TIME, 'custom', 'Y-m-d');
  global $user;

  if (!isset($form_state['results_container_id'])) {
    $form_state['results_container_id'] = 'commerce-pos-report-results-container';
  }

  if (isset($form_state['input']['date']) && is_array($form_state['input']['date'])) {
    $date_input = $form_state['input']['date'];
    $date_filter = $date_input['year'] . '-' . $date_input['month'] . '-' . $date_input['day'];

  }
  else {
    $date_filter = $default_date;
  }

  $js_settings = array(
    'commercePosReport' => array(
      'cssUrl' => url(drupal_get_path('module', 'commerce_pos_report') . '/css/commerce_pos_report_receipt.css', array(
        'absolute' => TRUE,
      )),
    ),
  );

  $ajax = array(
    'callback' => 'commerce_pos_report_end_of_day_results_js',
    'wrapper' => $form_state['results_container_id'],
    'effect' => 'fade',
  );

  $form['#attached']['libraries_load'][] = array('jquery-print');
  $form['#attached']['css'][] = drupal_get_path('module', 'commerce_pos') . '/css/commerce_pos_style.css';
  $form['#attached']['js'][] = array(
    'type' => 'setting',
    'data' => $js_settings,
  );

  $form['header'] = array(
    '#markup' => theme('commerce_pos_header', array('account' => $user)),
  );

  $form['filters']['date'] = array(
    '#type' => 'date_select',
    '#title' => t('Transaction Date'),
    '#date_format' => 'Y-m-d',
    '#default_value' => $default_date,
    '#ajax' => $ajax,
    '#element_key' => 'date_completed',
  );

  if (module_exists('commerce_pos_location')) {
    $current_location = commerce_pos_location_get_current_location();
    $location_options = commerce_pos_location_options();

    if (isset($form_state['input']['location_id'])) {
      $location_id = $form_state['input']['location_id'];
    }
    elseif ($current_location) {
      $location_id = $current_location;
    }
    else {
      $location_id = key($location_options);
    }

    $form['filters']['location_id'] = array(
      '#type' => 'select',
      '#title' => t('Location'),
      '#options' => $location_options,
      '#default_value' => $location_id,
      '#ajax' => $ajax,
    );
  }
  else {
    $location_id = 0;
  }

  // Get saved data for requested date.
  $report_history = commerce_pos_report_get_eod_report($location_id, $date_filter);

  $form['results'] = array(
    '#type' => 'container',
    '#id' => $form_state['results_container_id'],
  );

  $headers = array(
    t('Count Group'),
    t('Declared Amount'),
    t('POS expected Amount'),
    t('Over/Short'),
    t('Cash Deposit'),
  );


  // Get the float for the day.
  if (module_exists('commerce_pos_location')) {
    $float = commerce_pos_location_get_float($location_id, strtotime($date_filter));
  }
  else {
    $float = FALSE;
  }

  $totals = commerce_pos_report_get_totals($date_filter, $location_id);

  foreach ($totals as $currency_code => $currency_totals) {
    $form['results'][$currency_code] = array(
      '#theme' => 'commerce_pos_report_end_of_day_result_table',
      '#header' => $headers,
      'rows' => array(
        '#tree' => TRUE,
      ),
      '#tree' => TRUE,
    );

    foreach ($currency_totals as $payment_method_id => $amounts) {
      if ($payment_method_id == 'commerce_pos_change') {
        // Change shouldn't be listed as an actual item in this report.
        continue;
      }

      $is_cash = $payment_method_id == 'commerce_pos_payment_cash';

      $payment_method = commerce_payment_method_load($payment_method_id);
      $row = array();

      $expected_amount = $amounts[CommercePosService::TRANSACTION_TYPE_SALE] - $amounts[CommercePosService::TRANSACTION_TYPE_RETURN];

      if ($is_cash && isset($currency_totals['commerce_pos_change'])) {
        $change_amounts = &$currency_totals['commerce_pos_change'];

        // The change amount reflects the change we GAVE BACK, so we have to add
        // it to the expected amount of cash.
        $expected_amount += ($change_amounts[CommercePosService::TRANSACTION_TYPE_SALE] - $change_amounts[CommercePosService::TRANSACTION_TYPE_RETURN]);
        unset($change_amounts);
      }

      if ($float) {
        $expected_amount += $float->amount;
      }

      // Count group
      $row['title'] = array(
        '#markup' => $payment_method['title'],
      );

      // Declared amount
      $row['declared'] = array(
        '#type' => 'textfield',
        '#size' => 5,
        '#maxlength' => 10,
        '#title' => t('Declared amount'),
        '#title_display' => 'invisible',
        '#attributes' => array(
          'class' => array('commerce-pos-report-declared-input'),
          'data-currency-code' => $currency_code,
          'data-amount' => 0,
          'data-payment-method-id' => $payment_method_id,
          'data-expected-amount' => $expected_amount,
        ),
        '#element_validate' => array('_commerce_pos_report_validate_amount'),
        '#required' => TRUE,
      );

      if (!empty($report_history['data'][$payment_method_id]['declared'])) {
        $row['declared']['#default_value'] = $report_history['data'][$payment_method_id]['declared'];
      }

      // Expected amount
      $row[] = array(
        '#markup' => '<div class="commerce-pos-report-expected-amount">'
          . commerce_pos_report_currency_format($expected_amount, $currency_code)
          . '</div>',
      );

      // Over/short
      $row[] = array(
        '#markup' => '<div class="commerce-pos-report-balance" data-payment-method-id="' . $payment_method_id . '">'
          . commerce_pos_report_currency_format(0, $currency_code)
          . '</div>',
      );

      // Cash Deposit
      if ($is_cash) {
        $row['cash_deposit'] = array(
          '#type' => 'textfield',
          '#size' => 5,
          '#maxlength' => 10,
          '#title' => t('Cash Deposit'),
          '#title_display' => 'invisible',
        );

        if (!empty($report_history['data'][$payment_method_id]['cash_deposit'])) {
          $row['cash_deposit']['#default_value'] = $report_history['data'][$payment_method_id]['cash_deposit'];
        }

      }
      else {
        $row['cash_deposit'] = array(
          '#markup' => '&nbsp;',
        );
      }

      $form['results'][$currency_code]['rows'][$payment_method_id] = $row;
    }
  }

  if (!empty($totals)) {
    $js_settings['currencies'] = commerce_pos_report_currency_js(array_keys($totals));

    $form['results']['#attached']['js'][] = drupal_get_path('module', 'commerce_pos_report') . '/js/commerce_pos_report.js';
    $form['results']['#attached']['js'][] = array(
      'type' => 'setting',
      'data' => array(
        'commercePosReport' => $js_settings,
      ),
    );
  }

  if (!empty($totals)) {
    $form['results']['actions'] = array(
      '#type' => 'actions',
    );

    $form['results']['actions']['save'] = array(
      '#type' => 'submit',
      '#value' => t('Save and Print'),
      '#ajax' => array(
        'callback' => 'commerce_pos_report_end_of_day_save_js',
        'wrapper' => '',
      ),
    );
  }

  return $form;
}

/**
 * @param $date_filter
 * @param $location_id
 * @return array
 */
function commerce_pos_report_get_totals($date_filter, $location_id) {
  $query = db_select('commerce_pos_transaction', 't');

  // Get all POS transactions for the given day.
  $query->fields('ct', array(
    'commerce_order_total_amount',
    'commerce_order_total_currency_code',
    'commerce_order_total_data',
  ));

  $query->fields('pt', array(
    'amount',
    'payment_method',
  ));

  $query->addField('t', 'type');

  $query->join('field_data_commerce_order_total', 'ct', 'ct.entity_id = t.order_id AND ct.entity_type=:commerce_order', array(
    ':commerce_order' => 'commerce_order',
  ));

  $query->join('commerce_payment_transaction', 'pt', 'pt.order_id = t.order_id');

  $query->where("DATE_FORMAT(FROM_UNIXTIME(t.completed), '%Y-%m-%d') = :completed_date", array(
    ':completed_date' => $date_filter,
  ));

  $query->orderBy('t.order_id');

  if ($date_filter) {
    $query->condition('t.location_id', $location_id);
  }

  $totals = array();

  foreach ($query->execute() as $row) {

    if (!isset($totals[$row->commerce_order_total_currency_code][$row->payment_method])) {
      $totals[$row->commerce_order_total_currency_code][$row->payment_method] = array(
        CommercePosService::TRANSACTION_TYPE_SALE => 0,
        CommercePosService::TRANSACTION_TYPE_RETURN => 0,
      );
    }
    $method = &$totals[$row->commerce_order_total_currency_code][$row->payment_method];
    $method[$row->type] += $row->amount;
  }

  return $totals;
}

/**
 * @param $element
 * @param $form_state
 * @param $form
 */
function _commerce_pos_report_validate_amount($element, &$form_state, $form) {
  if (!is_numeric($element['#value'])) {
    form_error($element, t('Amount must be a number'));
  }
}

/**
 * AJAX callback for the report filter elements.
 */
function commerce_pos_report_end_of_day_results_js($form, &$form_state) {
  return $form['results'];
}

/**
 * AJAX callback for the report "save" button.
 */
function commerce_pos_report_end_of_day_save_js($form, &$form_state) {
  $commands = array();

  $date = $form_state['values']['date'];
  $location = $form_state['values']['location_id'];

  $receipt = theme('commerce_pos_report_receipt', array('date' => $date, 'location' => $location));

  $commands[] = array(
    'command' => 'printWindow',
    'content' => $receipt,
  );

  return array('#type' => 'ajax', '#commands' => $commands);
}

/**
 * Submit handler for the End of Day report form.
 */
function commerce_pos_report_end_of_day_submit($form, &$form_state) {
  $date = $form_state['values']['date'];
  // POS location.
  $location = $form_state['values']['location_id'];

  // Serialize form data.
  $data = $form_state['values']['USD']['rows'];
  $serial_data = serialize($data);

  $exists = commerce_pos_report_exists($date, $location);

  if ($exists) {
    $query = db_update('commerce_pos_report_declared_data')
      ->condition('location_id', $location, '=')
      ->condition('date', strtotime($date), '=')
      ->fields(array(
        // TODO: There is an amount column - what's it for?
        'data' => $serial_data,
      ));
    $id = $query->execute();
  }
  else {
    $query = db_insert('commerce_pos_report_declared_data')
      ->fields(array(
        'location_id' => $location,
        // TODO: There is an amount column - what's it for?
        'date' => strtotime($date),
        'data' => $serial_data,
      ));
    $id = $query->execute();
  }
}

function commerce_pos_report_exists($date, $location) {
  $query = db_select('commerce_pos_report_declared_data', 't')
    ->fields('t')
    ->condition('location_id', $location, '=')
    ->condition('date', strtotime($date), '=');
  $result = $query->execute()->fetchAssoc();

  return !empty($result);
}

function commerce_pos_report_get_eod_report($location, $date) {
  $time = strtotime($date);

  $result = db_select('commerce_pos_report_declared_data', 't')
    ->fields('t')
    ->condition('location_id', $location)
    ->condition('date', $time, '=')
    ->execute();

  $report = $result->fetchAssoc();
  $report['data'] = unserialize($report['data']);

  return $report;
}

/**
 * Callback for printing a transaction receipt.
 *
 * This returns a custom AJAX command to trigger the printing of the receipt
 * via JavaScript. Modules wishing to use this callback will need to make sure
 * that commerce_pos_receipt.js is included on the page.
 *
 * @param string $date
 *   The date of the report to print.
 * @param $location
 *   The location to get the report for.
 */
function commerce_pos_report_receipt_print($date, $location) {
  $commands = array();

//  $transaction = CommercePosService::loadTransaction($transaction_id);
  $receipt = theme('commerce_pos_report_receipt', array('date' => $date));

  $commands[] = array(
    'command' => 'printReceipt',
    'content' => $receipt,
  );

  ajax_deliver(array('#type' => 'ajax', '#commands' => $commands));
}
