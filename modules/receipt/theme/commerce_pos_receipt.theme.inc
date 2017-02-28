<?php

/**
 * @file
 * Theme callbacks and preprocess hooks for commerce_pos_receipt.
 */

/**
 * Implements template_preprocess_pos_receipt().
 */
function commerce_pos_receipt_preprocess_commerce_pos_receipt(&$variables) {

  /* @var CommercePosTransaction $transaction */
  $transaction = &$variables['transaction'];
  $order_wrapper = $transaction->getOrderWrapper();

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

  drupal_add_css(drupal_get_path('module', 'commerce_pos_receipt') . '/css/commerce_pos_receipt.css', array('media' => 'print'));
  $order_value = $order_wrapper->value();
  $variables['receipt_body'] = commerce_pos_receipt_build_receipt_body($order_value);
}

/**
 * Theme callback for a table with a POS transaction's payments.
 */
function theme_commerce_pos_receipt_payments_table(&$variables) {
  $payments = commerce_payment_transaction_load_multiple(array(), array('order_id' => $variables['order_id']));
  $price_prefix = $variables['return'] ? '(' : '';
  $price_suffix = $variables['return'] ? ')' : '';
  $payment_rows = array();
  $visible_statuses = variable_get('commerce_pos_receipt_visible_payment_status', array('success', 'void'));

  foreach ($payments as $payment) {
    if (in_array($payment->status, $visible_statuses)) {
      $status = commerce_payment_transaction_status_load($payment->status);
      $method_name = '<span class="payment-name">' . commerce_payment_method_get_title('title', $payment->payment_method) . '</span>';
      $row_classes = array('payment');

      if (!$status['total']) {
        $method_name .= '&nbsp;-&nbsp;<span class="commerce-pos-receipt-void-message">' . $status['title'] . '</span>';
        $row_classes[] = 'payment-no-total';
      }

      $row = array(
        array(
          'data' => $method_name,
          'class' => array('component-label'),
        ),
        array(
          'data' => $price_prefix . commerce_currency_format($payment->amount, $payment->currency_code, $payment) . $price_suffix,
          'class' => array('component-total'),
        ),
      );
      $payment_rows[] = array(
        'data' => $row,
        'class' => $row_classes,
      );
    }
  }

  return theme('table', array('rows' => $payment_rows, 'attributes' => array('class' => array('commerce-pos-order'))));
}
