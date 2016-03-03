<?php

/**
 * @file
 * Views data and handler definitions.
 */

/**
 * Implements hook_views_data_alter().
 */
function commerce_pos_location_views_data_alter(&$data) {
  $data['commerce_pos_transaction']['location_id'] = array(
    'title' => t('Location ID'),
    'description' => t('The POS location the transaction was performed in.'),
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
      'base' => 'commerce_pos_location',
      'base field' => 'location_id',
      'handler' => 'views_handler_relationship',
      'label' => t('Transaction location'),
      'title' => t('Transaction location'),
      'help' => t('The POS location that the transaction was performed in.'),
    ),
  );
}
