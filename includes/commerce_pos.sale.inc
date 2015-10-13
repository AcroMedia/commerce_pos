<?php

/**
 * @file
 * Callbacks for the POS sale functionality.
 */

/**
 * Callback for the main POS sale form.
 */
function commerce_pos_sale($form, &$form_state) {
  global $user;

  commerce_pos_sale_ajax_check($form, $form_state);

  if (!empty($form_state['order_updated']) || !isset($form_state['transaction'])) {
    $transaction = CommercePosService::getCurrentTransaction(CommercePosService::TRANSACTION_TYPE_SALE, $user->uid);

    if (empty($transaction)) {
      $transaction = new CommercePosTransaction(NULL, CommercePosService::TRANSACTION_TYPE_SALE, $user->uid);
    }

    $form_state['transaction'] = $transaction;

    if ($order = $form_state['transaction']->getOrder()) {
      if (!empty($form_state['order_updated'])) {
        commerce_order_calculate_total($order);
        commerce_order_save($order);
      }

      $form_state['order_wrapper'] = entity_metadata_wrapper('commerce_order', $order);
    }
    elseif (isset($form_state['order_wrapper'])) {
      unset($form_state['order_wrapper']);
    }
  }

  $order_wrapper = !empty($form_state['order_wrapper']) ? $form_state['order_wrapper'] : FALSE;
  $has_active_transaction = ($order_wrapper && !empty($order_wrapper->commerce_line_items->value()));

  $wrapper_id = 'commerce-pos-sale-wrapper';
  // Lets other modules easily get the wrapper ID.
  $form_state['wrapper_id'] = $wrapper_id;

  $wrapper_ajax = array(
    'callback' => 'commerce_pos_sale_wrapper_js',
    'wrapper' => $wrapper_id,
  );

  // Determine whether or not the product input should be focused by default.
  if (empty($form_state['values']) || !empty($form_state['focus_product_input'])) {
    $focus_product_input = TRUE;
    unset($form_state['focus_product_input']);
  }
  else {
    $focus_product_input = FALSE;
  }

  $js_settings = array(
    'productAutoCompleteUrl' => url('pos/product/autocomplete'),
    'focusProductInput' => $focus_product_input,
  );

  $form['header'] = array(
    '#markup' => theme('commerce_pos_header', array('account' => $user)),
  );

  $form['#prefix'] = '<div id="' . $wrapper_id . '">';
  $form['#suffix'] = '</div>';

  if (!empty($form_state['transaction']->transactionId)) {
    $form['transaction_id'] = array(
      '#markup' => '<div class="commerce-pos-transaction-id">' . t('Transaction #@id', array('@id' => $form_state['transaction']->transactionId)) . '</div>',
    );
  }

  if (!empty($form_state['lookup_result'])) {
    $form['product_results'] = array(
      '#type' => 'container',
      '#id' => 'commerce-pos-pay-product-results-wrapper',
      '#theme' => 'commerce_pos_product_results',
      '#products' => $form_state['lookup_result'],
    );

    foreach ($form_state['lookup_result'] as $product_id) {
      $form['product_results'][$product_id] = array(
        '#type' => 'button',
        '#value' => t('add'),
        '#name' => 'commerce-pos-pay-product-results-' . $product_id,
        '#product_id' => $product_id,
      );
    }

    unset($form_state['lookup_result']);
  }

  $form['product_search'] = array(
    '#type' => 'container',
  );

  $form['product_search']['input'] = array(
    '#type' => 'textfield',
    '#title' => 'product',
    '#size' => 60,
    '#attributes' => array(
      'class' => array('commerce-pos-product-autocomplete', 'commerce-pos-product-search'),
    ),
  );

  $form['product_search']['product_sku'] = array(
    '#type' => 'textfield',
    '#title' => NULL,
    '#ajax' => $wrapper_ajax,
    '#attributes' => array(
      'class' => array('commerce-pos-product-sku-input'),
    ),
    '#element_key' => 'product-sku',
    '#prefix' => '<div class="element-invisible">',
    '#suffix' => '</div>',
  );

  if (!empty($order_wrapper)) {
    $form['line_items'] = array(
      '#tree' => TRUE,
      '#type' => 'container',
    );

    foreach ($order_wrapper->commerce_line_items as $line_item_wrapper) {
      // Only show product line item types.
      if ($line_item_wrapper->type->value() != 'product') {
        continue;
      }

      $line_item_id = $line_item_wrapper->line_item_id->value();

      $form['line_items'][$line_item_id] = array(
        '#type' => 'container',
      );

      $line_item_element = &$form['line_items'][$line_item_id];

      $line_item_element['qty'] = array(
        '#title' => (int) $line_item_wrapper->quantity->value(),
        '#type' => 'textfield',
        '#value' => (int) $line_item_wrapper->quantity->value(),
        '#ajax' => $wrapper_ajax,
        '#element_key' => 'line-item-qty',
        '#line_item_id' => $line_item_id,
        '#element_validate' => array('_commerce_pos_sale_validate_qty'),
        '#attributes' => array(
          'class' => array('thevault-pos-hidden-element'),
        ),
      );

      $line_item_element['add_qty'] = array(
        '#type' => 'button',
        '#value' => '+',
        '#name' => 'line-item-' . $line_item_id . '-plus',
        '#element_key' => 'line-item-add-qty',
        '#line_item_id' => $line_item_id,
        '#ajax' => $wrapper_ajax,
      );

      $line_item_element['remove_qty'] = array(
        '#type' => 'button',
        '#value' => '-',
        '#name' => 'line-item-' . $line_item_id . '-minus',
        '#element_key' => 'line-item-remove-qty',
        '#line_item_id' => $line_item_id,
        '#ajax' => $wrapper_ajax,
      );

      $line_item_element['title'] = array(
        '#markup' => $line_item_wrapper->commerce_product->title->value(),
      );

      $line_item_element['sku'] = array(
        '#markup' => $line_item_wrapper->commerce_product->sku->value(),
      );

      $unit_price = $line_item_wrapper->commerce_unit_price->value();

      $line_item_element['price_edit'] = array(
        '#type' => 'textfield',
        '#title' => t('@ @price', array(
          '@price' => commerce_currency_format($unit_price['amount'], $unit_price['currency_code']),
        )),
        '#default_value' => number_format($unit_price['amount'] / 100, 2),
        '#size' => 10,
        '#element_key' => 'line-item-change-price',
        '#line_item_id' => $line_item_id,
        '#ajax' => $wrapper_ajax,
        '#attributes' => array(
          'class' => array('thevault-pos-hidden-element'),
        ),
      );

      $price_view = field_view_field('commerce_line_item', $line_item_wrapper->value(), 'commerce_total', 'commerce_pos');
      $price_view = render($price_view);

      if (empty($price_view)) {
        $line_item_total = $line_item_wrapper->commerce_total->value();
        $price_view = commerce_currency_format($line_item_total['amount'], $line_item_total['currency_code']);
      }

      $line_item_element['total'] = array(
        '#markup' => $price_view,
      );

      $line_item_element['remove'] = array(
        '#type' => 'submit',
        '#value' => 'X',
        '#name' => 'line-item-' . $line_item_id . '-remove',
        '#submit' => array('commerce_pos_sale_line_item_remove_submit'),
        '#line_item_id' => $line_item_id,
        '#ajax' => $wrapper_ajax,
      );
    }
  }

  // Transaction options.
  $form['transaction_options'] = array(
    '#type' => 'container',
    '#tree' => TRUE,
    '#id' => 'commerce-pos-transactions-options-wrapper',
  );

  if ($has_active_transaction) {
    $order_total = field_view_field('commerce_order', $order_wrapper->value(), 'commerce_order_total', 'commerce_pos');
    $transaction_options = &$form['transaction_options'];

    $transaction_options['order_total'] = array(
      '#markup' => render($order_total),
    );

    $transaction_options['actions'] = array(
      '#type' => 'action',
    );

    $transaction_options['actions']['pay'] = array(
      '#type' => 'submit',
      '#value' => t('Pay (F4)'),
      '#attributes' => array(
        'class' => array('commerce-pos-btn-pay'),
      ),
      '#key_binds' => array('f4'),
      '#validate' => array('commerce_pos_sale_pay_validate'),
      '#submit' => array('commerce_pos_sale_pay_submit'),
    );

    $transaction_options['actions']['park'] = array(
      '#type' => 'button',
      '#value' => t('Park'),
      '#attributes' => array(
        'class' => array('commerce-pos-transaction-btn'),
      ),
      '#ajax' => $wrapper_ajax,
      '#element_key' => 'park-transaction',
    );

    $transaction_options['actions']['void'] = array(
      '#type' => 'button',
      '#value' => t('Void'),
      '#attributes' => array(
        'class' => array('commerce-pos-transaction-btn'),
      ),
      '#ajax' => $wrapper_ajax,
      '#element_key' => 'void-transaction',
    );

    $transaction_options['customer'] = array(
      '#type' => 'textfield',
      '#title' => t('Customer'),
      '#autocomplete_path' => 'pos/user/autocomplete',
      '#ajax' => array(
        'wrapper' => 'commerce-pos-customer-input-wrapper',
        'callback' => 'commerce_pos_sale_customer_js',
      ),
      '#prefix' => '<div id="commerce-pos-customer-input-wrapper">',
      '#suffix' => '</div>',
      '#element_key' => 'customer-transaction-update',
      '#attributes' => array(
        'placeholder' => t('email@address.com'),
      ),
      '#default_value' => !empty($transaction->data['customer email']) ? $transaction->data['customer email'] : NULL,
    );
  }

  // Parked transactions
  if ($parked_transactions = CommercePosService::getParkedTransactions(CommercePosService::TRANSACTION_TYPE_SALE, $user->uid)) {
    $form['parked_transactions'] = array(
      '#type' => 'container',
      '#tree' => TRUE,
    );

    $description = format_plural(count($parked_transactions), '1 Transaction Parked', '@count Transactions Parked') . ' - ';

    $form['parked_transactions']['description'] = array(
      '#markup' => $description,
    );

    foreach ($parked_transactions as $transaction_id) {
      if (count($parked_transactions) == 1) {
        $button_text = t('Retrieve');
      }
      else {
        $button_text = t('Retrieve Transaction #@id', array(
          '@id' => $transaction_id,
        ));
      }

      $form['parked_transactions'][$transaction_id] = array(
        '#type' => 'button',
        '#value' => $button_text,
        '#attributes' => array(
          'class' => array('commerce-pos-btn-retrieve-transaction'),
        ),
        '#ajax' => $wrapper_ajax,
        '#name' => 'retrieve-parked-transaction-' . $transaction_id,
        '#transaction_id' => $transaction_id,
        '#disabled' => $has_active_transaction,
        '#element_key' => 'retrieve-parked-transaction',
      );
    }
  }

  // Attach our CSS and JS
  $form['#attached']['library'][] = array(
    'system',
    'ui.autocomplete',
  );

  $form['#attached']['css'][] = drupal_get_path('module', 'commerce_pos') . '/css/commerce_pos.css';
  $form['#attached']['js'][] = drupal_get_path('module', 'commerce_pos') . '/js/commerce_pos.sale.js';
  $form['#attached']['js'][] = array(
    'type' => 'setting',
    'data' => array(
      'commercePosSale' => $js_settings,
      '#attributes' => array(
        'class' => array('commerce-pos-transaction-btn'),
      ),
    ),
  );

  return $form;
}

