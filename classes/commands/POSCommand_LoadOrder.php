<?php


class POSCommand_LoadOrder extends POS_Command_Modal {

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

  function modalPage($js, POS_State $state) {
    $output = commerce_embed_view('pos_order_selection', 'default', array(), $_GET['q']);
    if ($js) {
      return array(
        ctools_modal_command_display(drupal_get_title(), $output)
      );
    }
    return $output;
  }
}