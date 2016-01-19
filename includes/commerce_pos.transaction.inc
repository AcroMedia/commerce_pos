<?php

/**
 * @file
 * Common form-based functions for transactions.
 */

/**
 * Callback for the main POS sale form.
 */
function commerce_pos_sale($form, &$form_state) {
  commerce_pos_transaction_form($form, $form_state, CommercePosService::TRANSACTION_TYPE_SALE);
  return $form;
}

/**
 * Callback for the POS return form.
 */
function commerce_pos_return($form, &$form_state) {
  commerce_pos_transaction_form($form, $form_state, CommercePosService::TRANSACTION_TYPE_RETURN);
  return $form;
}

/**
 * Primary callback for building a POS transaction form.
 */
function commerce_pos_transaction_form(&$form, &$form_state, $transaction_type) {
  global $user;

  commerce_pos_transaction_ajax_check($form, $form_state);
  form_load_include($form_state, 'inc', 'commerce_pos', 'includes/commerce_pos.common');

  $return = $transaction_type == CommercePosService::TRANSACTION_TYPE_RETURN;

  $form['#theme'] = 'commerce_pos_transaction';
  $form['#redirect_key'] = $return ? 'return' : 'sale';
  $form['#transaction_type'] = $transaction_type;

  if (!empty($form_state['order_updated']) || !isset($form_state['transaction'])) {
    $transaction = CommercePosService::getCurrentTransaction($transaction_type, $user->uid);

    if (empty($transaction)) {
      $transaction = new CommercePosTransaction(NULL, $transaction_type, $user->uid);
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
  if ($order_wrapper) {
    $line_items = $order_wrapper->commerce_line_items->value();
    $has_active_transaction = !empty($line_items);
  }
  else {
    $has_active_transaction = FALSE;
  }

  $wrapper_id = 'commerce-pos-sale-wrapper';
  // Lets other modules easily get the wrapper ID.
  $form_state['wrapper_id'] = $wrapper_id;

  $wrapper_ajax = array(
    'callback' => 'commerce_pos_transaction_wrapper_js',
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

  $form['#prefix'] = '<div id="' . $wrapper_id . '" class="' . ($return ? 'return' : 'sale') . '">';
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
    '#element_validate' => array('_commerce_pos_transaction_validate_product_input'),
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

    $total_prefix = $return ? '(' : '';
    $total_suffix = $return ? ')' : '';

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

      $line_item_element['line_item_row_wrapper_open'] = array(
        '#markup' => '<div class="line-item-row-wrapper">',
      );

      $line_item_element['quantity_wrapper_open'] = array(
        '#markup' => '<div class="quantity-wrapper line-item-col">',
      );

      $line_item_element['qty'] = array(
        '#title' => (int) $line_item_wrapper->quantity->value(),
        '#type' => 'textfield',
        '#value' => (int) $line_item_wrapper->quantity->value(),
        '#size' => 1,
        '#maxlength' => 3,
        '#ajax' => $wrapper_ajax,
        '#element_key' => 'line-item-qty',
        '#line_item_id' => $line_item_id,
        '#element_validate' => array('_commerce_pos_transaction_validate_qty'),
        '#attributes' => array(
          'class' => array('thevault-pos-hidden-element'),
        ),
      );

      $line_item_element['add_remove_wrapper_open'] = array(
        '#markup' => '<div class="add-remove-wrapper line-item-col">',
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

      $line_item_element['add_remove_wrapper_close'] = array(
        '#markup' => '</div>',
      );

      $line_item_element['quantity_wrapper_close'] = array(
        '#markup' => '</div>',
      );

      $line_item_element['title_wrapper_open'] = array(
        '#markup' => '<div class="title-wrapper line-item-col">',
      );

      $line_item_element['title'] = array(
        '#markup' => '<span class="title">'. $line_item_wrapper->commerce_product->title->value() .'</span>',
      );

      $line_item_element['sku'] = array(
        '#markup' => '<br />' . $line_item_wrapper->commerce_product->sku->value(),
      );

      $line_item_element['title_wrapper_close'] = array(
        '#markup' => '</div>',
      );

      $base_price = commerce_price_component_load($line_item_wrapper->commerce_unit_price->value(), 'base_price');

      $line_item_element['price_edit'] = array(
        '#type' => 'textfield',
        '#title' => t('@ @price', array(
          '@price' => commerce_currency_format($base_price[0]['price']['amount'], $base_price[0]['price']['currency_code']),
        )),
        '#default_value' => number_format($base_price[0]['price']['amount'] / 100, 2),
        '#size' => 2,
        '#maxlength' => 10,
        '#element_key' => 'line-item-change-price',
        '#line_item_id' => $line_item_id,
        '#ajax' => $wrapper_ajax,
        '#attributes' => array(
          'class' => array('thevault-pos-hidden-element'),
        ),
        '#prefix' => '<div class="price-edit-wrapper line-item-col">',
        '#suffix' => '</div>',
      );

      $line_item_element['total_wrapper_open'] = array(
        '#markup' => '<div class="total-wrapper line-item-col">',
      );

      $line_item_total = $line_item_wrapper->commerce_total->value();

      $line_item_element['total'] = array(
        '#markup' => '<strong>' . $total_prefix . commerce_currency_format($line_item_total['amount'], $line_item_total['currency_code']) . $total_suffix . '</strong>',
      );

      $line_item_element['remove'] = array(
        '#type' => 'submit',
        '#value' => 'X',
        '#name' => 'line-item-' . $line_item_id . '-remove',
        '#submit' => array('commerce_pos_transaction_line_item_remove_submit'),
        '#line_item_id' => $line_item_id,
        '#ajax' => $wrapper_ajax,
      );

      $line_item_element['total_wrapper_close'] = array(
        '#markup' => '</div>',
      );

      $line_item_element['line_item_row_wrapper_close'] = array(
        '#markup' => '</div>',
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
    $order_total = commerce_pos_price_order_format($order_wrapper, 'commerce_order_total', $return);
    $transaction_options = &$form['transaction_options'];

    $transaction_options['order_total'] = array(
      '#markup' => render($order_total),
    );

    $order_value = $form_state['order_wrapper']->value();
    $transaction_options['balance'] = array(
      '#markup' => commerce_pos_balance_summary($order_value),
    );

    $transaction_options['actions'] = array(
      '#type' => 'action',
    );

    $transaction_options['actions']['pay'] = array(
      '#type' => 'submit',
      '#value' => $return ? t('Refund (F4)') : t('Pay (F4)'),
      '#attributes' => array(
        'class' => array('commerce-pos-btn-pay'),
      ),
      '#key_binds' => array('f4'),
      '#validate' => array('commerce_pos_transaction_pay_validate'),
      '#submit' => array('commerce_pos_transaction_pay_submit'),
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
        'callback' => 'commerce_pos_transaction_customer_js',
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

  $form['parked_transactions'] = commerce_pos_parked_transactions_section($transaction_type, $wrapper_ajax, $has_active_transaction);

  // Attach our CSS and JS
  $form['#attached']['library'][] = array(
    'system',
    'ui.autocomplete',
  );

  $form['#attached']['css'][] = drupal_get_path('module', 'commerce_pos') . '/css/commerce_pos.css';

  $form['#attached']['js'][] = drupal_get_path('module', 'commerce_pos') . '/js/commerce_pos.common.js';
  $form['#attached']['js'][] = drupal_get_path('module', 'commerce_pos') . '/js/commerce_pos.transaction.js';
  $form['#attached']['js'][] = array(
    'type' => 'setting',
    'data' => array(
      'commercePosSale' => $js_settings,
      '#attributes' => array(
        'class' => array('commerce-pos-transaction-btn'),
      ),
    ),
  );
}

/**
 * Validation handler for the product search input.
 */
function _commerce_pos_transaction_validate_product_input($element, &$form_state, $form) {
  // Always set this input back to empty.
  unset($form_state['input']['input']);
  form_set_value($element, '', $form_state);
}

/**
 * Validation handler for the line item quantity fields.
 */
function _commerce_pos_transaction_validate_qty($element, &$form_state, $form) {
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
function commerce_pos_transaction_ajax_check(&$form, &$form_state) {
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
          $parked_transaction = CommercePosService::loadTransaction($triggering_element['#transaction_id']);
          $parked_transaction->unpark();
          $transaction->void();
          unset($form_state['transaction']);

          drupal_set_message(t('Transaction @id retrieved.', array('@id' => $parked_transaction->transactionId)));
          break;

        case 'line-item-change-price':
          $transaction->setLineItemPrice($triggering_element['#line_item_id'], $triggering_element['#value'] * 100);
          break;

        case 'customer-transaction-update':
          if (!empty($triggering_element['#value'])) {
            $transaction->data['customer email'] = $triggering_element['#value'];
            $transaction->doAction('save');
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
function commerce_pos_transaction_line_item_remove_submit($form, &$form_state) {
  if (!empty($form_state['triggering_element']['#line_item_id'])) {
    $form_state['transaction']->doAction('deleteLineItem', $form_state['triggering_element']['#line_item_id'], TRUE);
    $form_state['transaction']->doAction('saveOrder');
    $form_state['order_updated'] = TRUE;
    $form_state['rebuild'] = TRUE;
  }
}

/**
 * AJAX callback for returning the sale form's wrapper.
 */
function commerce_pos_transaction_wrapper_js($form, &$form_state) {
  return $form;
}

/**
 * AJAX callback for the customer field.
 */
function commerce_pos_transaction_customer_js($form, &$form_state) {
  return $form['customer'];
}

/**
 * Validation handler for the Sale form's "Pay" button.
 */
function commerce_pos_transaction_pay_validate($form, &$form_state) {
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
function commerce_pos_transaction_pay_submit($form, &$form_state) {
  if (!empty($form_state['values']['transaction_options']['customer'])) {
    // Attempt to associate the transaction with a customer.
    $email_address = $form_state['values']['transaction_options']['customer'];

    if ($user = user_load_by_mail($email_address)) {
      if ($form_state['transaction']->doAction('setOrderCustomer', $user)) {
        // Only save if if the order customer was actually updated.
        $form_state['transaction']->doAction('saveOrder');
      }
    }
  }

  $form_state['transaction']->doAction('save');
  $form_state['redirect'] = 'pos/' . $form['#redirect_key'] . '/payment';
}

