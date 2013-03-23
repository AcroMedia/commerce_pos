<?php


function template_preprocess_pos_receipt(&$variables) {
  $state = $variables['state'];
  $registry = $variables['registry'];

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

  if (module_exists('barcode') && $order_command = $registry->getCommand('load_order')) {
      $variables['receipt_footer']['barcode'] = array(
        '#theme' => 'barcode_image',
        '#barcode_value' => str_replace('%s', $state->getOrder()->order_id, $order_command->getInputPattern()),
        '#encoding' => variable_get('commerce_pos_barcode_format', 'EAN-13'),
        '#height' => 50,
        '#image_format' => 'jpg',
        '#barcolor' => '#000000',
        '#bgcolor' => '#FFFFFF',
      );
  }
  $variables['receipt_date'] = format_date(REQUEST_TIME);
  drupal_add_css(drupal_get_path('module', 'commerce_pos') . '/theme/pos-receipt.css', array('media' => 'print'));

  $order_pane = new POSPane_Order('order', 'Order', array('interactive' => FALSE));
  $variables['receipt_body'] = $order_pane->build($state, $registry);
}