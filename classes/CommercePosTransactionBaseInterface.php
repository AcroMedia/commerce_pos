<?php

/**
 * @file
 * CommercePosTransactionBaseInterface.php
 */

interface CommercePosTransactionBaseInterface {

  public function __construct(CommercePosTransaction $transaction);
  public function actions();
  public function subscriptions();
  public function events();
}
