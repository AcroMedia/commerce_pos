<?php


class POSCommand_SetUser extends POS_Command {

  function access(CommercePOS $pos, $input = '') {
    return commerce_order_access('update', $pos->getState()->getOrder(), $pos->getState()->getCashier());
  }

  function execute(CommercePOS $pos, $input = '') {
    if (!$input === 0 && !$account = user_load($input)) {
      throw new InvalidArgumentException('Invalid user.');
    }
    $order = $pos->getState()->getOrder();
    $order->uid = $input;
    if (empty($order->isNew)) {
      commerce_order_save($order);
    }
    $pos->getState()->setOrder($order);
  }
}