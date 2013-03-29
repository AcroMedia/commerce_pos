<?php


class POSCommand_SetUser extends POS_Command {

  function access($input, POS_State $state) {
    return commerce_order_access('update', $state->getOrder(), $state->getCashier());
  }

  function execute($uid, POS_State $state) {
    if (!$uid === 0 && !$account = user_load($uid)) {
      throw new InvalidArgumentException('Invalid user.');
    }
    $order = $state->getOrder();
    $order->uid = $uid;
    if (empty($order->isNew)) {
      commerce_order_save($order);
    }
    $state->setOrder($order);
  }
}