/**
 * Validation handler for the line item quantity fields.
 */
function _commerce_pos_sale_validate_qty($element, &$form_state, $form) {
  $requested_qty = drupal_array_get_nested_value($form_state['input'], $element['#parents']);

  if (is_numeric($requested_qty) && $requested_qty > 0) {
    form_set_value($element, $requested_qty, $form_state);
  }
  else {
    form_error($element, t('Quantity must be a number and greater than zero.'));
  }
}

/**
 * Helper function to check for AJAX submissions on the POS sale form.
 *
 * This will look at what triggered the AJAX submission and act accordingly.
 */
function commerce_pos_sale_ajax_check(&$form, &$form_state) {
  if (isset($form_state['triggering_element'])) {
    $triggering_element = $form_state['triggering_element'];

    if (!empty($form_state['triggering_element']['#element_key'])) {
      /* @var CommercePosTransaction $transaction */
      $transaction = $form_state['transaction'];

      $form_state['order_updated'] = TRUE;

      switch ($triggering_element['#element_key']) {
        case 'product-sku':
          if (!empty($form_state['values']['product_sku'])) {
            $product_sku = $form_state['values']['product_sku'];
          }
          elseif (!empty($form_state['input']['product_sku'])) {
            $product_sku = $form_state['input']['product_sku'];
          }
          else {
            $product_sku = FALSE;
          }

          if (!empty($product_sku) && $product = commerce_product_load_by_sku($product_sku)) {
            $transaction->addProduct($product);
          }

          $form_state['focus_product_input'] = TRUE;
          break;

        case 'line-item-qty':
          $line_item_id = $triggering_element['#line_item_id'];
          $transaction->updateLineItemQuantity($line_item_id, $form_state['values']['line_items'][$line_item_id]['qty']);
          break;

        case 'line-item-add-qty':
          $transaction->updateLineItemQuantity($triggering_element['#line_item_id'], 1, 'update');
          break;

        case 'line-item-remove-qty':
          $transaction->updateLineItemQuantity($triggering_element['#line_item_id'], -1, 'update');
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

        case 'retrieve-parked-transaction':
          $parked_transaction = new CommercePosTransaction($triggering_element['#transaction_id']);
          $parked_transaction->unpark();
          $transaction->void();
          unset($form_state['transaction']);

          drupal_set_message(t('Transaction @id retrieved.', array('@id' => $parked_transaction->transactionId)));
          break;

        case 'line-item-change-price':
          $transaction->setLineItemPrice($triggering_element['#line_item_id'], $triggering_element['#value']);
          break;

        case 'customer-transaction-update':
          if (!empty($triggering_element['#value'])) {
            $transaction->data['customer email'] = $triggering_element['#value'];
            $transaction->save();
          }
          break;

        case 'product-input':
          $keywords = $triggering_element['#value'];

          if (!empty($keywords)) {
            if ($lookup_result = commerce_pos_product_lookup($keywords)) {
              if (is_array($lookup_result)) {
                // Got a set of results back, need to present the options to the user.
                $form_state['lookup_result'] = $lookup_result;
              }
              else {
                // Got a straight up product ID back, just add it to the order.
                $product = commerce_product_load($lookup_result);
                $transaction->addProduct($product);
              }
            }
          }
          break;

        default:
          // If we didn't reach any of the above, it means we don't need to reload the order.
          $form_state['order_updated'] = FALSE;
      }

      // See API documentation for explain of this hook.
      drupal_alter('commerce_pos_sale_form_ajax', $form_state, $triggering_element);
    }
  }
}

