<?php

/**
 * @file
 */

/**
 *
 */
class CommercePosRegisterBase extends CommercePosTransactionBase implements CommercePosTransactionBaseInterface {

  /**
   * Subscribe to transaction actions.
   */
  public function subscriptions() {
    $subscriptions = parent::subscriptions();
    $subscriptions['saveBefore'][] = 'detectRegister';
    $subscriptions['createNewOrderBefore'][] = 'detectRegister';
    return $subscriptions;
  }

  /**
   * Act upon a transaction being saved.
   *
   * This checks to see if the transaction's register_id is different than the
   * register ID in the session and will modify it as needed.
   */
  public function detectRegister() {
    if ($current_register_id = commerce_pos_register_get_current_register()) {
      if ($current_register_id != $this->transaction->registerId) {
        $this->transaction->registerId = $current_register_id;
      }
    }
  }

}
