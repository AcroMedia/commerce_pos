<?php

/**
 * @file
 * Admin form and page callbacks for Commerce POS Labels.
 */

/**
 * Callback to generate the print labels page.
 */
function commerce_pos_label_labels_form_page() {
  global $user;
  $page = array();

  $page['#attached']['library'][] = array(
    'system',
    'ui.autocomplete',
  );

  $page['#attached']['js'][] = array(
    'type' => 'setting',
    'data' => array(
      'commercePosSale' => array(
        'productAutoCompleteUrl' => url('admin/commerce/pos/product/autocomplete'),
        'autoCompleteCallback' => 'autocompleteCallback',
        'autoCompleteNamespace' => 'commercePosLabel',
      ),
    ),
  );

  $page['#attached']['js'][] = drupal_get_path('module', 'commerce_pos') . '/js/keynavigator.js';
  $page['#attached']['css'][] = drupal_get_path('module', 'commerce_pos') . '/css/commerce_pos_style.css';
  $page['#attached']['js'][] = drupal_get_path('module', 'commerce_pos') . '/js/commerce_pos.transaction.js';

  $page['header'] = array(
    '#markup' => theme('commerce_pos_header', array('account' => $user)),
  );
  $page['form'] = drupal_get_form('commerce_pos_label_labels_form');

  return $page;
}

/**
 * Callback for the print labels form.
 */
function commerce_pos_label_labels_form($form, &$form_state, $product = FALSE) {
  $format_options = array();

  foreach (commerce_pos_label_get_label_formats() as $name => $format) {
    $format_options[$name] = $format['title'];
  }

  // We need at least 1 label format to proceed.
  if (empty($format_options)) {
    drupal_set_message(t('There are no available label formats. Please enable at least one POS label format module.'), 'error');
    return $form;
  }

  if (!isset($form_state['labels_to_create'])) {
    $form_state['labels_to_create'] = array();

    if ($product !== FALSE) {
      $form_state['labels_to_create'][$product->product_id] = commerce_pos_label_labels_form_build_info_array($product);
    }
  }

  $form_wrapper_id = 'commerce-pos-label-form-container';

  $form['#prefix'] = '<div id="' . $form_wrapper_id . '" class="commerce-pos-form-container">';
  $form['#suffix'] = '</div>';

  $form['label_format'] = array(
    '#type' => 'select',
    '#title' => t('Label format'),
    '#options' => $format_options,
    '#required' => TRUE,
    '#default_value' => variable_get('commerce_pos_label_default_format', NULL),
  );

  if ($product === FALSE) {
    $form['product_search'] = array(
      '#type' => 'textfield',
      '#title' => t('product search'),
      '#title_display' => 'invisible',
      '#size' => 60,
      '#description' => t('Search by SKU or product title, start typing to begin your search.'),
      '#attributes' => array(
        'class' => array(
          'commerce-pos-product-autocomplete',
          'commerce-pos-product-search',
        ),
        'placeholder' => t('Product Search'),
      ),
    );

    $form['product_search_add'] = array(
      '#type' => 'submit',
      '#value' => t('Add'),
      '#validate' => array('commerce_pos_label_labels_form_add_validate'),
      '#submit' => array('commerce_pos_label_labels_form_add_submit'),
      '#attributes' => array(
        'class' => array('commerce-pos-label-btn-add commerce-pos-btn fixed-width btn-success'),
      ),
      '#ajax' => array(
        'wrapper' => $form_wrapper_id,
        'callback' => 'commerce_pos_label_labels_form_js',
      ),
    );
  }

  $form['label_options'] = array(
    '#type' => 'container',
    '#id' => 'commerce-pos-label-label-options-container',
    '#prefix' => '<br>',
  );

  $form['label_options']['label_list'] = array(
    '#type' => 'container',
    '#tree' => TRUE,
  );

  if (!empty($form_state['labels_to_create'])) {
    foreach ($form_state['labels_to_create'] as $product_id => $label_info) {
      $form['label_options']['label_list'][$product_id]['quantity'] = array(
        '#type' => 'textfield',
        '#title' => t('Quantity'),
        '#default_value' => $label_info['quantity'],
        '#required' => TRUE,
        '#size' => 5,
      );

      $form['label_options']['label_list'][$product_id]['title'] = array(
        '#title' => t('Title'),
        '#type' => 'textfield',
        '#required' => TRUE,
        '#default_value' => $label_info['title'],
      );

      $form['label_options']['label_list'][$product_id]['description'] = array(
        '#title' => t('Description'),
        '#type' => 'textfield',
        '#default_value' => $label_info['description'],
      );

      $form['label_options']['label_list'][$product_id]['price'] = array(
        '#type' => 'textfield',
        '#title' => t('Price'),
        '#default_value' => $label_info['price'],
        '#required' => TRUE,
        '#size' => 5,
      );

      if ($product === FALSE) {
        $form['label_options']['label_list'][$product_id]['remove'] = array(
          '#type' => 'submit',
          '#value' => t('Remove'),
          '#name' => 'remove-' . $product_id,
          '#product_id' => $product_id,
          '#submit' => array('commerce_pos_label_labels_form_remove_submit'),
          '#ajax' => array(
            'wrapper' => $form_wrapper_id,
            'callback' => 'commerce_pos_label_labels_form_js',
          ),
          '#attributes' => array(
            'class' => array('commerce-pos-btn fixed-width btn-danger'),
          ),
        );
      }
    }

    $form['label_options']['print_labels'] = array(
      '#type' => 'submit',
      '#value' => t('Print'),
      '#submit' => array('commerce_pos_label_labels_form_print_submit'),
      '#ajax' => array(
        'wrapper' => $form_wrapper_id,
        'callback' => 'commerce_pos_labels_labels_print_js',
      ),
      '#attributes' => array(
        'class' => array('commerce-pos-btn fixed-width'),
      ),
    );
  }

  // Attach our CSS and JS.
  $form['#attached']['library'][] = array(
    'system',
    'ui.autocomplete',
  );

  $form['#attached']['libraries_load'][] = array('jquery-print');
  $form['#attached']['js'][] = drupal_get_path('module', 'commerce_pos_label') . '/js/commerce_pos_label.js';

  return $form;
}

