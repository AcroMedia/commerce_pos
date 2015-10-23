<?php

/**
 * Implements hook_views_data().
 */
function commerce_pos_views_data() {
  $data['commerce_pos_transaction']['table']['group'] = t('Commerce POS Transaction');

  $data['commerce_pos_transaction']['table']['join'] = array(
    'user' => array(
      'left_field' => 'uid',
      'field' => 'uid',
    ),
    'commerce_order' => array(
      'left_field' => 'order_id',
      'field' => 'order_id',
    ),
  );

  $data['commerce_pos_transaction']['transaction_id'] = array(
    'title' => t('Transaction ID'),
    'field' => array(
      'handler' => 'views_handler_field_numeric',
      'click sortable' => TRUE,
    ),
    'filter' => array(
      'handler' => 'views_handler_filter_numeric',
    ),
    'argument' => array(
      'handler' => 'views_handler_argument_numeric',
    ),
  );

  $data['commerce_pos_transaction']['order_id'] = array(
    'title' => t('Transaction order ID'),
    'field' => array(
      'handler' => 'views_handler_field_numeric',
      'click sortable' => TRUE,
    ),
    'filter' => array(
      'handler' => 'views_handler_filter_numeric',
    ),
    'argument' => array(
      'handler' => 'views_handler_argument_numeric',
    ),
  );

  $data['commerce_pos_transaction']['uid'] = array(
    'title' => t('Transaction owner'),
    'field' => array(
      'handler' => 'views_handler_field_numeric',
      'click sortable' => TRUE,
    ),
    'filter' => array(
      'handler' => 'views_handler_filter_numeric',
    ),
  );

  $data['commerce_pos_transaction']['created'] = array(
    'title' => t('Created date'),
    'help' => t('The date the POS transaction was created'),
    'field' => array(
      'handler' => 'views_handler_field_date',
      'click sortable' => TRUE,
    ),
    'sort' => array(
      'handler' => 'views_handler_sort_date',
    ),
    'filter' => array(
      'handler' => 'date_views_filter_handler',
      'is date' => TRUE,
    ),
    'argument' => array(
      'handler' => 'date_views_argument_handler',
      'is date' => TRUE,
    ),
  );

  return $data;
}

/**
 * Implements hook_views_data_alter()
 */
function commerce_pos_views_data_alter(&$data) {
  $data['commerce_payment_transaction']['commerce_pos_balance_summary'] = array(
    'title' => t('POS Balance Summary'),
    'help' => t('Display POS transaction total and order balance information for all transactions in the View.'),
    'area' => array(
      'handler' => 'commerce_pos_handler_area_balance',
    ),
  );
}
