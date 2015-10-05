<?php

/**
 * @file
 * PosTransactionBase class definition.
 */

class CommercePosTransactionBase {

  /* @var CommercePosTransaction $transaction */
  protected $transaction;

  public function __construct(CommercePosTransaction $transaction) {
    $this->transaction = $transaction;
  }
}
