<?php

function commerce_pos_rules_event_info() {
  $events = array();

  $events['commerce_pos_complete'] = array(
    'label' => t('Completing a POS order'),
    'group' => t('Commerce POS'),
    'variables' => array(
      'commerce_order' => array(
        'type' => 'commerce_order',
        'label' => t('Completed order', array(), array('context' => 'a drupal commerce order')),
      ),
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