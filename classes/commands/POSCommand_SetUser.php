<?php


class POSCommand_SetUser extends POS_Command_Modal {

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

  function modalPage($js, POS_State $state) {
    $output = commerce_embed_view('pos_user_selection', 'default', array(), $_GET['q']);
    if ($js) {
      return array(
        ctools_modal_command_display(drupal_get_title(), $output)
      );
    }
    return $output;
  }
}