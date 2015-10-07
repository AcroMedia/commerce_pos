<?php

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
