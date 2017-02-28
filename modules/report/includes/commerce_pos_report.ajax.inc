<?php

/**
 * @file
 * AJAX callbacks for Commerce POS Report.
 */

/**
 * Callback for retrieving an order's details.
 */
function commerce_pos_report_order_details_js($order) {
  $commands = array();

  $commands[] = ajax_command_html('td.commerce-pos-report-journal-role-order-info[data-order-id=' . $order->order_id . ']', theme('commerce_pos_report_order_details', array(
    'order' => $order,
  )));

  $commands[] = ajax_command_invoke('td.commerce-pos-report-journal-role-order-info[data-order-id=' . $order->order_id . ']', 'toggleClass', array('element-invisible'));

  return array('#type' => 'ajax', '#commands' => $commands);
}
