<?php

/**
 * @file
 * PosTransactionBase class definition.
 */

/**
 * Base transaction class, only has a transaction object and some empty defaults.
 */
class CommercePosTransactionBase implements CommercePosTransactionBaseInterface {

  /**
   * Transaction object that is required for any transaction functionality.
   *
   * @var CommercePosTransaction
   */
  protected $transaction;

  /**
   * {@inheritdoc}
   */
  public function __construct(CommercePosTransaction $transaction) {
    $this->transaction = $transaction;
  }

  /**
   * {@inheritdoc}
   */
  public function actions() {
    return array();
  }

  /**
   * {@inheritdoc}
   */
  public function subscriptions() {
    return array();
  }

  /**
   * {@inheritdoc}
   */
  public function events() {
    return array();
  }

}
