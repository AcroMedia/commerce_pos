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

  if (!empty($form_state['order_updated'] || !isset($form_state['transaction']))) {
    $transaction = CommercePos::getCurrentTransaction(CommercePos::TRANSACTION_TYPE_SALE, $user->uid);

    if (empty($transaction)) {
      $transaction = new CommercePosTransaction(NULL, CommercePos::TRANSACTION_TYPE_SALE, $user->uid);
    }

    $form_state['transaction'] = $transaction;

    if ($order = $form_state['transaction']->getOrder()) {
      $form_state['order_wrapper'] = entity_metadata_wrapper('commerce_order', $order);
    }
    elseif (isset($form_state['order_wrapper'])) {
      unset($form_state['order_wrapper']);
    }
  }

  $order_wrapper = !empty($form_state['order_wrapper']) ? $form_state['order_wrapper'] : FALSE;

  $form['#attached']['library'][] = array(
    'system',
    'ui.autocomplete',
  );

  $wrapper_id = 'commerce-pos-sale-wrapper';
  $wrapper_ajax = array(
    'callback' => 'commerce_pos_sale_wrapper_js',
    'wrapper' => $wrapper_id,
  );

  $form['#attached']['js'][] = drupal_get_path('module', 'commerce_pos') . '/js/commerce_pos.sale.js';

  $js_settings = array(
    'productAutoCompleteUrl' => url('pos/product/autocomplete'),
  );

  $form['wrapper'] = array(
    '#type' => 'container',
    '#id' => $wrapper_id,
  );

  $wrapper = &$form['wrapper'];

  $wrapper['product_search'] = array(
    '#type' => 'textfield',
    '#title' => 'product',
    '#size' => 60,
    '#attributes' => array(
      'class' => array('commerce-pos-product-autocomplete'),
    ),
  );

  $wrapper['product_sku'] = array(
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
    $wrapper['line_items'] = array(
      '#tree' => TRUE,
      '#type' => 'container',
    );

    foreach ($order_wrapper->commerce_line_items as $line_item_wrapper) {
      $line_item_id = $line_item_wrapper->line_item_id->value();

      $wrapper['line_items'][$line_item_id] = array(
        '#type' => 'container',
      );

      $line_item_element = &$wrapper['line_items'][$line_item_id];

      $line_item_element['qty'] = array(
        '#title' => 'Quantity',
        '#type' => 'textfield',
        '#value' => (int) $line_item_wrapper->quantity->value(),
        '#ajax' => $wrapper_ajax,
        '#element_key' => 'line-item-qty',
        '#line_item_id' => $line_item_id,
        '#element_validate' => array('_commerce_pos_sale_validate_qty'),
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
        '#default_value' => $unit_price['amount'] / 100,
        '#size' => 10,
        '#ajax' => $wrapper_ajax,
      );

      $line_item_total = $line_item_wrapper->commerce_total->value();

      $line_item_element['total'] = array(
        '#markup' => commerce_currency_format($line_item_total['amount'], $line_item_total['currency_code']),
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

  $form['wrapper']['transaction_options'] = array(
    '#type' => 'container',
    '#tree' => TRUE,
  );

  if ($order_wrapper && !empty($order_wrapper->commerce_line_items->value())) {
    $order_total = field_view_field('commerce_order', $order_wrapper->value(), 'commerce_order_total', 'commerce_pos');
    $transaction_options = &$form['wrapper']['transaction_options'];

    $transaction_options['order_total'] = array(
      '#markup' => render($order_total),
    );

    $transaction_options['pay'] = array(
      '#type' => 'submit',
      '#value' => t('Pay (F4)'),
      '#attributes' => array(
        'class' => array('commerce-pos-btn-pay'),
      ),
      '#key_binds' => array('f4'),
      '#ajax' => $wrapper_ajax,
    );

    $transaction_options['park'] = array(
      '#type' => 'button',
      '#value' => t('Park'),
      '#attributes' => array(
        'class' => array('commerce-pos-transaction-btn'),
      ),
      '#ajax' => $wrapper_ajax,
      '#element_key' => 'park-transaction',
    );

    $transaction_options['void'] = array(
      '#type' => 'button',
      '#value' => t('Void'),
      '#attributes' => array(
        'class' => array('commerce-pos-transaction-btn'),
      ),
    );
    $transaction_options['discount'] = array(
      '#type' => 'button',
      '#value' => t('Discount'),
      '#attributes' => array(
        'class' => array('commerce-pos-transaction-btn'),
      ),
    );

    $transaction_options['customer'] = array(
      '#type' => 'textfield',
      '#title' => t('Customer'),
      '#autocomplete_path' => 'pos/user/autocomplete',
    );

    $has_active_transaction = TRUE;
  }
  else {
    $has_active_transaction = FALSE;
  }

  // Parked transactions
  if ($parked_transactions = CommercePos::getParkedTransactions(CommercePos::TRANSACTION_TYPE_SALE, $user->uid)) {
    $form['parked_transactions'] = array(
      '#type' => 'container',
      '#tree' => TRUE,
    );

    $description = format_plural(count($parked_transactions), '1 Transaction Parked', '@count Transactions Parked') . ' - ';

    $form['wrapper']['parked_transactions']['description'] = array(
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

      $form['wrapper']['parked_transactions'][$transaction_id] = array(
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

  $js_settings['hasActiveTransaction'] = $has_active_transaction;

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
  if (!empty($form_state['triggering_element']['#element_key'])) {
    $triggering_element = $form_state['triggering_element'];

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
        $transaction->park();
        unset($form_state['transaction']);
        break;

      case 'retrieve-parked-transaction':
        $parked_transaction = new CommercePosTransaction($triggering_element['#transaction_id']);
        $parked_transaction->unpark();
        $transaction->void();
        break;

      default:
        // If we didn't reach any of the above, it means we don't need to reload the order.
        $form_state['order_updated'] = FALSE;
    }
  }
}

/**
 * Submit handler for removing a line item entirely.
 */
function commerce_pos_sale_line_item_remove_submit($form, &$form_state) {
  if (!empty($form_state['triggering_element']['#line_item_id'])) {
    $form_state['transaction']->deleteLineItem($form_state['triggering_element']['#line_item_id']);
    $form_state['order_updated'] = TRUE;
    $form_state['rebuild'] = TRUE;
  }
}

/**
 * AJAX callback for returning the sale form's wrapper.
 */
function commerce_pos_sale_wrapper_js($form, &$form_state) {
  return $form['wrapper'];
}
