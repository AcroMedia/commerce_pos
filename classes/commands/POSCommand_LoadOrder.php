<?php


class POSCommand_LoadOrder extends POS_Command {

  function access($order_id, POS_State $state) {
    if ($order_id) {
      $order = commerce_order_load($order_id);
      return commerce_order_access('view', $order, $state->getCashier());
    }
    else {
      return commerce_order_access('view');
    }
  }

  function execute($order_id, POS_State $state) {
    if ($order = commerce_order_load($order_id)) {
      $state->setOrder($order);
    }
    else {
      throw new InvalidArgumentException(t('Invalid order ID: %id', array('%id' => $order_id)));
    }
  }
}