<?php

/**
 * @file
 * Theme callbacks for Commerce POS Labels.
 */

/**
 * Theme callback for printing labels.
 */
function theme_commerce_pos_label_print(&$variables) {
  $output = '<div>';
  $format = commerce_pos_label_format_load($variables['format']);

  foreach ($variables['product_ids'] as $product_id => $info) {
    $product = commerce_product_load($product_id);
    $barcode = FALSE;

    if (!empty($format['barcode']) && module_exists('commerce_pos_label_barcode')) {
      if ($barcode = commerce_pos_label_barcode_generate($product, $format)) {
        $barcode = base64_encode($barcode);
      }
    }

    $label = theme('commerce_pos_label', array(
      'product' => $product,
      'title' => $info['title'],
      'price' => $info['price'],
      'barcode' => $barcode,
      'format' => $format,
      'description' => $info['description'],
    ));

    for ($i = 0; $i < $info['quantity']; $i ++) {
      $output .= $label;
    }
  }

  $output .= '</div>';

  return $output;
}

/**
 * Theme callback for the POS labels form.
 */
function theme_commerce_pos_label_labels_form(&$variables) {
  return drupal_render_children($variables['form']);
}

/**
 * Implements hook_preprocess_HOOK().
 */
function commerce_pos_label_preprocess_commerce_pos_label(&$variables) {
  $variables['theme_hook_suggestions'][] = $variables['format']['name'];
  $format = &$variables['format'];

  $variables['attributes_array']['style'] = "width: {$format['dimensions']['width']}in; height: {$format['dimensions']['height']}in;";

  $product = &$variables['product'];
  if ($price = field_get_items('commerce_product', $product, 'commerce_price')) {
    $variables['price'] = commerce_currency_format($variables['price'], $price[0]['currency_code'], NULL, FALSE);
  }

  if (empty($variables['description'])) {
    $variables['classes_array'][] = 'no-description';
  }

  if (empty($variables['barcode'])) {
    $variables['classes_array'][] = 'no-barcode';
  }
}

/**
 * Implements template_preprocess_commerce_pos_label_labels_form().
 */
function commerce_pos_label_preprocess_commerce_pos_label_labels_form(&$variables) {
  $form = &$variables['form'];

  if (!empty($form['label_options']['label_list'])) {
    $rows = array();
    $header = array();

    foreach (element_children($form['label_options']['label_list']) as $key) {
      $elements = &$form['label_options']['label_list'][$key];
      $row = array();
      $build_header = empty($header);

      foreach (element_children($elements) as $element_key) {
        $elements[$element_key]['#title_display'] = 'invisible';
        $row[] = render($elements[$element_key]);

        if ($build_header) {
          $header[] = isset($elements[$element_key]['#title']) ? $elements[$element_key]['#title'] : '';
        }
      }

      $rows[] = $row;
    }

    $form['label_options']['label_list']['table'] = array(
      '#theme' => 'table',
      '#header' => $header,
      '#rows' => $rows,
    );
  }
}
