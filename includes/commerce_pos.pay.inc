<?php

/**
 * @file
 * commerce_pos.pay.inc
 */

/**
 * Callback for the POS "pay" form.
 */
function commerce_pos_pay($form, &$form_state) {
  global $user;

  $payment_methods = commerce_pos_get_payment_methods();

  $form['#prefix'] = '<div id="commerce-pos-pay-form-wrapper">';
  $form['#suffix'] = '</div>';
  $form_ajax = array(
    'wrapper' => 'commerce-pos-pay-form-wrapper',
    'callback' => 'commerce_pos_pay_wrapper_js',
  );

  if (empty($payment_methods)) {
    drupal_set_message(t('No payment methods have been configured for the POS!'), 'error');
  }

  if (!isset($form_state['transaction'])) {
    if ($transaction = CommercePosService::getCurrentTransaction(CommercePosService::TRANSACTION_TYPE_SALE, $user->uid)) {
      $order_wrapper = $transaction->getOrderWrapper();

      if ($order_wrapper && $order_wrapper->status->value() == 'commerce_pos_in_progress' ) {
        $form_state['transaction'] = $transaction;
        $form_state['order_wrapper'] = $order_wrapper;
      }
    }
  }

  if (!isset($form_state['transaction'])) {
    // No valid transaction found, go back to the sale form.
    drupal_goto('pos/sale');
  }

  $order_balance = commerce_payment_order_balance($form_state['order_wrapper']->value());

  $line_item_count = 0;

  foreach ($form_state['order_wrapper']->commerce_line_items as $line_item_wrapper) {
    if (in_array($line_item_wrapper->type->value(), commerce_product_line_item_types())) {
      $line_item_count ++;
    }
  }

  $items_link = l(format_plural($line_item_count, '1 Item', '@count Items'), '');

  $form['summary'] = array(
    '#markup' => t('Payment - !items - Order #@order_id', array(
      '!items' => $items_link,
      '@order_id' => $form_state['order_wrapper']->order_id->value(),
    )),
  );

  $form['edit_order'] = array(
    '#markup' => l(t('Edit Order'), 'pos/sale', array(
      'attributes' => array(
        'class' => array('commerce-pos-sale-pay-edit-order'),
      ),
    )),
  );

  $form['payment_options'] = array(
    '#type' => 'container',
    '#tree' => TRUE,
  );

  $payment_ajax = array(
    'wrapper' => 'commerce-pos-sale-keypad-wrapper',
    'callback' => 'commerce_pos_pay_keypad_js',
    'effect' => 'fade',
  );

  foreach ($payment_methods as $payment_method) {
    $form['payment_options'][$payment_method['id']] = array(
      '#type' => 'button',
      '#value' => $payment_method['title'],
      '#name' => 'commerce-pos-payment-method-' . $payment_method['id'],
      '#ajax' => $payment_ajax,
      '#payment_method_id' => $payment_method['id'],
      '#disabled' => $order_balance['amount'] <= 0,
      '#limit_validation_errors' => array(),
    );
  }

  $form['keypad'] = array(
    '#type' => 'container',
    '#id' => 'commerce-pos-sale-keypad-wrapper',
    '#tree' => TRUE,
    '#theme' => 'commerce_pos_keypad',
  );

  if (!empty($form_state['triggering_element']['#payment_method_id'])) {
    $method_id = $form_state['triggering_element']['#payment_method_id'];

    $form['keypad']['amount'] = array(
      '#type' => 'textfield',
      '#title' => t('Enter @title Amount', array(
        '@title' => $payment_methods[$method_id]['title'],
      )),
      '#required' => TRUE,
    );

    $form['keypad']['add'] = array(
      '#type' => 'submit',
      '#value' => t('add'),
      '#name' => 'commerce-pos-pay-keypad-add',
      '#validate' => array('commerce_pos_pay_add_payment_validate'),
      '#submit' => array('commerce_pos_pay_add_payment_submit'),
      '#ajax' => $form_ajax,
      '#payment_method_id' => $method_id,
    );
  }

  $form['balance'] = array(
    '#type' => 'container',
    '#id' => 'commerce-pos-pay-balance-wrapper',
  );

  $form['balance']['summary'] = array(
    '#markup' => commerce_pos_balance_summary($form_state['order_wrapper']->value()),
  );

  $form['balance']['actions'] = array(
    '#type' => 'actions',
  );

  $form['balance']['actions']['finish'] = array(
    '#type' => 'submit',
    '#value' => t('Finish'),
    '#disabled' => $order_balance['amount'] > 0,
    '#validate' => array('commerce_pos_pay_validate'),
    '#submit' => array('commerce_pos_pay_finish'),
  );

  $form['balance']['actions']['park'] = array(
    '#type' => 'button',
    '#value' => t('Park'),
  );

  $form['balance']['actions']['void'] = array(
    '#type' => 'submit',
    '#value' => t('Void'),
    '#submit' => array('commerce_pos_pay_void_submit'),
  );

  $form['#attached']['css'][] = drupal_get_path('module', 'commerce_pos') . '/css/commerce_pos.css';
  $form['#attached']['js'][] = drupal_get_path('module', 'commerce_pos') . '/js/commerce_pos.pay.js';

  return $form;
}

/**
 * AJAX callback for the Pay form keypad.
 */
function commerce_pos_pay_keypad_js($form, &$form_state) {
  return $form['keypad'];
}

/**
 * AJAX callback for the Pay form's wrapper.
 */
function commerce_pos_pay_wrapper_js($form, &$form_state) {
  return $form;
}

/**
 * Validation handler for the "Add" payment button.
 */
function commerce_pos_pay_add_payment_validate($form, &$form_state) {
  if (!is_numeric($form_state['values']['keypad']['amount'])) {
    form_set_error('keypad][amount', t('Payment amount must be a number.'));
  }
}

/**
 * Submit handler for the "Add" payment button.
 */
function commerce_pos_pay_add_payment_submit($form, &$form_state) {
  $payment_method = commerce_payment_method_load($form_state['triggering_element']['#payment_method_id']);
  $order_wrapper = $form_state['transaction']->getOrderWrapper();
  $transaction = commerce_payment_transaction_new($form_state['triggering_element']['#payment_method_id'], $order_wrapper->order_id->value());
  $transaction->instance_id = $payment_method['method_id'] . '|commerce_pos';
  $transaction->amount = $form_state['values']['keypad']['amount'] * 100;
  $transaction->currency_code = $order_wrapper->commerce_order_total->currency_code->value();
  $transaction->status = COMMERCE_PAYMENT_STATUS_SUCCESS;
  $transaction->message = '';
  commerce_payment_transaction_save($transaction);

  $form_state['rebuild'] = TRUE;
}

/**
 * Submit handler for the "Void" button.
 */
function commerce_pos_pay_void_submit($form, &$form_state) {
  $form_state['transaction']->void();
  $form_state['redirect'] = 'pos/sale';
}

/**
 * Validation handler for the pay form's "Finish" button.
 */
function commerce_pos_pay_validate($form, &$form_state) {

}

/**
 * Submit handler for the pay form's "Finish" button.
 */
function commerce_pos_pay_finish($form, &$form_state) {
  // @TODO: need to mark the order as completed, add a "change" line item if
  // necessary, print the receipt, and redirect back to the "Sale" form.
  // Also need to create and/or associate the order with a customer.
  $form_state['transaction']->doAction('completeTransaction');

  drupal_set_message(t('Transaction completed'));
  $form_state['redirect'] = 'pos/sale';
}
