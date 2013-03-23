<?php

/**
 * Implements hook_views_data_alter().
 */
function commerce_pos_views_data_alter(&$data) {
  ctools_include('plugins');
  $registry = POS_Command_Registry::create();

  if($command = $registry->getCommand('set_user')) {
    $data['users']['pos_set_user'] = array(
      'title' => t('Set POS Order User'),
      'group' => t('POS'),
      'help' => t('Set the user for the current POS order'),
      'real field' => 'uid',
      'field' => array(
        'handler' => 'commerce_pos_handler_field_pos_command',
        'command' => $command,
        'click sortable' => FALSE,
      )
    );
  }

  if($command = $registry->getCommand('load_order')) {
    $data['commerce_order']['pos_load_order'] = array(
      'title' => t('Load POS Order'),
      'group' => t('POS'),
      'help' => t('Load the order onto the POS'),
      'real field' => 'order_id',
      'field' => array(
        'handler' => 'commerce_pos_handler_field_pos_command',
        'command' => $command,
      )
    );
  }

  if($command = $registry->getCommand('add_product')) {
    $data['commerce_product']['pos_add_product'] = array(
      'title' => t('Add Product'),
      'group' => t('POS'),
      'help' => t('Add Product to POS Order'),
      'real field' => 'sku',
      'field' => array(
        'handler' => 'commerce_pos_handler_field_pos_command',
        'command' => $command,
      )
    );
  }

  if($command = $registry->getCommand('price_check')) {
    $data['commerce_product']['pos_price_check'] = array(
      'title' => t('Price Check'),
      'group' => t('POS'),
      'help' => t('Run a price check'),
      'real field' => 'sku',
      'field' => array(
        'handler' => 'commerce_pos_handler_field_pos_command',
        'command' => $command,
      )
    );
  }
}