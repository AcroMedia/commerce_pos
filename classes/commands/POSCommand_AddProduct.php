<?php


class POSCommand_AddProduct extends POS_Command_Modal {

  public function shouldRun($input) {
    list($sku) = $this->reParseInput($input);
    return (bool) commerce_product_load_by_sku($sku);
  }

  public function access($input, POS_State $state) {
    return commerce_order_access('update', $state->getOrder(), $state->getCashier());
  }

  function execute($input, POS_State $state) {
    list($sku, $quant) = $this->reParseInput($input);
    /**
     * Most of this was grabbed from commerce_cart_add_to_cart_form_submit().
     * We currently have no ability to handle configurable line items.
     */

    if ($product = commerce_product_load_by_sku($sku)) {
      $order = $state->getOrder();
      // We must have an order ID to create line items.
      if (!$order->order_id) {
        commerce_order_save($order);
      }
      $line_item = commerce_product_line_item_new($product, $quant, $order->order_id);
      drupal_alter('commerce_product_calculate_sell_price_line_item', $line_item);
      rules_invoke_event('commerce_product_calculate_sell_price', $line_item);

      // Only attempt an Add to Cart if the line item has a valid unit price.
      $line_item_wrapper = entity_metadata_wrapper('commerce_line_item', $line_item);

      if (!is_null($line_item_wrapper->commerce_unit_price->value())) {
        commerce_line_item_save($line_item);

        $order = $state->getOrder();
        $order_wrapper = entity_metadata_wrapper('commerce_order', $order);

        // Add it to the order's line item reference value.
        $order_wrapper->commerce_line_items[] = $line_item_wrapper->value();
        // Save the updated order.
        commerce_order_save($order);
        $state->setOrder($order);
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

  public function modalPage($js, POS_State $state) {
    $output = commerce_embed_view('pos_product_selection', 'default', array(), $_GET['q']);
    if ($js) {
      return array(
        ctools_modal_command_display(drupal_get_title(), $output)
      );
    }
    return $output;
  }
}