/**
 * Submit handler for removing a line item entirely.
 */
function commerce_pos_sale_line_item_remove_submit($form, &$form_state) {
  if (!empty($form_state['triggering_element']['#line_item_id'])) {
    $form_state['transaction']->invokeBaseMethod('deleteLineItem', $form_state['triggering_element']['#line_item_id'], TRUE);
    $form_state['transaction']->invokeBaseMethod('saveOrder');

    if (empty($form_state['transaction']->getLineItems())) {
      $form_state['transaction']->void();
      unset($form_state['transaction']);
    }

    $form_state['order_updated'] = TRUE;
    $form_state['rebuild'] = TRUE;
  }
}

/**
 * AJAX callback for returning the sale form's wrapper.
 */
function commerce_pos_sale_wrapper_js($form, &$form_state) {
  return $form;
}

/**
 * AJAX callback for the customer field.
 */
function commerce_pos_sale_customer_js($form, &$form_state) {
  return $form['customer'];
}

/**
 * Validation handler for the Sale form's "Pay" button.
 */
function commerce_pos_sale_pay_validate($form, &$form_state) {
  if (!empty($form_state['values']['transaction_options']['customer'])) {
    $email_address = $form_state['values']['transaction_options']['customer'];

    if (!valid_email_address($email_address)) {
      form_error($form['transaction_options']['customer'], t('Invalid customer email address.'));
    }
  }
}

