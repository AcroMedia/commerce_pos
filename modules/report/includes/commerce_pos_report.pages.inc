<?php

/**
 * @file
 * Page output functions for Commerce POS Report module.
 */

/**
 * Callback for the POS reports page.
 */
function commerce_pos_reports_page() {
  drupal_goto('admin/commerce/pos/reports/end-of-day');
}

/**
 * Form callback for the end of day report.
 */
function commerce_pos_report_end_of_day($form, &$form_state) {
  global $user;

  $default_date = format_date(REQUEST_TIME, 'custom', 'Y-m-d');

  $form['#attached']['css'][] = drupal_get_path('module', 'commerce_pos_report') . '/css/commerce_pos_report.css';

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

  $can_save = commerce_pos_report_before_end_of_day(strtotime($date_filter . ' ' . date('H:i:s')));

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

  $form['#prefix'] = '<div id="commerce-pos-report-eod-form-container">';
  $form['#suffix'] = '</div>';

  $form['#attached']['libraries_load'][] = array('jquery-print');
  $form['#attached']['css'][] = drupal_get_path('module', 'commerce_pos') . '/css/commerce_pos_style.css';
  $form['#attached']['js'][] = array(
    'type' => 'setting',
    'data' => $js_settings,
  );

  if (!isset($form_state['header'])) {
    $form_state['header'] = theme('commerce_pos_header', array('account' => $user));
  }

  $form['header'] = array(
    '#markup' => $form_state['header'],
  );

  $form['filters']['date_header'] = array(
    '#type' => 'item',
    '#title' => t('Transaction Date'),
    '#prefix' => '<div class="commerce-pos-report-transaction-date-header">',
    '#suffix' => '</div>',
  );

  $form['filters']['date'] = array(
    '#type' => 'date_select',
    '#title' => t('Transaction Date'),
    '#title_display' => 'invisible',
    '#date_format' => 'Y-m-d',
    '#default_value' => $default_date,
    '#ajax' => $ajax,
    '#element_key' => 'date_completed',
  );

  $register_id = 0;

  if (module_exists('commerce_pos_register') && $register_options = commerce_pos_register_options()) {
    $current_register = commerce_pos_register_get_current_register();
    $register_ids = array();

    foreach ($register_options as $location_name => $register_info) {
      foreach ($register_info as $id => $name) {
        $register_ids[] = $id;
      }
    }

    if (isset($form_state['input']['register_id'])) {
      $register_id = $form_state['input']['register_id'];
    }
    elseif ($current_register) {
      $register_id = $current_register;
    }
    else {
      // Fall back to using the first register ID.
      $register_id = $register_ids[0];
    }

    $form['filters']['register_id'] = array(
      '#type' => 'select',
      '#title' => t('Register'),
      '#options' => $register_options,
      '#default_value' => $register_id,
      '#ajax' => $ajax,
    );
  }

  if (!isset($form_state['report_options'])) {
    $form_state['report_options'] = theme('commerce_pos_report_options');
  }

  $form['report-options'] = array(
    '#markup' => $form_state['report_options'],
  );

  // Get saved data for requested date.
  $report_history = commerce_pos_report_get_eod_report($date_filter, $register_id);

  $form['results'] = array(
    '#type' => 'container',
    '#id' => $form_state['results_container_id'],
  );

  $headers = array(
    t('Payment Type'),
    t('Declared Amount'),
    t('POS expected Amount'),
    t('Over/Short'),
    t('Cash Deposit'),
  );

  list($totals, $transaction_counts) = commerce_pos_report_get_totals($date_filter, $register_id);

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
      $currency = commerce_currency_load($currency_code);
      $payment_method = commerce_payment_method_load($payment_method_id);
      $row = array();

      $expected_amount = $amounts[CommercePosService::TRANSACTION_TYPE_SALE] - $amounts[CommercePosService::TRANSACTION_TYPE_RETURN];
      $input_prefix = '';
      $input_suffix = '';

      if (!empty($currency['symbol'])) {
        switch ($currency['symbol_placement']) {
          case 'before':
            $input_prefix = $currency['symbol'];
            break;

          case 'after':
            $input_suffix = $currency['symbol'];
        }
      }

      if ($is_cash) {
        if (isset($currency_totals['commerce_pos_change'])) {
          $change_amounts = &$currency_totals['commerce_pos_change'];

          // The change amount reflects the change we GAVE BACK, so we have to
          // add it to the expected amount of cash.
          $expected_amount += ($change_amounts[CommercePosService::TRANSACTION_TYPE_SALE] - $change_amounts[CommercePosService::TRANSACTION_TYPE_RETURN]);
          unset($change_amounts);
        }

        if (module_exists('commerce_pos_register')) {
          // Get the float for the day.
          $floats = commerce_pos_register_get_floats($register_id, strtotime($date_filter));
          foreach ($floats as $float) {
            $expected_amount += $float->amount;
          }
        }
      }

      // Count group.
      $row['title'] = array(
        '#markup' => $payment_method['title'],
      );

      // Declared amount.
      $declared = array(
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
        '#disabled' => !$can_save,
        '#field_prefix' => $input_prefix,
        '#field_suffix' => $input_suffix,
      );

      if ($is_cash) {
        $declared['#commerce_pos_keypad'] = array(
          'type' => 'cash input',
          'currency_code' => $currency_code,
        );
      }

      $row['declared'] = $declared;

      if (!empty($report_history['data'][$payment_method_id]['declared'])) {
        $row['declared']['#default_value'] = $report_history['data'][$payment_method_id]['declared'];
      }

      // Expected amount.
      $row[] = array(
        '#markup' => '<div class="commerce-pos-report-expected-amount" data-payment-method-id="' . $payment_method_id . '">'
        . commerce_pos_report_currency_format($expected_amount, $currency_code)
        . '</div>',
      );

      // Over/short.
      $row[] = array(
        '#markup' => '<div class="commerce-pos-report-balance" data-payment-method-id="' . $payment_method_id . '">'
        . commerce_pos_report_currency_format(0, $currency_code)
        . '</div>',
      );

      // Cash Deposit.
      if ($is_cash) {
        $row['cash_deposit'] = array(
          '#type' => 'textfield',
          '#size' => 5,
          '#maxlength' => 10,
          '#title' => t('Cash Deposit'),
          '#title_display' => 'invisible',
          '#field_prefix' => $input_prefix,
          '#field_suffix' => $input_suffix,
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

    if ($can_save) {
      $form['results']['actions']['save'] = array(
        '#type' => 'submit',
        '#value' => t('Save'),
        '#validate' => array('commerce_pos_report_end_of_day_save_validate'),
        '#submit' => array('commerce_pos_report_end_of_day_save_submit'),
        '#disabled' => !$can_save,
      );
    }

    $form['results']['actions']['print'] = array(
      '#type' => 'submit',
      '#value' => t('Print'),
      '#ajax' => array(
        'callback' => 'commerce_pos_report_end_of_day_print_js',
        'wrapper' => 'commerce-pos-report-eod-form-container',
      ),
    );

    if ($can_save) {
      $form['results']['actions']['print']['#validate'][] = 'commerce_pos_report_end_of_day_save_validate';
      $form['results']['actions']['print']['#submit'][] = 'commerce_pos_report_end_of_day_save_submit';
    }

    if (strtotime($date_filter) >= strtotime('00:00:00')) {
      $form['results']['end_of_day_notice'] = array(
        '#markup' => '<div class="commerce-pos-report-eod-notice"><p>' . t('End of Day is at @time.', array(
          '@time' => format_date(commerce_pos_report_end_of_day_timestamp(), 'custom', 'g:i a T'),
        )) . '</p><p>' . t('Changes cannot be saved after that time.') . '</p></div>',
        '#weight' => 200,
      );
    }
  }

  return $form;
}

/**
 * Get the reports for the specified day of a single register.
 *
 * @param string $date_filter
 *   The date range to filter by in compatible strtotime format.
 *   Will search this time +1day.
 * @param int $register_id
 *   The id of the register to get totals for.
 *
 * @return array
 *   Total, Count and Summary
 */
function commerce_pos_report_get_totals($date_filter, $register_id) {
  $query = db_select('commerce_pos_transaction', 't');

  $query->fields('t');

  // Get all POS transactions for the given day.
  $query->fields('ct', array(
    'commerce_order_total_amount',
    'commerce_order_total_currency_code',
    'commerce_order_total_data',
  ));

  $query->fields('pt', array(
    'amount',
    'payment_method',
    'status',
  ));

  $query->addField('t', 'type');

  $query->join('field_data_commerce_order_total', 'ct', 'ct.entity_id = t.order_id AND ct.entity_type=:commerce_order', array(
    ':commerce_order' => 'commerce_order',
  ));

  $query->join('commerce_payment_transaction', 'pt', 'pt.order_id = t.order_id');

  $date_timestamp = strtotime($date_filter);
  $query->condition('t.completed', $date_timestamp, '>=');
  $query->condition('t.completed', strtotime('+1 day', $date_timestamp), '<');

  $query->orderBy('t.order_id');

  if ($date_filter) {
    $query->condition('t.register_id', $register_id);
  }

  $totals = array();
  $transaction_counts = array();
  $transaction_summary = array();

  // Populate the arrays with the POS Payment methods.
  $pos_methods = commerce_pos_get_payment_methods();
  $currencies = commerce_currencies(TRUE);

  foreach ($pos_methods as $pos_method) {
    if (!empty($pos_method['status']) && intval($pos_method['status']) === COMMERCE_POS_PAYMENT_METHOD_STATUS_ENABLED) {
      foreach (array_keys($currencies) as $currency_code) {
        $totals[$currency_code][$pos_method['id']] = array(
          CommercePosService::TRANSACTION_TYPE_SALE => 0,
          CommercePosService::TRANSACTION_TYPE_RETURN => 0,
        );
      }
      $transaction_summary[$pos_method['id']] = array();
      $transaction_counts[$pos_method['id']] = 0;
    }
  }

  $visible_statuses = variable_get('commerce_pos_receipt_visible_payment_status', array(COMMERCE_PAYMENT_STATUS_SUCCESS, COMMERCE_PAYMENT_STATUS_VOID));
  $statuses = commerce_payment_transaction_statuses();

  foreach ($query->execute() as $row) {

    if (!isset($totals[$row->commerce_order_total_currency_code][$row->payment_method])) {
      $totals[$row->commerce_order_total_currency_code][$row->payment_method] = array(
        CommercePosService::TRANSACTION_TYPE_SALE => 0,
        CommercePosService::TRANSACTION_TYPE_RETURN => 0,
      );
    }

    if ($row->status == COMMERCE_PAYMENT_STATUS_SUCCESS) {
      $method = &$totals[$row->commerce_order_total_currency_code][$row->payment_method];
      $method[$row->type] += $row->amount;
    }

    if (in_array($row->status, $visible_statuses)) {
      if (!isset($transaction_counts[$row->payment_method])) {
        $transaction_counts[$row->payment_method] = 0;
      }

      $transaction_counts[$row->payment_method]++;

      if (!isset($transaction_summary[$row->payment_method])) {
        $transaction_summary[$row->payment_method] = array();
      }

      if (!empty($row->cashier) && $cashier = commerce_pos_cashier_load($row->cashier)) {
        $cashier_name = $cashier->name;
      }
      else {
        $cashier_name = t('Unknown');
      }

      $transaction_summary[$row->payment_method][] = array(
        'order_id' => $row->order_id,
        'completed' => $row->completed,
        'cashier' => $cashier_name,
        'amount' => commerce_pos_report_currency_format($row->amount, $row->commerce_order_total_currency_code),
        'status' => $statuses[$row->status]['title'],
      );
    }
  }

  return array($totals, $transaction_counts, $transaction_summary);
}

/**
 * Used to validate that the declared amount is set if not disabled.
 */
function _commerce_pos_report_validate_amount($element, &$form_state, $form) {
  if (!is_numeric($element['#value']) && empty($element['#disabled'])) {
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
 * AJAX callback for the report "print" button.
 */
function commerce_pos_report_end_of_day_print_js($form, &$form_state) {
  $commands = array();

  $commands[] = ajax_command_replace(NULL, render($form));
  $commands[] = ajax_command_prepend(NULL, theme('status_messages'));

  if (!form_get_errors()) {
    $date = $form_state['values']['date'];
    $register = $form_state['values']['register_id'];

    $commands[] = array(
      'command' => 'printWindow',
      'content' => theme('commerce_pos_report_receipt', array('date' => $date, 'register' => $register)),
    );
  }

  return array('#type' => 'ajax', '#commands' => $commands);
}

/**
 * Validation handler for the End of Day report "save" button.
 */
function commerce_pos_report_end_of_day_save_validate($form, &$form_state) {
  // Make sure we're not past the end of the day.
  if (time() >= commerce_pos_report_end_of_day_timestamp()) {
    form_error($form['results']['actions']['save'], t('Unable to save, it is past the end of the day.'));
  }
}

/**
 * Submit handler for the End of Day report "save" button.
 */
function commerce_pos_report_end_of_day_save_submit($form, &$form_state) {
  $date = $form_state['values']['date'];
  // POS register.
  $register = $form_state['values']['register_id'];

  // Serialize form data.
  $default_currency = commerce_default_currency();
  $data = $form_state['values'][$default_currency]['rows'];
  $serial_data = serialize($data);

  $exists = commerce_pos_report_exists($date, $register);

  if ($exists) {
    $query = db_update('commerce_pos_report_declared_data')
      ->condition('register_id', $register, '=')
      ->condition('date', strtotime($date), '=')
      ->fields(array(
        // TODO: There is an amount column - what's it for?
        'data' => $serial_data,
      ));
    $query->execute();
  }
  else {
    $query = db_insert('commerce_pos_report_declared_data')
      ->fields(array(
        'register_id' => $register,
        // TODO: There is an amount column - what's it for?
        'date' => strtotime($date),
        'data' => $serial_data,
      ));
    $query->execute();
  }
}

/**
 * Checks if a report already exists, used to determine update or insert.
 *
 * @param string $date
 *   A strtotime compatible date, will search this date exactly.
 * @param int $register_id
 *   Id of the register to load the report for.
 *
 * @return bool
 *   True if the report exists, false if it doesn't.
 */
function commerce_pos_report_exists($date, $register_id) {
  $query = db_select('commerce_pos_report_declared_data', 't')
    ->fields('t')
    ->condition('register_id', $register_id, '=')
    ->condition('date', strtotime($date), '=');
  $result = $query->execute()->fetchAssoc();

  return !empty($result);
}

/**
 * Loads the EOD report for a specific register and date.
 *
 * @param string $date
 *   A strtotime compatible date, will search this date exactly.
 * @param int $register_id
 *   Id of the register to load the report for.
 *
 * @return array
 *   Report metainfo and data.
 */
function commerce_pos_report_get_eod_report($date, $register_id) {
  $time = strtotime($date);

  $result = db_select('commerce_pos_report_declared_data', 't')
    ->fields('t')
    ->condition('register_id', $register_id)
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
 */
function commerce_pos_report_receipt_print($date) {
  $commands = array();

  $receipt = theme('commerce_pos_report_receipt', array('date' => $date));

  $commands[] = array(
    'command' => 'printReceipt',
    'content' => $receipt,
  );

  ajax_deliver(array('#type' => 'ajax', '#commands' => $commands));
}

/**
 * Callback for the Journal Role form/report.
 */
function commerce_pos_report_journal_role($form, &$form_state) {
  global $user;

  $form['#tree'] = TRUE;
  $form['#attributes']['class'][] = 'element-form-report';
  $form['header'] = array(
    '#markup' => theme('commerce_pos_header', array('account' => $user)),
  );

  $query_filter_defaults = array(
    'date' => format_date(REQUEST_TIME, 'custom', 'Y-m-d'),
    'register' => commerce_pos_register_get_current_register(),
    'payment_type' => 'all',
    'cashier' => 'all',
    'results_per_page' => COMMERCE_POS_REPORT_JOURNAL_ROLE_DEFAULT_ITEMS_PER_PAGE,
  );

  $query_params = drupal_get_query_parameters() + $query_filter_defaults;

  $form['filters'] = array(
    '#type' => 'container',
  );

  if (!isset($form_state['results_container_id'])) {
    $form_state['results_container_id'] = 'commerce-pos-report-journal-role-container';
  }

  $js_settings = array(
    'commercePosReport' => array(
      'cssUrl' => url(drupal_get_path('module', 'commerce_pos_report') . '/css/commerce_pos_report_receipt.css', array(
        'absolute' => TRUE,
      )),
    ),
  );

  $form['#attached']['libraries_load'][] = array('jquery-print');
  $form['#attached']['css'][] = drupal_get_path('module', 'commerce_pos') . '/css/commerce_pos_style.css';
  $form['#attached']['css'][] = drupal_get_path('module', 'commerce_pos_report') . '/css/commerce_pos_report.css';
  $form['#attached']['js'][] = drupal_get_path('module', 'commerce_pos_report') . '/js/commerce_pos_report.sorter.js';
  $form['#attached']['js'][] = array(
    'type' => 'setting',
    'data' => $js_settings,
  );

  $form['filters']['date_header'] = array(
    '#type' => 'item',
    '#title' => t('Transaction Date'),
    '#prefix' => '<div class="commerce-pos-report-transaction-date-header">',
    '#suffix' => '</div>',
  );

  $form['filters']['date'] = array(
    '#type' => 'date_select',
    '#title' => t('Transaction Date'),
    '#title_display' => 'invisible',
    '#date_format' => 'Y-m-d',
    '#default_value' => $query_params['date'],
    '#element_key' => 'date_completed',
    '#attributes' => array(
      'class' => array('commerce-pos-report-journal-role-filter'),
    ),
  );

  if (module_exists('commerce_pos_register')) {
    $register_options = commerce_pos_register_options();

    $form['filters']['register'] = array(
      '#type' => 'select',
      '#title' => t('Register'),
      '#options' => $register_options,
      '#default_value' => $query_params['register'],
      '#attributes' => array(
        'class' => array('commerce-pos-report-journal-role-filter'),
      ),
    );
  }

  $form['report-options'] = array(
    '#theme' => 'commerce_pos_report_options',
  );

  $form['options'] = array(
    '#type' => 'container',
    '#id' => 'commerce-pos-report-journal-role-options-container',
  );

  $payment_filter_options = array(
    'all' => t('All'),
  );

  foreach (commerce_pos_get_payment_methods() as $key => $payment_method) {
    $payment_filter_options[$key] = $payment_method['title'];
  }

  $form['options']['payment_type'] = array(
    '#type' => 'select',
    '#title' => t('Payment'),
    '#options' => $payment_filter_options,
    '#default_value' => $query_params['payment_type'],
    '#attributes' => array(
      'class' => array('commerce-pos-report-journal-role-filter'),
    ),
  );

  $cashier_options = array(
    'all' => t('All'),
  );

  $cashier_options += commerce_pos_cashiers_get_cashiers();

  $form['options']['cashier'] = array(
    '#type' => 'select',
    '#title' => t('Cashier'),
    '#default_value' => $query_params['cashier'],
    '#options' => $cashier_options,
    '#attributes' => array(
      'class' => array('commerce-pos-report-journal-role-filter'),
    ),
  );

  $form['options']['results_per_page'] = array(
    '#type' => 'select',
    '#title' => t('Items per page'),
    '#options' => array(
      25 => '25',
      50 => '50',
      100 => '100',
      500 => '500',
      -1 => t('all'),
    ),
    '#default_value' => $query_params['results_per_page'],
    '#attributes' => array(
      'class' => array('commerce-pos-report-journal-role-filter'),
    ),
  );

  $form['results'] = array(
    '#type' => 'container',
    '#id' => $form_state['results_container_id'],
    '#attributes' => array(
      'class' => array(
        'report-table-theme',
      ),
    ),
  );

  $form['results']['table'] = commerce_pos_report_build_journal_role_table($query_params);

  // Pager has to come after we've build the journal role table.
  $form['options']['pager'] = array('#theme' => 'pager');
  $form['results']['pager'] = array('#theme' => 'pager');

  $form['submit'] = array(
    '#value' => t('Submit'),
    '#type' => 'submit',
    '#weight' => -50,
    '#attributes' => array(
      'class' => array('element-invisible', 'commerce-pos-report-journal-role-submit'),
    ),
  );

  return $form;
}

/**
 * Submit handler for the journal role report form.
 */
function commerce_pos_report_journal_role_submit($form, &$form_state) {
  $query_params = array_merge(drupal_get_query_parameters(), $form_state['values']['filters'], $form_state['values']['options']);
  $form_state['redirect'] = array(current_path(), array('query' => $query_params));
}

/**
 * Builds a Journal Role result table.
 *
 * @param array $filters
 *   An array of filters for the query. Can include any of the following:
 *   'register' - The register ID of the transactions.
 *   'date' - The date of the transactions in the format of YYYY-MM-DD.
 *   'cashier' - The cashier who performed the transactions.
 *   'payment_type' - The type of payment the transactions had.
 *   'results_per_page' - The number of results per page.
 *
 * @return array
 *   A render array for the result table.
 */
function commerce_pos_report_build_journal_role_table(array $filters = array()) {
  $header = array(
    array('data' => t('Order No.'), 'field' => 't.order_id'),
    array('data' => t('Time'), 'field' => 't.completed'),
    array('data' => t('Payment')),
    array('data' => t('Cashier'), 'field' => 'u.name'),
    array('data' => t('Customer')),
    array('data' => t('Purchased')),
    array('data' => t('Returned')),
    array('data' => t('Discount')),
    array(
      'data' => t('Order Total'),
      'field' => 'ot.commerce_order_total_amount',
    ),
  );

  $query = db_select('commerce_pos_transaction', 't')
    ->extend('PagerDefault')
    ->extend('TableSort');

  if ($filters['results_per_page'] > 0) {
    $query->limit($filters['results_per_page']);
  }

  $query->fields('t', array('order_id', 'completed', 'uid', 'type'));

  // Cashier data
  // We left join because legacy transactions may not have a cashier.
  $query->leftJoin('commerce_pos_cashier', 'cashier', 'cashier.cashier_id = t.cashier');
  $query->addField('cashier', 'name', 'cashier');

  // Payment transaction data.
  $query->join('commerce_payment_transaction', 'pt', 'pt.order_id = t.order_id');
  $query->fields('pt', array('transaction_id'));

  // Join order data.
  $query->join('commerce_order', 'o', 'o.order_id = t.order_id');
  $query->leftJoin('users', 'customer', 'customer.uid = o.uid');
  $query->addField('customer', 'mail', 'customer_mail');

  // Join order total data.
  $query->join('field_data_commerce_order_total', 'ot', 't.order_id = ot.entity_id AND ot.entity_type = :commerce_order', array(
    ':commerce_order' => 'commerce_order',
  ));
  $query->fields('ot', array(
    'commerce_order_total_amount',
    'commerce_order_total_currency_code',
    'commerce_order_total_data',
  ));

  // Join line item data.
  $query->join('commerce_line_item', 'l', 'l.order_id = t.order_id');
  $query->addField('l', 'quantity');
  $query->addExpression('COUNT(DISTINCT l.line_item_id)', 'item_count');

  $line_item_types = commerce_product_line_item_types();
  $query->condition('l.type', $line_item_types, 'IN');

  // Make sure line item count is aggregated.
  $query->groupBy('l.order_id');

  // Set up our filters/conditions.
  if (!empty($filters['register'])) {
    $query->condition('t.register_id', $filters['register']);
  }

  if (!empty($filters['date'])) {
    $date_timestamp = strtotime($filters['date']);
    $query->condition('completed', $date_timestamp, '>=');
    $query->condition('completed', strtotime('+1 day', $date_timestamp), '<');
  }

  if (!empty($filters['payment_type']) && $filters['payment_type'] != 'all') {
    $query->condition('pt.payment_method', $filters['payment_type']);
  }

  if (!empty($filters['cashier']) && $filters['cashier'] != 'all') {
    $query->condition('cashier.cashier_id', $filters['cashier']);
  }

  $query->condition('completed', '0', '>');
  $query->orderByHeader($header);

  $result = $query->execute();

  $rows = array();
  $totals = array(
    'purchased' => 0,
    'returned' => 0,
    'order_total' => array(),
  );

  if ($result->rowCount() > 0) {
    foreach ($result as $row) {
      $table_row = array();

      $return_count = 0;
      $purchase_count = 0;

      switch ($row->type) {
        case CommercePosService::TRANSACTION_TYPE_RETURN:
          $return_count = (int) $row->quantity;
          break;

        default:
          $purchase_count = (int) $row->quantity;
          break;
      }

      // Build order ID link.
      $table_row[] = l($row->order_id, '/admin/commerce/pos/report/ajax/order-details/' . $row->order_id, array(
        'external' => TRUE,
        'attributes' => array(
          'class' => array('commerce-pos-report-journal-role-order use-ajax'),
          'data-order-id' => $row->order_id,
        ),
      ));

      $table_row[] = format_date($row->completed, 'custom', 'g:i a');

      // Payment type.
      $table_row[] = commerce_pos_report_payment_types($row->order_id);

      // Cashier.
      $table_row[] = $row->cashier;

      $table_row[] = !empty($row->customer_mail) ? $row->customer_mail : t('Anonymous');

      // Purchased.
      $table_row[] = $purchase_count;

      // Returned.
      $table_row[] = $return_count;

      // Discount flag.
      $order_total_data = unserialize($row->commerce_order_total_data);
      $has_discount = FALSE;

      if (!empty($order_total_data['components'])) {
        foreach ($order_total_data['components'] as $component) {
          if (!empty($component['name']) && strpos($component['name'], 'discount|') === 0) {
            $has_discount = TRUE;
            break;
          }
        }
      }

      $table_row[] = $has_discount ? '<span class="commerce-pos-report-ico-checked"></span>' : '';

      // Order total.
      $table_row[] = commerce_currency_format($row->commerce_order_total_amount, $row->commerce_order_total_currency_code);

      $rows[] = array(
        'data' => $table_row,
        'class' => array('commerce-pos-transaction-type-' . $row->type),
      );

      // Add another row right below it will serve as a placeholder for loading
      // in order data.
      $rows[] = array(
        array(
          'colspan' => count($table_row),
          'class' => array('commerce-pos-report-journal-role-order-info', 'element-invisible'),
          'data-order-id' => $row->order_id,
        ),
      );

      $totals['purchased'] += $purchase_count;
      $totals['returned'] += $return_count;

      if (!isset($totals['order_total'][$row->commerce_order_total_currency_code])) {
        $totals['order_total'][$row->commerce_order_total_currency_code] = $row->commerce_order_total_amount;
      }
      else {
        $totals['order_total'][$row->commerce_order_total_currency_code] += $row->commerce_order_total_amount;
      }
    }

    // Add a row for some totals.
    $order_totals_summary = array();

    foreach ($totals['order_total'] as $currency_code => $amount) {
      $order_totals_summary[] = commerce_currency_format($amount, $currency_code);
    }

    $rows[] = array(
      '',
      '',
      '',
      '',
      '',
      $totals['purchased'],
      $totals['returned'],
      '',
      implode('<br>', $order_totals_summary),
    );

    $table = array(
      '#theme' => 'table',
      '#rows' => $rows,
      '#header' => $header,
      '#attached' => array(
        'js' => array(
          drupal_get_path('module', 'commerce_pos_report') . '/js/commerce_pos_report.sorter.js',
        ),
        'library' => array(
          array('system', 'drupal.ajax'),
        ),
      ),
    );

    return $table;
  }
  else {
    return array(
      '#markup' => t('No transactions found'),
    );
  }
}

/**
 * Builds a Sales Report result table.
 *
 * @param array $filters
 *   An array of filters for the query. Can include any of the following:
 *   'cashier' - The cashier who performed the transactions.
 *   'payment_type' - The type of payment the transactions had.
 *   'results_per_page' - The number of results per page.
 *
 * @return array
 *   A render array for the result table.
 */
function commerce_pos_report_build_sales_report_table(array $filters = array()) {
  $header = array(
      array('data' => t('Cashier Id'), 'field' => 't.cashier'),
      array('data' => t('Cashier'), 'field' => 'cashier.name'),
      array('data' => t('Net Sales')),
      array('data' => t('Transactions')),
      array('data' => t('Items Sold')),
      array('data' => t('Unit per Trans (UPT)')),
      array('data' => t('$ Per Transaction (DPT)')),
  );

  $query = db_select('commerce_pos_transaction', 't')
    ->extend('PagerDefault')
    ->extend('TableSort');

  $query->fields('t', array('order_id', 'completed', 'uid', 'type'));
  $query->addField('t', 'cashier', 'cashier_id');

  // Cashier data
  // We left join because legacy transactions may not have a cashier.
  $query->leftJoin('commerce_pos_cashier', 'cashier', 'cashier.cashier_id = t.cashier');
  $query->addField('cashier', 'name', 'cashier');

  // Payment transaction data.
  $query->join('commerce_payment_transaction', 'pt', 'pt.order_id = t.order_id');
  $query->fields('pt', array('transaction_id'));

  // Join order data.
  $query->join('commerce_order', 'o', 'o.order_id = t.order_id');
  $query->leftJoin('users', 'customer', 'customer.uid = o.uid');
  $query->addField('customer', 'mail', 'customer_mail');

  // Join order total data.
  $query->join('field_data_commerce_order_total', 'ot', 't.order_id = ot.entity_id AND ot.entity_type = :commerce_order', array(
    ':commerce_order' => 'commerce_order',
  ));
  $query->fields('ot', array(
    'commerce_order_total_amount',
    'commerce_order_total_currency_code',
    'commerce_order_total_data',
  ));

  // Join line item data.
  $query->join('commerce_line_item', 'l', 'l.order_id = t.order_id');
  $query->addField('l', 'quantity');

  $line_item_types = commerce_product_line_item_types();
  $query->condition('l.type', $line_item_types, 'IN');

  // Make sure line item count is aggregated.
  $query->groupBy('l.order_id');

  /* As mysql do not support limits in sub queries, create sub query
  for pagination and to limit records use a separate query.*/
  $pagination_query = db_select('commerce_pos_cashier', 'cpc')
    ->extend('PagerDefault')
    ->extend('TableSort');

  $pagination_query->fields('cpc', array('cashier_id'));

  if ($filters['results_per_page'] > 0) {
    $pagination_query->limit($filters['results_per_page']);
  }

  $limit_rows_result = $pagination_query->execute();
  if ($limit_rows_result->rowCount() > 0) {
    foreach ($limit_rows_result as $fetchCashier) {
      $cashiers_id[] = $fetchCashier->cashier_id;
    }
  }
  $query->condition('cashier.cashier_id', $cashiers_id, 'IN');

  // Set up our filters/conditions.
  $query->orderByHeader($header);
  $result = $query->execute();

  $rows = array();
  $totals = array(
    'net_sales' => 0,
    'transactions' => 0,
    'item_sold' => 0,
    'unit_per_transaction' => 0,
    'dollar_per_transaction' => 0,
  );

  if ($result->rowCount() > 0) {
    $report = sales_process_data($result);
    foreach ($report as $cashier_transactions) {
      $table_row              = array();
      $transactions           = $cashier_transactions['cashier']['transactions'];
      $currency_code          = $cashier_transactions['cashier']['currency_code'];
      $net_sales              = $cashier_transactions['transaction']['net_sales'];
      $net_sold_items         = ($cashier_transactions['transaction']['purchase_count'] - $cashier_transactions['transaction']['return_count']);
      $unit_per_transaction   = round(($net_sold_items / $transactions), 2);
      $dollar_per_transaction = ($net_sales / $transactions);
      $table_row[]            = $cashier_transactions['cashier']['id'];
      $table_row[]            = $cashier_transactions['cashier']['name'];
      $table_row[]            = commerce_currency_format($net_sales, $currency_code);
      $table_row[]            = $transactions;
      $table_row[]            = $net_sold_items;
      // Unit per Trans (UPT)
      $table_row[] = $unit_per_transaction;
      $table_row[] = commerce_currency_format($dollar_per_transaction, $currency_code);
      // Table feet with total.
      $totals['net_sales'] += $net_sales;
      $totals['transactions'] += $transactions;
      $totals['item_sold'] += $net_sold_items;
      $totals['unit_per_transaction'] += $unit_per_transaction;
      $totals['dollar_per_transaction'] += $dollar_per_transaction;
      $rows[] = array(
        'data' => $table_row,
        'class' => array(),
      );
    }

    $rows[] = array(
      '',
      '',
      commerce_currency_format($totals['net_sales'], $currency_code),
      $totals['transactions'],
      $totals['item_sold'],
      $totals['unit_per_transaction'],
      commerce_currency_format($totals['dollar_per_transaction'], $currency_code),
    );
  }
  $table = array(
    '#theme' => 'table',
    '#rows' => $rows,
    '#header' => $header,
    '#empty'  => t('No transactions found'),
    '#attached' => array(
      'js' => array(
        drupal_get_path('module', 'commerce_pos_report') . '/js/commerce_pos_report.sorter.js',
      ),
      'library' => array(
          array('system', 'drupal.ajax'),
      ),
    ),
  );
  return $table;
}

/**
 * Callback for the Sales form/report.
 */
function commerce_pos_report_sales_report($form, &$form_state) {
  global $user;
  $form['#tree'] = TRUE;
  $form['#attributes']['class'][] = 'element-form-report';
  $form['header'] = array(
    '#markup' => theme('commerce_pos_header', array('account' => $user)),
  );

  $query_filter_defaults = array(
    'results_per_page' => COMMERCE_POS_REPORT_SALES_REPORT_DEFAULT_ITEMS_PER_PAGE,
  );

  $query_params = drupal_get_query_parameters() + $query_filter_defaults;

  $form['filters'] = array(
    '#type' => 'container',
  );

  if (!isset($form_state['results_container_id'])) {
    $form_state['results_container_id'] = 'commerce-pos-report-sale-container';
  }

  $js_settings = array(
    'commercePosReport' => array(
      'cssUrl' => url(drupal_get_path('module', 'commerce_pos_report') . '/css/commerce_pos_report_receipt.css', array(
        'absolute' => TRUE,
      )),
    ),
  );

  $form['#attached']['libraries_load'][] = array('jquery-print');
  $form['#attached']['css'][] = drupal_get_path('module', 'commerce_pos') . '/css/commerce_pos_style.css';
  $form['#attached']['css'][] = drupal_get_path('module', 'commerce_pos_report') . '/css/commerce_pos_report.css';
  $form['#attached']['js'][] = drupal_get_path('module', 'commerce_pos_report') . '/js/commerce_pos_report.sorter.js';
  $form['#attached']['js'][] = array(
    'type' => 'setting',
    'data' => $js_settings,
  );
  $form['report-options'] = array(
    '#theme' => 'commerce_pos_report_options',
  );
  $form['options'] = array(
    '#type' => 'container',
    '#id' => 'commerce-pos-report-journal-role-options-container',
  );
  $form['options']['results_per_page'] = array(
    '#type' => 'select',
    '#title' => t('Items per page'),
    '#options' => array(
      25 => '25',
      50 => '50',
      100 => '100',
      500 => '500',
      -1 => t('all'),
    ),
    '#default_value' => $query_params['results_per_page'],
    '#attributes' => array(
      'class' => array('commerce-pos-report-journal-role-filter'),
    ),
  );
  $form['results'] = array(
    '#type' => 'container',
    '#id' => $form_state['results_container_id'],
    '#attributes' => array(
      'class' => array(
        'report-table-theme',
      ),
    ),
  );
  $form['results']['table'] = commerce_pos_report_build_sales_report_table($query_params);
  // Pager has to come after we've build the journal role table.
  $form['options']['pager'] = array('#theme' => 'pager');
  $form['results']['pager'] = array('#theme' => 'pager');

  $form['submit'] = array(
    '#value' => t('Submit'),
    '#type' => 'submit',
    '#weight' => -50,
    '#attributes' => array(
      'class' => array('element-invisible', 'commerce-pos-report-journal-role-submit'),
    ),
  );

  return $form;
}

/**
 * Submit handler for the journal role report form.
 */
function commerce_pos_report_sales_report_submit($form, &$form_state) {
  $query_params = array_merge(drupal_get_query_parameters(), $form_state['values']['options']);
  $form_state['redirect'] = array(current_path(), array('query' => $query_params));
}

/**
 * Helper function for Sales table to process data.
 */
function sales_process_data($result) {
  $table_row = array();
  foreach ($result as $row) {
    $return_count = 0;
    $purchase_count = 0;
    $transactions = 1;
    $cashier_id = $row->cashier_id;

    if (!isset($table_row[$cashier_id])) {
      $table_row[$cashier_id] = array(
        'transaction' => array(
          'net_sales' => 0,
          'purchase_count' => 0,
          'return_count' => 0,
        ),
        'cashier' => array(
          'transactions' => 0,
        ),
      );
    }

    switch ($row->type) {
      case CommercePosService::TRANSACTION_TYPE_RETURN:
        $return_count = $row->quantity;
        // Order total.
        $table_row[$cashier_id]['transaction']['net_sales'] -= $row->commerce_order_total_amount;
        break;

      default:
        $purchase_count = $row->quantity;
        // Order total.
        $table_row[$cashier_id]['transaction']['net_sales'] += $row->commerce_order_total_amount;
        break;
    }

    // Build order ID link.
    $table_row[$cashier_id]['cashier']['id'] = $cashier_id;
    // Cashier.
    $table_row[$cashier_id]['cashier']['name'] = $row->cashier;
    // Number of transactions.
    $table_row[$cashier_id]['cashier']['transactions'] += $transactions;
    // Purchased.
    $table_row[$cashier_id]['transaction']['purchase_count'] += $purchase_count;
    // Returned.
    $table_row[$cashier_id]['transaction']['return_count'] += $return_count;

    // Discount flag.
    $table_row[$cashier_id]['cashier']['currency_code'] = $row->commerce_order_total_currency_code;

  }
  return $table_row;
}
