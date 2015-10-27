<?php

/**
 * @file
 */

/**
 * Callback for the main report form.
 */
function commerce_pos_report_main($form, &$form_state) {
  global $user;

  if (!isset($form_state['results_container_id'])) {
    $form_state['results_container_id'] = 'commerce-pos-report-results-container';
  }

  $default_date = format_date(REQUEST_TIME, 'custom', 'Y-m-d');
  $default_type = CommercePosService::TRANSACTION_TYPE_SALE;

  if (isset($form_state['input']['date_completed']) && is_array($form_state['input']['date_completed'])) {
    $date_input = $form_state['input']['date_completed'];
    $date_filter = $date_input['year'] . '-' . $date_input['month'] . '-' . $date_input['day'];
  }
  else {
    $date_filter = $default_date;
  }

  $ajax = array(
    'callback' => 'commerce_pos_report_main_results_js',
    'wrapper' => $form_state['results_container_id'],
    'effect' => 'fade',
  );

  $form['#attached']['css'][] = drupal_get_path('module', 'commerce_pos') . '/css/commerce_pos.css';

  $form['header'] = array(
    '#markup' => theme('commerce_pos_header', array('account' => $user)),
  );

  $form['filters'] = array(
    '#type' => 'container',
  );

  $type_filter = isset($form_state['input']['transaction_type']) ?
    $form_state['input']['transaction_type'] : $default_type;

  $form['filters']['date_completed'] = array(
    '#type' => 'date_select',
    '#title' => t('Transaction date'),
    '#date_format' => 'Y-m-d',
    '#default_value' => $default_date,
    '#ajax' => $ajax,
    '#element_key' => 'date_completed',
  );

  $form['filters']['transaction_type'] = array(
    '#type' => 'select',
    '#title' => t('Transaction type'),
    '#options' => CommercePosService::transactionTypes(),
    '#default_value' => $type_filter,
    '#ajax' => $ajax,
    '#element_key' => 'transaction_type',
  );

  $location_filter = 0;

  if (module_exists('commerce_pos_location')) {
    $current_location = commerce_pos_location_get_current_location();
    $location_options = commerce_pos_location_options();

    if (isset($form_state['input']['location_id'])) {
      $location_filter = $form_state['input']['location_id'];
    }
    elseif ($current_location) {
      $location_filter = $current_location;
    }
    else {
      $location_filter = key($location_options);
    }

    $form['filters']['location_id'] = array(
      '#type' => 'select',
      '#title' => t('Location'),
      '#options' => $location_options,
      '#default_value' => $location_filter,
      '#ajax' => $ajax,
    );
  }

  $form['results'] = array(
    '#type' => 'container',
    '#id' => $form_state['results_container_id'],
  );

  $form['results']['payment_transactions'] = array(
    '#markup' => views_embed_view('commerce_pos_transaction_report', 'report_main', $date_filter, $type_filter, $location_filter),
  );

  if ($order_summary = commerce_pos_report_order_summary($date_filter, $type_filter, $location_filter)) {
    if ($order_summary['total_orders']) {
      $form['results']['order_summary'] = array(
        '#type' => 'container',
      );

      $summary = &$form['results']['order_summary'];

      $summary['total_orders'] = array(
        '#markup' => '<h4>' . t('Total orders: @amount', array(
            '@amount' => $order_summary['total_orders'],
          )) . '</h4>',
      );

      $summary['summaries']['#markup'] = render($order_summary['summaries']);
    }
  }

  return $form;
}

/**
 * AJAX callback for the report filter elements.
 */
function commerce_pos_report_main_results_js($form, &$form_state) {
  return $form['results'];
}
