<?php
/**
 * @file
 * commerce_pos_messages.views_default.inc
 */

/**
 * Implements hook_views_default_views().
 */
function commerce_pos_messages_views_default_views() {
  $export = array();

  $view = new view();
  $view->name = 'commerce_pos_messages';
  $view->description = '';
  $view->tag = 'default';
  $view->base_table = 'message';
  $view->human_name = 'Commerce POS Messages';
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
  $handler->display->display_options['pager']['type'] = 'none';
  $handler->display->display_options['style_plugin'] = 'default';
  $handler->display->display_options['row_plugin'] = 'fields';
  $handler->display->display_options['row_options']['inline'] = array(
    'timestamp' => 'timestamp',
    'name' => 'name',
  );
  /* Relationship: Message: User uid */
  $handler->display->display_options['relationships']['user']['id'] = 'user';
  $handler->display->display_options['relationships']['user']['table'] = 'message';
  $handler->display->display_options['relationships']['user']['field'] = 'user';
  /* Field: Message: Message */
  $handler->display->display_options['fields']['message_commerce_body']['id'] = 'message_commerce_body';
  $handler->display->display_options['fields']['message_commerce_body']['table'] = 'field_data_message_commerce_body';
  $handler->display->display_options['fields']['message_commerce_body']['field'] = 'message_commerce_body';
  $handler->display->display_options['fields']['message_commerce_body']['label'] = '';
  $handler->display->display_options['fields']['message_commerce_body']['element_label_colon'] = FALSE;
  /* Field: User: Name */
  $handler->display->display_options['fields']['name']['id'] = 'name';
  $handler->display->display_options['fields']['name']['table'] = 'users';
  $handler->display->display_options['fields']['name']['field'] = 'name';
  $handler->display->display_options['fields']['name']['relationship'] = 'user';
  $handler->display->display_options['fields']['name']['label'] = 'Sales Agent';
  $handler->display->display_options['fields']['name']['link_to_user'] = FALSE;
  /* Field: Message: Timestamp */
  $handler->display->display_options['fields']['timestamp']['id'] = 'timestamp';
  $handler->display->display_options['fields']['timestamp']['table'] = 'message';
  $handler->display->display_options['fields']['timestamp']['field'] = 'timestamp';
  $handler->display->display_options['fields']['timestamp']['label'] = ' - ';
  $handler->display->display_options['fields']['timestamp']['element_label_colon'] = FALSE;
  $handler->display->display_options['fields']['timestamp']['date_format'] = 'long';
  $handler->display->display_options['fields']['timestamp']['second_date_format'] = 'long';
  /* Sort criterion: Message: Timestamp */
  $handler->display->display_options['sorts']['timestamp']['id'] = 'timestamp';
  $handler->display->display_options['sorts']['timestamp']['table'] = 'message';
  $handler->display->display_options['sorts']['timestamp']['field'] = 'timestamp';
  $handler->display->display_options['sorts']['timestamp']['order'] = 'DESC';
  /* Contextual filter: Message: Order (message_commerce_order) */
  $handler->display->display_options['arguments']['message_commerce_order_target_id']['id'] = 'message_commerce_order_target_id';
  $handler->display->display_options['arguments']['message_commerce_order_target_id']['table'] = 'field_data_message_commerce_order';
  $handler->display->display_options['arguments']['message_commerce_order_target_id']['field'] = 'message_commerce_order_target_id';
  $handler->display->display_options['arguments']['message_commerce_order_target_id']['default_action'] = 'not found';
  $handler->display->display_options['arguments']['message_commerce_order_target_id']['default_argument_type'] = 'fixed';
  $handler->display->display_options['arguments']['message_commerce_order_target_id']['summary']['number_of_records'] = '0';
  $handler->display->display_options['arguments']['message_commerce_order_target_id']['summary']['format'] = 'default_summary';
  $handler->display->display_options['arguments']['message_commerce_order_target_id']['summary_options']['items_per_page'] = '25';
  /* Filter criterion: Message: Type */
  $handler->display->display_options['filters']['type']['id'] = 'type';
  $handler->display->display_options['filters']['type']['table'] = 'message';
  $handler->display->display_options['filters']['type']['field'] = 'type';
  $handler->display->display_options['filters']['type']['value'] = array(
    'commerce_order_admin_comment' => 'commerce_order_admin_comment',
  );

  /* Display: Block */
  $handler = $view->new_display('block', 'Block', 'block');
  $export['commerce_pos_messages'] = $view;

  return $export;
}
