<?php

class POSCommand_PriceCheck extends POS_Command {

  function access($input, POS_State $state) {
    if ($input) {
      if ($product = commerce_product_load_by_sku($input)) {
        return commerce_product_access('view', $product, $state->getCashier());
      }
      throw new InvalidArgumentException('Invalid SKU');
    }
    return TRUE;
  }

  public function execute($input, POS_State $state) {
    $product = commerce_product_load_by_sku($input);
    $price = commerce_product_calculate_sell_price($product);
    drupal_set_message(t('Price for @name: @price', array(
      '@price' => commerce_currency_format($price['amount'], $price['currency_code'], $product),
      '@name' => entity_label('commerce_product', $product),
    )));
  }
}