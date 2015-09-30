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
  $order_wrapper = FALSE;

  commerce_pos_sale_ajax_check($form, $form_state);

  if (!empty($form_state['order_updated'] || !isset($form_state['transaction']))) {
    $transaction = CommercePos::getCurrentTransaction(CommercePos::TRANSACTION_TYPE_SALE, $user->uid);

    if (empty($transaction)) {
      $transaction = CommercePos::createNewTransaction(CommercePos::TRANSACTION_TYPE_SALE, $user->uid);
    }

    $form_state['transaction'] = $transaction;
    $order = $form_state['transaction']->getOrder();
    $order_wrapper = entity_metadata_wrapper('commerce_order', $order);
  }

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
    '#title' => t('Product SKU'),
    '#ajax' => $wrapper_ajax,
    '#attributes' => array(
      'class' => array('commerce-pos-product-sku-input'),
    ),
    '#element_key' => 'product-sku',
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

  $form['transaction_options'] = array(
    '#type' => 'container',
    '#tree' => TRUE,
  );

  if (!empty($order_wrapper->commerce_line_items->value())) {
    $order_total = field_view_field('commerce_order', $order, 'commerce_order_total', 'commerce_pos');
    $transaction_options = &$form['transaction_options'];

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
  }

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

    switch ($triggering_element['#element_key']) {
      case 'product-sku':
        if (!empty($form_state['values']['product_sku']) && $product = commerce_product_load_by_sku($form_state['values']['product_sku'])) {
          $transaction->addProduct($product);
          $form_state['order_updated'] = TRUE;
        }
        break;

      case 'line-item-qty':
        $line_item_id = $triggering_element['#line_item_id'];
        $transaction->updateLineItemQuantity($line_item_id, $form_state['values']['line_items'][$line_item_id]['qty']);
        $form_state['order_updated'] = TRUE;
        break;

      case 'line-item-add-qty':
        $transaction->updateLineItemQuantity($triggering_element['#line_item_id'], 1, 'update');
        $form_state['order_updated'] = TRUE;
        break;

      case 'line-item-remove-qty':
        $transaction->updateLineItemQuantity($triggering_element['#line_item_id'], -1, 'update');
        $form_state['order_updated'] = TRUE;
        break;
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
