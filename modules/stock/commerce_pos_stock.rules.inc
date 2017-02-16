<?php
/**
 * @file
 * commerce_pos_stock.rules.inc
 */

/**
 * Implements hook_rules_action_info().
 */
function commerce_pos_stock_rules_action_info() {
  $actions = array();

  $actions['commerce_pos_stock_increase_by_line_item'] = array(
    'label' => t('Increase the product stock level, given a line item, with damage check.'),
    'group' => t('Commerce Stock (ss)'),
    'parameter' => array(
      'commerce_line_item' => array(
        'type' => 'commerce_line_item',
        'label' => t('Line item'),
      ),
    ),
  );

  return $actions;
}

/**
 * Adds the sold amount in a line item to stock, if
 * $line_item->data['commerce_pos_stock_add_to_stock'] is set.
 *
 * Typically used when a line item is removed from an order (as when items are
 * added to and removed from cart).
 *
 * @param $line_item
 *   A line item object.
 *
 * @see commerce_ss_increase_by_line_item()
 */
function commerce_pos_stock_increase_by_line_item($line_item) {
  if (in_array($line_item->type, commerce_product_line_item_types()) && !empty($line_item->data['commerce_pos_stock_add_to_stock'])) {
    module_load_include('inc', 'commerce_ss', 'commerce_ss.rules');
    // The product SKU that will have its stock level adjusted.
    $sku = commerce_ss_line_item_sku($line_item);
    $product = commerce_product_load_by_sku($sku);
    if (commerce_ss_product_type_enabled($product->type)) {
      if (!(commerce_ss_product_type_override_enabled($product->type)
        && isset($product->commerce_stock_override['und']) && $product->commerce_stock_override['und'][0]['value'] == 1)
      ) {

        $qty = $line_item->quantity;
        // Subtract the sold amount from the available stock level.
        commerce_ss_stock_adjust($product, $qty);
      }
    }
  }
}
