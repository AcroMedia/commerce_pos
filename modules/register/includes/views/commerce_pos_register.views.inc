<?php

/**
 * @file
 * Views data and handler definitions.
 */

/**
 * Implements hook_views_data_alter().
 */
function commerce_pos_register_views_data_alter(&$data) {
  $data['commerce_pos_transaction']['register_id'] = array(
    'title' => t('Register ID'),
    'description' => t('The POS register the transaction was performed in.'),
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
    'relationship' => array(
      'base' => 'commerce_pos_register',
      'base field' => 'register_id',
      'handler' => 'views_handler_relationship',
      'label' => t('Transaction register'),
      'title' => t('Transaction register'),
      'help' => t('The POS register that the transaction was performed in.'),
    ),
  );
}
