<?php

/**
 * @file
 * Payment functionality for POS returns and sales.
 */

/**
 * Callback to build a POS payment form.
 */
function commerce_pos_payment($form, &$form_state) {
  global $user;

  $form['#attached']['css'][] = drupal_get_path('module', 'commerce_pos') . '/css/commerce_pos_style.css';

  $wrapper_id = 'commerce-pos-pay-form-wrapper';
  $form_state['wrapper_id'] = $wrapper_id;

  $cashier_id = commerce_pos_cashier_get_current_cashier();
  if (!$cashier_id || !commerce_pos_cashier_load($cashier_id)) {
    drupal_set_message(t('You must enter your cashier code to proceed.'), 'warning');
    return;
  }

  if (!isset($form_state['transaction'])) {
    if ($transaction = CommercePosService::getCurrentTransaction($cashier_id)) {
      $order_wrapper = $transaction->getOrderWrapper();

      if ($order_wrapper && $order_wrapper->status->value() == 'commerce_pos_in_progress') {
        $form_state['transaction'] = $transaction;
        $form_state['order_wrapper'] = $order_wrapper;
      }
    }
  }

  form_load_include($form_state, 'inc', 'commerce_pos', 'includes/commerce_pos.common');
  commerce_pos_payment_ajax_check($form, $form_state);

  $payment_options = commerce_pos_get_payment_options();
  $form['#theme'] = 'commerce_pos_payment';

  $form['#prefix'] = '<div id="' . $wrapper_id . '" class="sale">';
  $form['#suffix'] = '</div>';

  $form_ajax = array(
    'wrapper' => 'commerce-pos-pay-form-wrapper',
    'callback' => 'commerce_pos_payment_wrapper_js',
  );

  if (empty($payment_options)) {
    $link = l(t('click here'), '/admin/commerce/config/custom-offline-payments');
    drupal_set_message(t('No payment options have been configured for the POS! Enable Commerce POS Payment for default payment methods or !link to configure a payment method.',
      array('!link' => $link)), 'error');
  }

  if (isset($form_state['transaction'])) {
    $order_value = $form_state['order_wrapper']->value();
    $order_balance = commerce_payment_order_balance($order_value);
    $line_item_count = 0;

    foreach ($form_state['order_wrapper']->commerce_line_items as $line_item_wrapper) {
      if (in_array($line_item_wrapper->type->value(), commerce_product_line_item_types())) {
        $line_item_count++;
      }
    }

    $items_link = l(format_plural($line_item_count, '1 Item', '@count Items'), '', array(
      'fragment' => ' ',
      'external' => TRUE,
      'attributes' => array(
        'class' => array('commerce-pos-summary-toggle'),
      ),
    ));

    $replacements = array(
      '!items' => $items_link,
      '@order_id' => $form_state['order_wrapper']->order_id->value(),
    );

    $summary_text = t('<div class="order-text">Tranascation - !items - Order #@order_id </div>', $replacements);

    $form['summary'] = array(
      '#markup' => $summary_text,
    );

    $form['summary_table'] = array(
      '#theme' => 'commerce_pos_product_summary',
      '#order' => $form_state['order_wrapper']->value(),
      '#attributes' => array(
        'class' => array('element-invisible'),
      ),
    );

    $form['edit_order'] = array(
      '#markup' => l(t('Edit Transaction'), 'admin/commerce/pos/transaction', array(
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

    // We need to prevent remaining negative balances when were are doing a
    // refund. Otherwise we give change back to the customer.
    $original_balance = $form_state['order_wrapper']->commerce_order_total->amount->value();
    $balance_paid = ($original_balance <= 0 && $order_balance['amount'] == 0) || ($original_balance > 0 && $order_balance['amount'] <= 0);

    foreach ($payment_options as $payment_option) {
      $form['payment_options'][$payment_option['id']] = array(
        '#type' => 'button',
        '#value' => $payment_option['title'],
        '#name' => 'commerce-pos-payment-option-' . $payment_option['id'],
        '#ajax' => $payment_ajax,
        '#payment_option_id' => $payment_option['id'],
        '#disabled' => $balance_paid,
        '#limit_validation_errors' => array(),
      );
    }

    $form['keypad'] = array(
      '#type' => 'container',
      '#id' => 'commerce-pos-sale-keypad-wrapper',
      '#tree' => TRUE,
      '#theme' => 'commerce_pos_keypad',
    );

    // If no triggering element is set, grab the default payment method.
    $default_payment_method = variable_get('commerce_pos_default_payment_method', '');
    if (!empty($default_payment_method) && !empty($payment_options[$default_payment_method]) && empty($form_state['triggering_element']['#payment_option_id'])) {
      $form_state['triggering_element']['#payment_option_id'] = $default_payment_method;
    }

    if (!empty($form_state['triggering_element']['#payment_option_id'])) {
      $option_id = $form_state['triggering_element']['#payment_option_id'];

      if ($option_id == 'commerce_pos_payment_cash' && $order_balance['amount'] > 0) {

        $form['keypad']['quick_pay'] = array(
          '#type' => 'container',
        );

        foreach (array(1, 5, 20) as $step) {
          $rounded = commerce_pos_round_nearest($order_balance, $step);
          $form['keypad']['quick_pay'][$rounded] = array(
            '#type' => 'submit',
            '#prefix' => '<div class="commerce-pos-keypad-quick-pay-button">',
            '#suffix' => '</div>',
            '#value' => commerce_currency_format($rounded, $order_balance['currency_code']),
            '#amount' => $rounded,
            '#ajax' => $form_ajax,
            '#payment_method_id' => $option_id,
            '#element_key' => 'add-payment',
            '#limit_validation_errors' => array(),
            '#submit' => array('commerce_pos_payment_add_payment_submit'),
          );
        }
      }

      $order_balance_amount_format = commerce_currency_format($order_balance['amount'], $order_balance['currency_code']);
      $keypad_amount = preg_replace('/[^0-9\.,]/', '', $order_balance_amount_format);

      $form['keypad']['amount'] = array(
        '#type' => 'textfield',
        '#title' => t('Enter @title Amount', array(
          '@title' => $payment_options[$option_id]['title'],
        )),
        '#required' => TRUE,
        '#default_value' => $keypad_amount,
        '#attributes' => array(
          'autofocus' => 'autofocus',
          'autocomplete' => 'off',
          'class' => array(
            'commerce-pos-payment-keypad-amount',
          ),
        ),
      );

      $form['keypad']['amount']['#attached']['js'][] = array(
        'type' => 'setting',
        'data' => array(
          'commercePosPayment' => array(
            'focusInput' => TRUE,
            'selector' => '.commerce-pos-payment-keypad-amount',
          ),
        ),
      );

      $form['keypad']['add'] = array(
        '#type' => 'submit',
        '#value' => t('Add'),
        '#name' => 'commerce-pos-pay-keypad-add',
        '#validate' => array('commerce_pos_payment_add_payment_validate'),
        '#submit' => array('commerce_pos_payment_add_payment_submit'),
        '#ajax' => $form_ajax,
        '#payment_method_id' => $option_id,
        '#element_key' => 'add-payment',
      );
    }

    $form['balance'] = array(
      '#type' => 'container',
      '#id' => 'commerce-pos-pay-balance-wrapper',
    );

    $order_total = commerce_pos_price_order_format($form_state['order_wrapper'], 'commerce_order_total');

    $form['balance']['order_total'] = array(
      '#markup' => render($order_total),
    );

    $order_value = $form_state['order_wrapper']->value();
    $form['balance']['summary'] = array(
      '#markup' => commerce_pos_balance_summary($order_value),
    );

    $form['balance']['actions'] = array(
      '#type' => 'actions',
    );

    $form['balance']['actions']['void_payment'] = array(
      '#type' => 'submit',
      '#value' => t('Void Payment'),
      '#attributes' => array(
        'class' => array(
          'commerce-pos-transaction-btn',
          'commerce-pos-void-payment',
        ),
        'style' => array('display: none'),
      ),
      '#submit' => array('commerce_pos_payment_void_payment_submit'),
      '#ajax' => array_merge($form_ajax, array('event' => 'void_payment')),
      '#limit_validation_errors' => array(
        array('void_payment_text'),
      ),
      '#element_key' => 'void-payment',
    );

    $form['balance']['void_payment_text'] = array(
      '#type' => 'hidden',
      '#attributes' => array(
        'class' => array(
          'commerce-pos-void-payment-input',
        ),
        'style' => array('display: none'),
      ),
    );

    $form['balance']['actions']['finish'] = array(
      '#type' => 'submit',
      '#value' => t('Finish'),
      '#disabled' => !$balance_paid,
      '#validate' => array('commerce_pos_payment_validate'),
      '#submit' => array('commerce_pos_payment_finish'),
      '#attributes' => array(
        'class' => array('commerce-pos-payment-finish-btn'),
      ),
    );

    $form['balance']['actions']['park'] = array(
      '#type' => 'button',
      '#value' => t('Park'),
      '#attributes' => array(
        'class' => array('commerce-pos-transaction-btn'),
      ),
      '#ajax' => $form_ajax,
      '#limit_validation_errors' => array(),
      '#element_key' => 'park-transaction',
    );

    if (isset($form_state['force-void-transaction-prompt']) && $form_state['force-void-transaction-prompt'] === TRUE) {
      $form['balance']['actions']['void-prompt'] = array(
        '#markup' => '<p>' . t('You have payments that have not yet been voided. Are you sure you want to void this transaction? The payments will not be voided if you continue.') . '</p>',
      );

      $form['balance']['actions']['void'] = array(
        '#type' => 'button',
        '#value' => t('Void'),
        '#attributes' => array(
          'class' => array('commerce-pos-transaction-btn'),
        ),
        '#ajax' => $form_ajax,
        '#limit_validation_errors' => array(),
        '#element_key' => 'force-void-transaction-yes',
      );

      $form['balance']['actions']['cancel_void'] = array(
        '#type' => 'button',
        '#value' => t('Cancel'),
        '#attributes' => array(
          'class' => array('commerce-pos-transaction-btn'),
        ),
        '#ajax' => $form_ajax,
        '#limit_validation_errors' => array(),
        '#element_key' => 'force-void-transaction-no',
      );
    }
    else {
      $form['balance']['actions']['void'] = array(
        '#type' => 'button',
        '#value' => t('Void'),
        '#attributes' => array(
          'class' => array('commerce-pos-transaction-btn'),
        ),
        '#ajax' => $form_ajax,
        '#limit_validation_errors' => array(),
        '#element_key' => 'void-transaction',
      );
    }

    $form['balance']['actions']['customer'] = array(
      'email' => array(
        '#type' => 'textfield',
        '#title' => t('Customer'),
        '#autocomplete_path' => 'admin/commerce/pos/user/autocomplete',
        '#ajax' => array(
          'wrapper' => 'commerce-pos-customer-input-wrapper',
          'callback' => 'commerce_pos_customer_payment_js',
        ),
        '#prefix' => '<div id="commerce-pos-customer-input-wrapper">',
        '#element_key' => 'customer-update',
        '#description' => t('Enter an email address or phone number to lookup a customer'),
        '#attributes' => array(
          'placeholder' => t('email@address.com'),
        ),
        '#default_value' => !empty($transaction->data['customer email']) ? $transaction->data['customer email'] : NULL,
      ),
      'phone' => array(
        '#type' => 'textfield',
        '#title' => t('Phone Number'),
        '#suffix' => '</div>',
        '#element_key' => 'customer-phone-update',
        '#attributes' => array(
          'placeholder' => t('Phone Number'),
        ),
        '#default_value' => !empty($transaction->data['customer phone']) ? $transaction->data['customer phone'] : NULL,
      ),
    );
  }
  else {
    $form['no_transactions'] = array(
      '#markup' => t('You do not currently have any active transactions. Either retrieve a parked transaction or start a new one.'),
    );
  }

  $form['parked_transactions'] = commerce_pos_parked_transactions_section($form_ajax, !empty($form_state['transaction']));

  $form['#attached']['js'][] = drupal_get_path('module', 'commerce_pos') . '/js/commerce_pos.js';
  $form['#attached']['js'][] = drupal_get_path('module', 'commerce_pos') . '/js/keynavigator.js';

  return $form;
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
 * AJAX callback for the customer section.
 */
function commerce_pos_customer_payment_js($form, &$form_state) {
  return $form['balance']['actions']['customer'];
}

/**
 * Validation handler for the "Add" payment button.
 */
function commerce_pos_payment_add_payment_validate($form, &$form_state) {
  if (!is_numeric($form_state['values']['keypad']['amount'])) {
    form_set_error('keypad][amount', t('Payment amount must be a number.'));
  }
  $order_amount = $form_state['transaction']->getOrderWrapper()->commerce_order_total->amount->value();
  if ($order_amount < 0) {
    $currency_code = $form_state['transaction']->getOrderWrapper()->commerce_order_total->currency_code->value();
    $keyed_amount = commerce_currency_decimal_to_amount($form_state['values']['keypad']['amount'], $currency_code);
    $payments = commerce_payment_transaction_load_multiple(array(), array('order_id' => $form_state['transaction']->getOrderWrapper()->order_id->value()));
    foreach ($payments as $payment) {
      if ($payment->status !== 'void') {
        $order_amount -= $payment->amount;
      }
    }
    if ($keyed_amount > abs($order_amount)) {
      form_set_error('keypad][amount', t('Refund must not be greater than order total.'));
    }
  }
}

/**
 * Submit handler for the "Add" payment button.
 */
function commerce_pos_payment_add_payment_submit($form, &$form_state) {
  $payment_method = commerce_payment_method_load($form_state['triggering_element']['#payment_method_id']);

  $order_wrapper = $form_state['transaction']->getOrderWrapper();
  $order_amount = $order_wrapper->commerce_order_total->amount->value();
  $order_id = $order_wrapper->order_id->value();
  $currency_code = $order_wrapper->commerce_order_total->currency_code->value();
  $entered_pay_value = !empty($form_state['triggering_element']['#amount']) ?
    $form_state['triggering_element']['#amount'] : commerce_currency_decimal_to_amount($form_state['values']['keypad']['amount'], $currency_code);
  $transaction = commerce_payment_transaction_new($form_state['triggering_element']['#payment_method_id'], $order_id);
  $transaction->instance_id = $payment_method['method_id'] . '|commerce_pos';
  $transaction->amount = (($order_amount < 0) ? -1 : 1) * $entered_pay_value;
  $transaction->currency_code = $order_wrapper->commerce_order_total->currency_code->value();
  $transaction->status = COMMERCE_PAYMENT_STATUS_SUCCESS;
  $transaction->message = '';
  commerce_payment_transaction_save($transaction);
  $form_state['input']['keypad']['amount'] = NULL;
  $form_state['triggering_element']['#payment_method_id'] = NULL;

  $form_state['rebuild'] = TRUE;
}

/**
 * Validation handler for the pay form's "Finish" button.
 */
function commerce_pos_payment_validate($form, &$form_state) {
  if (empty($form_state['values']['email'])) {
    if (!empty($form_state['values']['phone'])) {
      form_error($form['balance']['actions']['customer']['email'], t('Please provide an email address with phone number'));
    }
  }
  else {
    if (!valid_email_address($form_state['values']['email'])) {
      form_error($form['balance']['actions']['customer']['email'], t('Invalid customer email address.'));
    }
  }
}

/**
 * Submit handler for a payment transaction's "void" link.
 */
function commerce_pos_payment_void_payment_submit($form, &$form_state) {
  $transaction_id = $form_state['values']['void_payment_text'];

  commerce_pos_payment_transaction_void($transaction_id);

  $form_state['rebuild'] = TRUE;
}

/**
 * Submit handler for the pay form's "Finish" button.
 */
function commerce_pos_payment_finish($form, &$form_state) {
  if (isset($form_state['values']['phone'])) {
    $form_state['transaction']->data['customer phone'] = $form_state['values']['phone'];
  }
  $form_state['transaction']->doAction('completeTransaction');
  $form_state['redirect'] = 'admin/commerce/pos/transaction';
  drupal_set_message(t('Transaction Completed'));
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
          $parked_transaction = CommercePosService::loadTransaction($triggering_element['#transaction_id']);
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
          if (commerce_pos_void_transaction_validate($transaction)) {
            $transaction->void();
            unset($form_state['transaction']);
            drupal_set_message(t('Transaction @id voided.', array('@id' => $transaction->transactionId)));
          }
          else {
            $form_state['force-void-transaction-prompt'] = TRUE;
          }
          break;

        case 'force-void-transaction-no':
          unset($form_state['force-void-transaction-prompt']);
          break;

        case 'force-void-transaction-yes':
          $transaction->void();
          unset($form_state['force-void-transaction-prompt']);
          unset($form_state['transaction']);
          drupal_set_message(t('Transaction @id voided.', array('@id' => $transaction->transactionId)));
          break;

        case 'customer-update':
          $transaction->data['customer email'] = !empty($triggering_element['#value']) ? $triggering_element['#value'] : NULL;
          $transaction->doAction('save');
          break;

        default:
          // If we didn't reach any of the above, it means we don't need to reload the order.
          $form_state['order_updated'] = FALSE;
      }

      // See API documentation for an explanation of this hook.
      drupal_alter('commerce_pos_sale_form_ajax', $form_state, $triggering_element);
    }
  }
}

/**
 * Determine if it's safe to void a transaction or not.
 *
 * It is considered unsafe to void a transaction that still have successful
 * payment transactions on it.
 *
 * @param CommercePosTransaction $transaction
 *   The transaction object to validate.
 *
 * @return bool
 *   Whether the transaction is valid or not.
 */
function commerce_pos_void_transaction_validate(CommercePosTransaction $transaction) {
  $valid = TRUE;
  $order = $transaction->getOrder();

  foreach (commerce_pos_get_order_transactions($order->order_id) as $payment_transaction_id) {
    $payment_transaction = commerce_payment_transaction_load($payment_transaction_id);
    if ($payment_transaction->status === COMMERCE_PAYMENT_STATUS_SUCCESS) {
      // It is unnecessary to look at additional transactions after finding a
      // single successful one.
      $valid = FALSE;
      break;
    }
  }

  return $valid;
}
