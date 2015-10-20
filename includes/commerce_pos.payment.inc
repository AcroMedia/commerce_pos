<?php

/**
 * Payment functionality for POS returns and sales.
 */

/**
 * Callback for the POS sale payment form.
 */
function commerce_pos_sale_payment($form, &$form_state) {
  commerce_pos_payment($form, $form_state, CommercePosService::TRANSACTION_TYPE_SALE);
  return $form;
}

/**
 * Callback for the POS return payment form.
 */
function commerce_pos_return_payment($form, &$form_state) {
  commerce_pos_payment($form, $form_state, CommercePosService::TRANSACTION_TYPE_RETURN);
  return $form;
}

/**
 * Callback to build a POS payment form.
 */
function commerce_pos_payment(&$form, &$form_state, $transaction_type) {
  global $user;

  form_load_include($form_state, 'inc', 'commerce_pos', 'includes/commerce_pos.common');
  commerce_pos_payment_ajax_check($form, $form_state);

  $payment_methods = commerce_pos_get_payment_methods();
  $wrapper_id = 'commerce-pos-pay-form-wrapper';
  $form['#theme'] = 'commerce_pos_payment';

  $form['#prefix'] = '<div id="' . $wrapper_id . '">';
  $form['#suffix'] = '</div>';
  $form_ajax = array(
    'wrapper' => 'commerce-pos-pay-form-wrapper',
    'callback' => 'commerce_pos_payment_wrapper_js',
  );

  if (empty($payment_methods)) {
    drupal_set_message(t('No payment methods have been configured for the POS!'), 'error');
  }

  if (!isset($form_state['transaction'])) {
    if ($transaction = CommercePosService::getCurrentTransaction($transaction_type, $user->uid)) {
      $order_wrapper = $transaction->getOrderWrapper();

      if ($order_wrapper && $order_wrapper->status->value() == 'commerce_pos_in_progress' ) {
        $form_state['transaction'] = $transaction;
        $form_state['order_wrapper'] = $order_wrapper;
      }
    }
  }

  $form['header'] = array(
    '#markup' => theme('commerce_pos_header', array('account' => $user)),
  );

  if (isset($form_state['transaction'])) {
    $order_balance = commerce_payment_order_balance($form_state['order_wrapper']->value());
    $line_item_count = 0;

    foreach ($form_state['order_wrapper']->commerce_line_items as $line_item_wrapper) {
      if (in_array($line_item_wrapper->type->value(), commerce_product_line_item_types())) {
        $line_item_count++;
      }
    }

    $items_link = l(format_plural($line_item_count, '1 Item', '@count Items'), '');

    $form['summary'] = array(
      '#markup' => t('Payment - !items - Order #@order_id', array(
        '!items' => $items_link,
        '@order_id' => $form_state['order_wrapper']->order_id->value(),
      )),
    );

    // @TODO: not working.
    $form['summary_table'] = array(
      '#theme' => 'commerce_pos_product_summary',
      '#order' => $form_state['order_wrapper']->value(),
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
      'callback' => 'commerce_pos_payment_keypad_js',
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
        '#validate' => array('commerce_pos_payment_add_payment_validate'),
        '#submit' => array('commerce_pos_payment_add_payment_submit'),
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
      '#validate' => array('commerce_pos_payment_validate'),
      '#submit' => array('commerce_pos_payment_finish'),
      '#ajax' => array(
        'callback' => 'commerce_pos_payment_finish_js',
        'wrapper' => $wrapper_id,
      ),
    );

    $form['balance']['actions']['park'] = array(
      '#type' => 'button',
      '#value' => t('Park'),
      '#attributes' => array(
        'class' => array('commerce-pos-transaction-btn'),
      ),
      '#ajax' => $form_ajax,
      '#element_key' => 'park-transaction',
    );

    $form['balance']['actions']['void'] = array(
      '#type' => 'button',
      '#value' => t('Void'),
      '#attributes' => array(
        'class' => array('commerce-pos-transaction-btn'),
      ),
      '#ajax' => $form_ajax,
      '#element_key' => 'void-transaction',
    );
  }
  else {
    $form['no_transactions'] = array(
      '#markup' => t('You do not currently have any active transactions. Either retrieve a parked transaction or start a new one.'),
    );
  }

  $form['parked_transactions'] = commerce_pos_parked_transactions_section($transaction_type, $form_ajax, !empty($form_state['transaction']));

  $form['#attached']['css'][] = drupal_get_path('module', 'commerce_pos') . '/css/commerce_pos.css';
  $form['#attached']['js'][] = drupal_get_path('module', 'commerce_pos') . '/js/commerce_pos.payment.js';
}

