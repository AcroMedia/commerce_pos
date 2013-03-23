<?php


function commerce_pos_default_rules_configuration() {
  // Add a reaction rule to update an order to the default status of the pending
  // order status upon accepting full payment on the POS.
  $rule = rules_reaction_rule();
  $rule->label = t('Update order status on full payment of POS orders');
  $rule->active = TRUE;
  $rule
    ->event('commerce_payment_order_paid_in_full')
    ->condition('rules_condition_order_is_on_pos', array('order:select' => 'commerce-order'))
    ->action('commerce_order_update_status', array(
      'commerce_order:select' => 'commerce-order',
      'order_status' => 'pos_completed',
    ));


  $rules['commerce_pos_update_order_status_on_payment'] = $rule;

  return $rules;
}