/**
 * Submit handler for the Sale form's "Pay" button.
 */
function commerce_pos_sale_pay_submit($form, &$form_state) {
  if (!empty($form_state['values']['transaction_options']['customer'])) {
    // Attempt to associate the transaction with a customer.
    $email_address = $form_state['values']['transaction_options']['customer'];

    if ($user = user_load_by_mail($email_address)) {
      if ($form_state['transaction']->invokeBaseMethod('setOrderCustomer', $user)) {
        // Only save if if the order customer was actually updated.
        $form_state['transaction']->invokeBaseMethod('saveOrder');
      }
    }
  }

  drupal_goto('pos/sale/pay');
}

/**
 * Callback for the POS "pay" form.
 */
function commerce_pos_sale_pay($form, &$form_state) {
  global $user;

  $payment_methods = commerce_pos_get_payment_methods();

  $form['#prefix'] = '<div id="commerce-pos-pay-form-wrapper">';
  $form['#suffix'] = '</div>';
  $form_ajax = array(
    'wrapper' => 'commerce-pos-pay-form-wrapper',
    'callback' => 'commerce_pos_sale_pay_wrapper_js',
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
    'callback' => 'commerce_pos_sale_pay_keypad_js',
    'effect' => 'fade',
  );

  foreach ($payment_methods as $payment_method) {
    $form['payment_options'][$payment_method['id']] = array(
      '#type' => 'button',
      '#value' => $payment_method['title'],
      '#name' => 'commerce-pos-payment-method-' . $payment_method['id'],
      '#ajax' => $payment_ajax,
      '#payment_method_id' => $payment_method['id'],
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
      '#validate' => array('commerce_pos_sale_pay_add_payment_validate'),
      '#submit' => array('commerce_pos_sale_pay_add_payment_submit'),
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

  $order_balance = commerce_payment_order_balance($form_state['order_wrapper']->value());

  $form['balance']['actions']['finish'] = array(
    '#type' => 'submit',
    '#value' => t('Finish'),
    '#disabled' => $order_balance['amount'] > 0,
    '#validate' => array('commerce_pos_sale_pay_validate'),
    '#submit' => array('commerce_pos_sale_pay_finish'),
  );

  $form['balance']['actions']['park'] = array(
    '#type' => 'button',
    '#value' => t('Park'),
  );

  $form['balance']['actions']['void'] = array(
    '#type' => 'submit',
    '#value' => t('Void'),
    '#submit' => array('commerce_pos_sale_pay_void_submit'),
  );

  $form['#attached']['js'][] = drupal_get_path('module', 'commerce_pos') . '/js/commerce_pos.pay.js';

  return $form;
}

/**
 * AJAX callback for the Pay form keypad.
 */
function commerce_pos_sale_pay_keypad_js($form, &$form_state) {
  return $form['keypad'];
}

/**
 * AJAX callback for the Pay form's wrapper.
 */
function commerce_pos_sale_pay_wrapper_js($form, &$form_state) {
  return $form;
}

/**
 * Validation handler for the "Add" payment button.
 */
function commerce_pos_sale_pay_add_payment_validate($form, &$form_state) {
  if (!is_numeric($form_state['values']['keypad']['amount'])) {
    form_set_error('keypad][amount', t('Payment amount must be a number.'));
  }
}

/**
 * Submit handler for the "Add" payment button.
 */
function commerce_pos_sale_pay_add_payment_submit($form, &$form_state) {
  $payment_method = commerce_payment_method_load($form_state['triggering_element']['#payment_method_id']);
  $order_wrapper = $form_state['transaction']->getOrderWrapper();
  $transaction = commerce_payment_transaction_new($form_state['triggering_element']['#payment_method_id'], $order_wrapper->order_id->value());
  $transaction->instance_id = $payment_method['method_id'] . '|commerce_pos';
  $transaction->amount = $form_state['values']['keypad']['amount'] * 100;
  $transaction->currency_code = $order_wrapper->commerce_order_total->currency_code->value();
  $transaction->status = COMMERCE_PAYMENT_STATUS_SUCCESS;
  $transaction->message = '';
  commerce_payment_transaction_save($transaction);
}

/**
 * Submit handler for the "Void" button.
 */
function commerce_pos_sale_pay_void_submit($form, &$form_state) {
  $form_state['transaction']->void();
  $form_state['redirect'] = 'pos/sale';
}

/**
 * Submit handler for the pay form's "Finish" button.
 */
function commerce_pos_sale_pay_finish($form, &$form_state) {
  // @TODO: need to mark the order as completed, add a "change" line item if
  // necessary, print the receipt, and redirect back to the "Sale" form.
  // Also need to create and/or associate the order with a customer.
}
