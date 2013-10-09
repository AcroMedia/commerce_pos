<?php


class POSCommand_AddProduct extends POS_Command {

  public function matchesInput($input) {
    if($arg = parent::parseInput($input)) {
      list($sku) = $this->reParseInput($arg);
      return (bool) commerce_product_load_by_sku($sku);
    }
    return FALSE;
  }

  public function access(CommercePOS $pos, $input = '') {
    return commerce_order_access('update', $pos->getState()->getOrder(), $pos->getState()->getCashier());
  }

  function execute(CommercePOS $pos, $input = '') {
    list($sku, $quant) = $this->reParseInput($input);
    /**
     * Most of this was grabbed from commerce_cart_add_to_cart_form_submit().
     * We currently have no ability to handle configurable line items.
     */

    if ($product = commerce_product_load_by_sku($sku)) {
      $order = $pos->getState()->getOrder();
      // We must have an order ID to create line items.
      if (!$order->order_id) {
        commerce_order_save($order);
        // We need to manually set the order here, since
        // commerce_pos_order_is_on_pos() doesn't know about our order yet.
        $pos->getState()->setOrder($order);
      }
      $line_item = commerce_product_line_item_new($product, $quant, $order->order_id);
      drupal_alter('commerce_product_calculate_sell_price_line_item', $line_item);
      rules_invoke_event('commerce_product_calculate_sell_price', $line_item);

      // Only attempt an Add to Cart if the line item has a valid unit price.
      $line_item_wrapper = entity_metadata_wrapper('commerce_line_item', $line_item);

      if (!is_null($line_item_wrapper->commerce_unit_price->value())) {
        commerce_line_item_save($line_item);

        $order_wrapper = entity_metadata_wrapper('commerce_order', $order);

        // Add it to the order's line item reference value.
        $order_wrapper->commerce_line_items[] = $line_item_wrapper->value();
        // Save the updated order.
        commerce_order_save($order);
        drupal_set_message(t('Added @name to order.', array('@name' => $product->title)));
        return;
      }
      else {
        throw new RuntimeException('There was a problem adding this product to the order.');
      }
    }
  }

  protected function reParseInput($input) {
    preg_match('/(?:(\d+)\*)?(\S+)/', $input, $matches);
    return array(
      $matches[2], // SKU
      $matches[1] ? $matches[1] : 1, // Quantity
    );
  }
}
