<?php

/**
 * @file
 * Rules definitions.
 */

/**
 * Implements hook_rules_event_info().
 */
function commerce_pos_rules_event_info() {
  return array(
    'commerce_pos_transaction_completed' => array(
      'label' => t('A Commerce POS transaction was completed'),
      'module' => 'commerce_pos',
      'variables' => array(
        'order' => array(
          'type' => 'commerce_order',
          'label' => t("The transaction's Commerce order"),
        ),
        'transaction_type' => array(
          'type' => 'text',
          'label' => t('The Commerce POS transaction type'),
        ),
      ),
    ),
  );
}

