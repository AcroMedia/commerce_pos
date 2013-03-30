<?php


function template_preprocess_pos_receipt(&$variables) {
  $pos = empty($variables['pos']) ? CommercePOS::instance() : $variables['pos'];

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

  if (module_exists('barcode') && $order_command = $pos->getCommand('load_order')) {
      $variables['receipt_footer']['barcode'] = array(
        '#theme' => 'barcode_image',
        '#barcode_value' => $order_command->createInput($pos->getState()->getOrder()->order_id),
        '#encoding' => variable_get('commerce_pos_barcode_format', 'EAN-13'),
        '#height' => 50,
        '#image_format' => 'jpg',
        '#barcolor' => '#000000',
        '#bgcolor' => '#FFFFFF',
      );
  }
  $variables['receipt_date'] = format_date(REQUEST_TIME);
  drupal_add_css(drupal_get_path('module', 'commerce_pos') . '/theme/pos-receipt.css', array('media' => 'print'));

  $order_pane = new POSPane_Order('order', 'Order');
  // Using an empty interface ensures we won't have buttons on our receipt.
  $variables['receipt_body'] = $order_pane->build($pos, new POS_Interface($pos, array(), array()));
}