<?php


class POSCommand_Void extends POS_Command {

  function access(CommercePOS $pos, $input = '') {
    if (!$input) {
      return FALSE;
    }
    if ($line_item = commerce_line_item_load($input)) {
      return commerce_line_item_access('delete', $line_item, $pos->getState()->getCashier());
    }
  }

  function execute(CommercePOS $pos, $input = '') {
    $order = $pos->getState()->getOrder();

    // Ensure that the line item is actually on the current order before deleting.
    foreach ($order->commerce_line_items[LANGUAGE_NONE] as $item) {
      if ($item['line_item_id'] == $input) {
        commerce_line_item_delete($input);
        drupal_set_message('Line item was removed');
        return;
      }
    }

    throw new InvalidArgumentException('Line item does not exist on this order.');
  }

}