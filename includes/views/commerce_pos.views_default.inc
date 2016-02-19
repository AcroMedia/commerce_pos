<?php
/**
 * @file
 * commerce_pos.views_default.inc
 */

/**
 * Implements hook_views_default_views().
 */
function commerce_pos_views_default_views() {
  $export = array();

  $view = new view();
  $view->name = 'pos_balance_summary';
  $view->description = '';
  $view->tag = 'default';
  $view->base_table = 'commerce_payment_transaction';
  $view->human_name = 'POS Balance Summary';
  $view->core = 7;
  $view->api_version = '3.0';
  $view->disabled = FALSE; /* Edit this to true to make a default view disabled initially */

  /* Display: Master */
  $handler = $view->new_display('default', 'Master', 'default');
  $handler->display->display_options['use_more_always'] = FALSE;
  $handler->display->display_options['access']['type'] = 'none';
  $handler->display->display_options['cache']['type'] = 'none';
  $handler->display->display_options['query']['type'] = 'views_query';
  $handler->display->display_options['exposed_form']['type'] = 'basic';
  $handler->display->display_options['pager']['type'] = 'full';
  $handler->display->display_options['style_plugin'] = 'table';
  $handler->display->display_options['style_options']['columns'] = array(
    'payment_method' => 'payment_method',
    'amount' => 'amount',
    'nothing' => 'nothing',
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
    'nothing' => array(
      'align' => '',
      'separator' => '',
      'empty_column' => 0,
    ),
  );
  /* Footer: Commerce Payment Transaction: POS Balance Summary */
  $handler->display->display_options['footer']['commerce_pos_balance_summary']['id'] = 'commerce_pos_balance_summary';
  $handler->display->display_options['footer']['commerce_pos_balance_summary']['table'] = 'commerce_payment_transaction';
  $handler->display->display_options['footer']['commerce_pos_balance_summary']['field'] = 'commerce_pos_balance_summary';
  $handler->display->display_options['footer']['commerce_pos_balance_summary']['label'] = 'Balance';
  /* Field: Commerce Payment Transaction: Transaction ID */
  $handler->display->display_options['fields']['transaction_id']['id'] = 'transaction_id';
  $handler->display->display_options['fields']['transaction_id']['table'] = 'commerce_payment_transaction';
  $handler->display->display_options['fields']['transaction_id']['field'] = 'transaction_id';
  $handler->display->display_options['fields']['transaction_id']['label'] = '';
  $handler->display->display_options['fields']['transaction_id']['exclude'] = TRUE;
  $handler->display->display_options['fields']['transaction_id']['element_label_colon'] = FALSE;
  /* Field: Commerce Payment Transaction: Payment method */
  $handler->display->display_options['fields']['payment_method']['id'] = 'payment_method';
  $handler->display->display_options['fields']['payment_method']['table'] = 'commerce_payment_transaction';
  $handler->display->display_options['fields']['payment_method']['field'] = 'payment_method';
  $handler->display->display_options['fields']['payment_method']['label'] = '';
  $handler->display->display_options['fields']['payment_method']['element_label_colon'] = FALSE;
  /* Field: Global: Custom text */
  $handler->display->display_options['fields']['nothing']['id'] = 'nothing';
  $handler->display->display_options['fields']['nothing']['table'] = 'views';
  $handler->display->display_options['fields']['nothing']['field'] = 'nothing';
  $handler->display->display_options['fields']['nothing']['label'] = '';
  $handler->display->display_options['fields']['nothing']['alter']['text'] = '<a href="#" class="commerce-pos-remove-payment button-link" data-transaction-id="[transaction_id]">remove</a>';
  $handler->display->display_options['fields']['nothing']['element_label_colon'] = FALSE;
  /* Field: Commerce Payment Transaction: Amount */
  $handler->display->display_options['fields']['amount']['id'] = 'amount';
  $handler->display->display_options['fields']['amount']['table'] = 'commerce_payment_transaction';
  $handler->display->display_options['fields']['amount']['field'] = 'amount';
  $handler->display->display_options['fields']['amount']['label'] = '';
  $handler->display->display_options['fields']['amount']['element_label_colon'] = FALSE;
  /* Contextual filter: Commerce Payment Transaction: Order ID */
  $handler->display->display_options['arguments']['order_id']['id'] = 'order_id';
  $handler->display->display_options['arguments']['order_id']['table'] = 'commerce_payment_transaction';
  $handler->display->display_options['arguments']['order_id']['field'] = 'order_id';
  $handler->display->display_options['arguments']['order_id']['default_action'] = 'not found';
  $handler->display->display_options['arguments']['order_id']['default_argument_type'] = 'fixed';
  $handler->display->display_options['arguments']['order_id']['summary']['number_of_records'] = '0';
  $handler->display->display_options['arguments']['order_id']['summary']['format'] = 'default_summary';
  $handler->display->display_options['arguments']['order_id']['summary_options']['items_per_page'] = '25';

  /* Display: Block */
  $handler = $view->new_display('block', 'Block', 'block_1');
  $export['pos_balance_summary'] = $view;

  return $export;
}
