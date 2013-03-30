<?php
/**
 * @file
 *  This class represents a button that pops up a payment form in a modal window.
 */

class POS_Button_Payment extends POS_Button_Modal {

  public function access(CommercePOS $pos, $input) {
    $order = $pos->getState()->getOrder();

    // We can't take payment for an order without an ID.
    if (empty($order->order_id)) {
      return FALSE;
    }

    // Exit early if the user has no access to create transactions.
    if (!commerce_payment_transaction_order_access('create', $order, $pos->getState()->getCashier())) {
      return FALSE;
    }

    // Exit early if the order has no balance.
    $total = commerce_payment_order_balance($order);
    if (!$total || $total['amount'] <= 0) {
      return FALSE;
    }

    // Check whether this method is allowed on this order.
    return (bool) $this->getInstanceId($order);
  }

  public function modalPage(CommercePOS $pos, $js) {
    module_load_include('forms.inc', 'commerce_payment', 'includes/commerce_payment');

    $form_state = array(
      'title' => drupal_get_title(),
      'build_info' => array('args' => array($pos->getState()->getOrder())),
      'payment_method' => commerce_payment_method_instance_load($this->getInstanceID($pos->getState()->getOrder())),
      'ajax' => $js,
    );

    if (!$js) {
      return drupal_build_form('commerce_payment_order_transaction_add_form', $form_state);
    }

    $output = ctools_modal_form_wrapper('commerce_payment_order_transaction_add_form', $form_state);

    return $output && !$form_state['executed'] ? $output : FALSE;
  }

  protected function getInstanceID($order) {
    // Finally, ensure that the stated payment method is applicable for this order.
    if (empty($order->payment_methods)) {
      $order->payment_methods = array();
      rules_invoke_all('commerce_payment_methods', $order);
    }
    foreach ($order->payment_methods as $instance_id => $method_info) {
      if ($method_info['method_id'] == $this->config['method_id']) {
        return $instance_id;
      }
    }
    return FALSE;
  }
}