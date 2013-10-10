<?php

function commerce_pos_rules_event_info() {
  $events = array();

  $events['commerce_pos_order_paid'] = array(
    'label' => t('A POS order has been fully paid.'),
    'group' => t('Commerce POS'),
    'variables' => array_merge(
      entity_rules_events_variables('commerce_order', t('Order', array(), array('context' => 'a drupal commerce order')), TRUE, TRUE),
      entity_rules_events_variables('commerce_payment_transaction', t('Last completed transaction'), TRUE)
    ),
    'access callback' => 'commerce_order_rules_access',
  );

  return $events;
}

function commerce_pos_rules_condition_info() {
  return array(
    'rules_condition_order_is_on_pos' => array(
      'base' => 'commerce_pos_order_is_on_pos',
      'label' => t('Order is currently on the POS'),
      'parameter' => array(
        'order' => array('label' => t('Order to Check'), 'type' => 'commerce_order'),
      ),
      'group' => t('Commerce POS'),
    ),
  );
}