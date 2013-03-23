<?php


class POSCommand_Payment extends POS_Command_Modal {

  public function access($input, POS_State $state) {
    $order = $state->getOrder();

    // We can't take payment for an order without an ID.
    if (empty($state->getOrder()->order_id)) {
      return FALSE;
    }

    // Exit early if the user has no access to create transactions.
    if (!commerce_payment_transaction_order_access('create', $state->getOrder(), $state->getCashier())) {
      return FALSE;
    }

    // Exit early if the order has no balance.
    $total = commerce_payment_order_balance($state->getOrder());
    if (!$total || $total['amount'] <= 0) {
      return FALSE;
    }

    // Finally, ensure that the stated payment method is applicable for this order.
    if (empty($order->payment_methods)) {
      $order->payment_methods = array();
      rules_invoke_all('commerce_payment_methods', $order);
    }
    foreach ($order->payment_methods as $instance_id => $method_info) {
      if ($method_info['method_id'] == $this->config['method_id']) {
        $this->instance_id = $instance_id;
        return TRUE;
      }
    }
    return FALSE;
  }

  /**
   * This command never actually runs.  It just provides a button to pop
   * up a modal window.
   */
  public function shouldRun() {
    return FALSE;
  }

  public function execute($input, POS_State $state) {
    //No-op.
  }

  public function getModalUrl() {
    return 'admin/commerce/pos/nojs/' . $this->id;
  }

  public function modalPage($js, POS_State $state) {
    module_load_include('forms.inc', 'commerce_payment', 'includes/commerce_payment');

    $form_state = array(
      'title' => drupal_get_title(),
      'build_info' => array('args' => array($state->getOrder())),
      'payment_method' => commerce_payment_method_instance_load($this->instance_id),
      'ajax' => $js,
    );

    if (!$js) {
      return drupal_build_form('commerce_payment_order_transaction_add_form', $form_state);
    }

    $output = ctools_modal_form_wrapper('commerce_payment_order_transaction_add_form', $form_state);

    return $output && !$form_state['executed'] ? $output : FALSE;
  }
}
