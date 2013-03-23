<?php


class POSCommand_Void extends POS_Command {

  function access($line_item_id, POS_State $state) {
    if (!$line_item_id) {
      return FALSE;
    }
    if ($line_item = commerce_line_item_load($line_item_id)) {
      return commerce_line_item_access('delete', $line_item, $state->getCashier());
    }
  }

  function execute($line_item_id, POS_State $state) {
    $order = $state->getOrder();

    // Ensure that the line item is actually on the current order before deleting.
    foreach ($order->commerce_line_items[LANGUAGE_NONE] as $item) {
      if ($item['line_item_id'] == $line_item_id) {
        commerce_line_item_delete($line_item_id);
        drupal_set_message('Line item was removed');
        return;
      }
    }

    throw new InvalidArgumentException('Line item does not exist on this order.');
  }

}