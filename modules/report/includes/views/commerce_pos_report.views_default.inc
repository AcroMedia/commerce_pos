<?php

/**
 * @file
 * commerce_pos_report.views_default.inc
 */

/**
 * Implements hook_views_default_views().
 */
function commerce_pos_report_views_default_views() {
  $export = array();

  $view = new view();
  $view->name = 'commerce_pos_transaction_report';
  $view->description = '';
  $view->tag = 'default';
  $view->base_table = 'commerce_order';
  $view->human_name = 'POS Transaction Report';
  $view->core = 7;
  $view->api_version = '3.0';
  $view->disabled = FALSE; /* Edit this to true to make a default view disabled initially */

  /* Display: Master */
  $handler = $view->new_display('default', 'Master', 'default');
  $handler->display->display_options['use_more_always'] = FALSE;
  $handler->display->display_options['group_by'] = TRUE;
  $handler->display->display_options['access']['type'] = 'none';
  $handler->display->display_options['cache']['type'] = 'none';
  $handler->display->display_options['query']['type'] = 'views_query';
  $handler->display->display_options['exposed_form']['type'] = 'basic';
  $handler->display->display_options['pager']['type'] = 'full';
  $handler->display->display_options['style_plugin'] = 'table';
  $handler->display->display_options['style_options']['grouping'] = array(
    0 => array(
      'field' => 'payment_method',
      'rendered' => 1,
      'rendered_strip' => 0,
    ),
  );
  $handler->display->display_options['style_options']['columns'] = array(
    'payment_method' => 'payment_method',
    'amount' => 'amount',
  );
  $handler->display->display_options['style_options']['default'] = '-1';
  $handler->display->display_options['style_options']['info'] = array(
    'payment_method' => array(
      'sortable' => 0,
      'default_sort_order' => 'asc',
      'align' => '',
      'separator' => '',
      'empty_column' => 0,
    ),
    'amount' => array(
      'sortable' => 0,
      'default_sort_order' => 'asc',
      'align' => '',
      'separator' => '',
      'empty_column' => 0,
    ),
  );
  /* No results behavior: Global: Text area */
  $handler->display->display_options['empty']['area']['id'] = 'area';
  $handler->display->display_options['empty']['area']['table'] = 'views';
  $handler->display->display_options['empty']['area']['field'] = 'area';
  $handler->display->display_options['empty']['area']['empty'] = TRUE;
  $handler->display->display_options['empty']['area']['content'] = 'No transactions found for the selected date.';
  $handler->display->display_options['empty']['area']['format'] = 'plain_text';
  /* Relationship: Commerce Order: Payment Transaction */
  $handler->display->display_options['relationships']['payment_transaction']['id'] = 'payment_transaction';
  $handler->display->display_options['relationships']['payment_transaction']['table'] = 'commerce_order';
  $handler->display->display_options['relationships']['payment_transaction']['field'] = 'payment_transaction';
  $handler->display->display_options['relationships']['payment_transaction']['required'] = TRUE;
  /* Field: COUNT(Commerce Payment Transaction: Payment method) */
  $handler->display->display_options['fields']['payment_method_1']['id'] = 'payment_method_1';
  $handler->display->display_options['fields']['payment_method_1']['table'] = 'commerce_payment_transaction';
  $handler->display->display_options['fields']['payment_method_1']['field'] = 'payment_method';
  $handler->display->display_options['fields']['payment_method_1']['relationship'] = 'payment_transaction';
  $handler->display->display_options['fields']['payment_method_1']['group_type'] = 'count';
  $handler->display->display_options['fields']['payment_method_1']['label'] = 'Total payments';
  /* Field: Commerce Payment Transaction: Payment method */
  $handler->display->display_options['fields']['payment_method']['id'] = 'payment_method';
  $handler->display->display_options['fields']['payment_method']['table'] = 'commerce_payment_transaction';
  $handler->display->display_options['fields']['payment_method']['field'] = 'payment_method';
  $handler->display->display_options['fields']['payment_method']['relationship'] = 'payment_transaction';
  $handler->display->display_options['fields']['payment_method']['label'] = '';
  $handler->display->display_options['fields']['payment_method']['exclude'] = TRUE;
  $handler->display->display_options['fields']['payment_method']['element_label_colon'] = FALSE;
  /* Field: SUM(Commerce Payment Transaction: Amount) */
  $handler->display->display_options['fields']['amount']['id'] = 'amount';
  $handler->display->display_options['fields']['amount']['table'] = 'commerce_payment_transaction';
  $handler->display->display_options['fields']['amount']['field'] = 'amount';
  $handler->display->display_options['fields']['amount']['relationship'] = 'payment_transaction';
  $handler->display->display_options['fields']['amount']['group_type'] = 'sum';
  /* Sort criterion: Commerce Payment Transaction: Payment method */
  $handler->display->display_options['sorts']['payment_method']['id'] = 'payment_method';
  $handler->display->display_options['sorts']['payment_method']['table'] = 'commerce_payment_transaction';
  $handler->display->display_options['sorts']['payment_method']['field'] = 'payment_method';
  $handler->display->display_options['sorts']['payment_method']['relationship'] = 'payment_transaction';
  /* Contextual filter: Commerce POS Transaction: Completed date */
  $handler->display->display_options['arguments']['completed']['id'] = 'completed';
  $handler->display->display_options['arguments']['completed']['table'] = 'commerce_pos_transaction';
  $handler->display->display_options['arguments']['completed']['field'] = 'completed';
  $handler->display->display_options['arguments']['completed']['default_action'] = 'not found';
  $handler->display->display_options['arguments']['completed']['default_argument_type'] = 'date';
  $handler->display->display_options['arguments']['completed']['year_range'] = '-10:+10';
  $handler->display->display_options['arguments']['completed']['granularity'] = 'day';
  $handler->display->display_options['arguments']['completed']['date_fields'] = array(
    'commerce_pos_transaction.completed' => 'commerce_pos_transaction.completed',
  );
  /* Contextual filter: Commerce POS Transaction: Transaction type */
  $handler->display->display_options['arguments']['type']['id'] = 'type';
  $handler->display->display_options['arguments']['type']['table'] = 'commerce_pos_transaction';
  $handler->display->display_options['arguments']['type']['field'] = 'type';
  $handler->display->display_options['arguments']['type']['default_argument_type'] = 'fixed';
  $handler->display->display_options['arguments']['type']['summary']['number_of_records'] = '0';
  $handler->display->display_options['arguments']['type']['summary']['format'] = 'default_summary';
  $handler->display->display_options['arguments']['type']['summary_options']['items_per_page'] = '25';
  $handler->display->display_options['arguments']['type']['limit'] = '0';
  /* Contextual filter: Commerce POS Transaction: Location ID */
  $handler->display->display_options['arguments']['location_id']['id'] = 'location_id';
  $handler->display->display_options['arguments']['location_id']['table'] = 'commerce_pos_transaction';
  $handler->display->display_options['arguments']['location_id']['field'] = 'location_id';
  $handler->display->display_options['arguments']['location_id']['default_argument_type'] = 'fixed';
  $handler->display->display_options['arguments']['location_id']['summary']['number_of_records'] = '0';
  $handler->display->display_options['arguments']['location_id']['summary']['format'] = 'default_summary';
  $handler->display->display_options['arguments']['location_id']['summary_options']['items_per_page'] = '25';
  /* Filter criterion: Commerce Order: Order type */
  $handler->display->display_options['filters']['type']['id'] = 'type';
  $handler->display->display_options['filters']['type']['table'] = 'commerce_order';
  $handler->display->display_options['filters']['type']['field'] = 'type';
  $handler->display->display_options['filters']['type']['value'] = array(
    'commerce_order' => 'commerce_order',
  );
  /* Filter criterion: Commerce POS Transaction: Transaction ID */
  $handler->display->display_options['filters']['transaction_id']['id'] = 'transaction_id';
  $handler->display->display_options['filters']['transaction_id']['table'] = 'commerce_pos_transaction';
  $handler->display->display_options['filters']['transaction_id']['field'] = 'transaction_id';
  $handler->display->display_options['filters']['transaction_id']['operator'] = '>';
  $handler->display->display_options['filters']['transaction_id']['value']['value'] = '0';

  /* Display: Block */
  $handler = $view->new_display('block', 'Block', 'report_main');
  $export['commerce_pos_transaction_report'] = $view;

  return $export;
}
