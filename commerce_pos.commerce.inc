<?php


/**
 * Implements hook_commerce_order_update().
 *
 * Update the order on the POS if it is resaved.
 */
function commerce_pos_commerce_order_update($order) {
  if (commerce_pos_order_is_on_pos($order)) {
    $state = POS_State::get();
    $state->setOrder($order);
  }
}

/**
 * Implements hook_commerce_order_state_info().
 */
function commerce_pos_commerce_order_state_info() {
  $order_states = array();
  $order_states['pos_created'] = array(
    'name' => 'pos_created',
    'title' => t('Created on POS'),
    'description' => t('Orders in this state have been started on the POS, but not finished.'),
    'weight' => -5,
    'default_status' => 'pos_in_progress',
  );
  return $order_states;
}

/**
 * Implements hook_commerce_order_status_info().
 */
function commerce_pos_commerce_order_status_info() {
  $order_statuses = array();
  $order_statuses['pos_completed'] = array(
    'name' => 'pos_completed',
    'title' => t('POS Completed'),
    'state' => 'completed',
  );
  $order_statuses['pos_in_progress'] = array(
    'name' => 'pos_in_progress',
    'title' => t('POS In Progress'),
    'state' => 'pos_created',
  );

  return $order_statuses;
}