/**
 * Validation handler for the Label form's "Add" button.
 */
function commerce_pos_label_labels_form_add_validate($form, &$form_state) {
  $valid_product_id = FALSE;

  if (!empty($form_state['input']['product_search'])) {
    $searched_sku = $form_state['input']['product_search'];
    // Do a quick check and make sure the product ID exists.
    $product_id = db_query('SELECT product_id FROM {commerce_product} WHERE sku = :sku', array(':sku' => $searched_sku))->fetchField();

    if ($product_id) {
      $valid_product_id = TRUE;
      $form_state['product_id_to_add'] = $product_id;
      form_set_value($form['product_search'], '', $form_state);
      unset($form_state['input']['product_search']);
    }
  }

  if (!$valid_product_id) {
    form_error($form['product_search'], t('Invalid product.'));
  }
}

/**
 * Submit handler for the label form's "Remove' buttons.
 */
function commerce_pos_label_labels_form_remove_submit($form, &$form_state) {
  $product_id = $form_state['triggering_element']['#product_id'];
  unset($form_state['labels_to_create'][$product_id]);
  $form_state['rebuild'] = TRUE;
}

/**
 * Main AJAX callback for the label form.
 */
function commerce_pos_label_labels_form_js($form, &$form_state) {
  return $form;
}

/**
 * Submit handler for the label form's "Add" button.
 */
function commerce_pos_label_labels_form_add_submit($form, &$form_state) {
  if (!empty($form_state['product_id_to_add'])) {
    $product = commerce_product_load($form_state['product_id_to_add']);
    $form_state['labels_to_create'][$form_state['product_id_to_add']] = commerce_pos_label_labels_form_build_info_array($product);
    unset($form_state['product_id_to_add']);
    $form_state['rebuild'] = TRUE;
  }
}

/**
 * Build an array of product info used for printing labels via the labels form.
 *
 * @param object $product
 *   The product to create the array for.
 *
 * @return array
 *   The info array.
 */
function commerce_pos_label_labels_form_build_info_array($product) {
  $default_price = 0;

  if ($price = field_get_items('commerce_product', $product, 'commerce_price')) {
    if (!empty($price[0]['amount'])) {
      $default_price = commerce_currency_amount_to_decimal($price[0]['amount'], $price[0]['currency_code']);
    }
  }

  return array(
    'title' => $product->title,
    'quantity' => 1,
    'price' => $default_price,
    'description' => commerce_pos_label_attributes_string($product),
  );
}

/**
 * Submit handler for the label form's "Print" button.
 */
function commerce_pos_label_labels_form_print_submit($form, &$form_state) {
  $format = commerce_pos_label_format_load($form_state['values']['label_format']);

  $form_state['storage']['labels_to_print'] = array(
    'format' => $format,
    'products' => array(),
  );

  foreach ($form_state['values']['label_list'] as $product_id => $values) {
    $form_state['storage']['labels_to_print']['products'][$product_id] = $values;
  }
}

/**
 * AJAX handler for the label form's "Print" button.
 */
function commerce_pos_labels_labels_print_js($form, &$form_state) {
  $commands = array();

  if (!form_get_errors()) {
    if (!empty($form_state['storage']['labels_to_print'])) {
      $format = &$form_state['storage']['labels_to_print']['format'];

      if (!empty($format['css'])) {
        $css_url = $format['css'];

      }
      else {
        $css_url = url(drupal_get_path('module', 'commerce_pos_label') . '/css/commerce_pos_label.css', array(
          'absolute' => TRUE,
        ));
      }

      $commands[] = array(
        'command' => 'printLabels',
        'cssUrl' => $css_url,
        'content' => theme('commerce_pos_label_print', array(
          'product_ids' => $form_state['storage']['labels_to_print']['products'],
          'format' => $form_state['values']['label_format'],
        )),
      );
    }
  }

  unset($form_state['storage']['labels_to_print']);

  return array('#type' => 'ajax', '#commands' => $commands);
}
