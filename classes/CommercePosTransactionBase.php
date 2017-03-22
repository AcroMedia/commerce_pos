<?php

/**
 * @file
 * PosTransactionBase class definition.
 */

/**
 *
 */
class CommercePosTransactionBase implements CommercePosTransactionBaseInterface {

  /**
   * @var CommercePosTransaction
   */
  protected $transaction;

  /**
   *
   */
  public function __construct(CommercePosTransaction $transaction) {
    $this->transaction = $transaction;
  }

  /**
   *
   */
  public function actions() {
    return array();
  }

  /**
   *
   */
  public function subscriptions() {
    return array();
  }

  /**
   *
   */
  public function events() {
    return array();
  }

}
