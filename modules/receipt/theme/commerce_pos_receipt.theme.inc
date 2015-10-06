<?php

/**
 * Theme callbacks and preprocess hooks for commerce_pos_receipt.
 */

/**
 * Implements template_preprocess_pos_receipt().
 */
function commerce_pos_receipt_preprocess_commerce_pos_receipt(&$variables) {

  /* @var CommercePosTransaction $transaction */
  $transaction = &$variables['transaction'];
  $order_wrapper = $transaction->getOrderWrapper();

  /*$pos = empty($variables['pos']) ? CommercePOS::instance() : $variables['pos'];*/

  $header = variable_get('commerce_pos_receipt_header', array('value' => NULL, 'format' => NULL));

  $variables['receipt_header'] = array(
    'message' => array(
      '#markup' => check_markup($header['value'], $header['format'])
    )
  );

  $footer = variable_get('commerce_pos_receipt_footer', array('value' => NULL, 'format' => NULL));

  $variables['receipt_footer'] = array(
    'message' => array(
      '#markup' => check_markup($footer['value'], $footer['format']),
    )
  );

  $variables['receipt_date'] = format_date(REQUEST_TIME);

  drupal_add_css(drupal_get_path('module', 'commerce_pos_receipt') . '/css/commerce_pos_receipt.css', array('media' => 'print'));

  /*$order_pane = new POSPane_Order('order', 'Order');*/
  // Using an empty interface ensures we won't have buttons on our receipt.
  /*$variables['receipt_body'] = $order_pane->build($pos, new POS_Interface($pos, array(), array()));*/

  $variables['receipt_body'] = 'I am the receipt body';
}
