<?php

/**
 * @file
 */

class CommercePosLocationBase extends CommercePosTransactionBase implements CommercePosTransactionBaseInterface {

  /**
   * Subscribe to transaction actions.
   */
  public function subscriptions() {
    $subscriptions = parent::subscriptions();
    $subscriptions['saveBefore'][] = 'detectLocation';
    $subscriptions['createNewOrderBefore'][] = 'detectLocation';
    return $subscriptions;
  }

  /**
   * Act upon a transaction being saved.
   *
   * This checks to see if the transaction's location_id is different than the
   * location ID in the session and will modify it as needed.
   */
  public function detectLocation() {
    if ($current_location_id = commerce_pos_location_get_current_location()) {
      if ($current_location_id != $this->transaction->locationId) {
        $this->transaction->locationId = $current_location_id;
      }
    }
  }
}
