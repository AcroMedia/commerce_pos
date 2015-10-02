<?php

/**
 * @file
 * PosTransactionBase class definition.
 */

class CommercePosTransactionBase {

  protected $transaction;

  public function __construct(CommercePosTransaction $transaction) {
    $this->transaction = $transaction;
  }
}
