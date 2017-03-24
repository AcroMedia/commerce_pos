<?php

/**
 * @file
 * CommercePosTransactionBaseInterface.php
 */

/**
 * Base transaction class, only has a transaction object and some empty defaults.
 */
interface CommercePosTransactionBaseInterface {

  /**
   * Basic contruct, only sets the transaction by default.
   */
  public function __construct(CommercePosTransaction $transaction);

  /**
   * Base actions, none by default.
   */
  public function actions();

  /**
   * Base subscriptions, none by default.
   */
  public function subscriptions();

  /**
   * Base events, none by default.
   */
  public function events();

}
