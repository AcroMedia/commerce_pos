<?php


class POSCommand_LoadOrder extends POS_Command {

  function access(CommercePOS $pos, $input = '') {
    if ($input) {
      $order = commerce_order_load($input);
      return commerce_order_access('view', $order, $pos->getState()->getCashier());
    }
    else {
      return commerce_order_access('view');
    }
  }

  function execute(CommercePOS $pos, $input = '') {
    if ($order = commerce_order_load($input)) {
      $pos->getState()->setOrder($order);
    }
    else {
      throw new InvalidArgumentException(t('Invalid order ID: %id', array('%id' => $input)));
    }
  }
}