/**
 * AJAX callback for the Pay form keypad.
 */
function commerce_pos_payment_keypad_js($form, &$form_state) {
  return $form['keypad'];
}

/**
 * AJAX callback for the Pay form's wrapper.
 */
function commerce_pos_payment_wrapper_js($form, &$form_state) {
  return $form;
}

/**
 * Validation handler for the "Add" payment button.
 */
function commerce_pos_payment_add_payment_validate($form, &$form_state) {
  if (!is_numeric($form_state['values']['keypad']['amount'])) {
    form_set_error('keypad][amount', t('Payment amount must be a number.'));
  }
}

/**
 * Submit handler for the "Add" payment button.
 */
function commerce_pos_payment_add_payment_submit($form, &$form_state) {
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
 * @TODO: is this even called?
 */
function commerce_pos_payment_void_submit($form, &$form_state) {
  $form_state['transaction']->void();
  $form_state['redirect'] = 'pos/sale';
}

/**
 * Validation handler for the pay form's "Finish" button.
 */
function commerce_pos_payment_validate($form, &$form_state) {

}

/**
 * Submit handler for the pay form's "Finish" button.
 */
function commerce_pos_payment_finish($form, &$form_state) {
  $form_state['transaction']->doAction('completeTransaction');
  $_SESSION['commerce_pos_previous_transaction'] = $form_state['transaction']->transaction_id;
  drupal_set_message(t('Transaction completed'));
}

/**
 * AJAX callback for the pay form's "Finish" button.
 *
 * This logic will occur AFTER the finish submit handler.
 */
function commerce_pos_payment_finish_js($form, &$form_state) {
  module_load_include('inc', 'ctools', 'includes/ajax');

  $commands = array();

  // Allow other modules to add to the list of AJAX commands being returned.
  foreach (module_invoke_all('commerce_pos_pay_finish_commands', $form_state['transaction']) as $module_command) {
    $commands[] = $module_command;
  }

  // Redirect should happen after all other commands.
  $commands[] = ctools_ajax_command_redirect('pos/sale');

  return array('#type' => 'ajax', '#commands' => $commands);
}

/**
 * Helper function to check for AJAX submissions on the POS pay form.
 *
 * This will look at what triggered the AJAX submission and act accordingly.
 */
function commerce_pos_payment_ajax_check($form, &$form_state) {
  if (isset($form_state['triggering_element'])) {
    $triggering_element = $form_state['triggering_element'];

    if (!empty($form_state['triggering_element']['#element_key'])) {
      /* @var CommercePosTransaction $transaction */
      $transaction = $form_state['transaction'];
      $form_state['order_updated'] = TRUE;

      switch ($triggering_element['#element_key']) {
        case 'retrieve-parked-transaction':
          $parked_transaction = new CommercePosTransaction($triggering_element['#transaction_id']);
          $parked_transaction->unpark();

          if ($transaction) {
            $transaction->void();
          }

          unset($form_state['transaction']);

          drupal_set_message(t('Transaction @id retrieved.', array('@id' => $parked_transaction->transactionId)));
          break;
        case 'park-transaction':
          $transaction->doAction('park');
          unset($form_state['transaction']);
          drupal_set_message(t('Transaction @id parked.', array('@id' => $transaction->transactionId)));
          break;

        case 'void-transaction':
          $transaction->void();
          unset($form_state['transaction']);
          drupal_set_message(t('Transaction @id voided.', array('@id' => $transaction->transactionId)));
          break;

        default:
          // If we didn't reach any of the above, it means we don't need to reload the order.
          $form_state['order_updated'] = FALSE;
      }
    }
  }
}
