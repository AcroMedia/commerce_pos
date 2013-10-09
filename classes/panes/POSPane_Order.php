<?php


class POSPane_Order extends POS_Pane {

  function build(CommercePOS $pos, POS_Interface $interface, $js = FALSE) {
    $order = $pos->getState()->getOrder();
    $void_col_used = FALSE;

    $rows = array();
    $headers = array(
      t('Name'),
      t('Qty'),
      t('Price'),
    );
    $wrapper = entity_metadata_wrapper('commerce_order', $order);

    foreach ($wrapper->commerce_line_items as $line_item) {
      if (!$line_item->value()) {
        // Handle broken line items by not skipping them - not sure what else to do here.
        continue;
      }
      $row = array(
        commerce_line_item_title($line_item->value()),
        $line_item->quantity->value(),
        commerce_currency_format(
          $line_item->commerce_total->amount->raw(),
          $line_item->commerce_total->currency_code->raw(),
          $line_item
        )
      );

      if (($button = $interface->getButton('void')) && $button->access($pos, $line_item->line_item_id->raw(), $pos)) {
        $void_col_used = TRUE;
        $row[] = $button->render($pos, NULL, $line_item->line_item_id->raw());
      }


      $rows[] = array(
        'data' => $row,
        'class' => array('line-item'),
        'data-line-item-id' => $line_item->line_item_id->raw()
      );
    }

    $payments = commerce_payment_transaction_load_multiple(array(), array('order_id' => $order->order_id));
    $transaction_statuses = commerce_payment_transaction_statuses();
    $totals = array();
    foreach ($payments as $payment) {
      if (commerce_payment_transaction_access('view', $payment, $pos->getState()->getCashier())) {
        $row = array(
          commerce_payment_method_get_title('title', $payment->payment_method),
          '',
          commerce_currency_format($payment->amount, $payment->currency_code, $payment),
        );
        if ($void_col_used) {
          $row[] = '';
        }
        $rows[] = array(
          'data' => $row,
          'class' => array('payment'),
        );
      }

      // Calculate totals for the next line.
      if (!empty($transaction_statuses[$payment->status]) && $transaction_statuses[$payment->status]['total']) {
        if (isset($totals[$payment->currency_code])) {
          $totals[$payment->currency_code] += $payment->amount;
        }
        else {
          $totals[$payment->currency_code] = $payment->amount;
        }
      }
    }

    if (!empty($rows)) {
      $total_rows = commerce_payment_totals_rows($totals, $order);
      foreach ($total_rows as $row) {
        // Stick an empty value in for quantity.
        array_splice($row['data'], 1, 0, '');
        if ($void_col_used) {
          $row['data'][] = '';
        }
        $rows[] = $row;
      }
    }


    if ($void_col_used) {
      $headers[] = t('Void');
    }

    return array(
      'info' => array(
        '#theme' => 'pos_order_info',
        '#order' => $order,
      ),
      'table' => array(
        '#theme' => 'table__pos_line_items',
        '#rows' => $rows,
        '#header' => $headers,
        '#empty' => 'No Items in order.',
        '#attributes' => array('class' => array('commerce-pos-order')),
      )
    );
  }
}