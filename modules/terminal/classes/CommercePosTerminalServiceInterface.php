<?php

/**
 * @file
 * An interface for terminal functionality in commerce_pos_terminal.
 */

/**
 *
 */
interface CommercePosTerminalServiceInterface {

  /**
   * CommercePosTerminalServiceInterface constructor.
   */
  public function __construct();

  /**
   * Create a new purchase transaction.
   *
   * @param $transaction
   *   A commerce transaction.
   *
   * @return object
   *   A commerce transaction object.
   */
  public function purchase($transaction);

  /**
   * Create a new refund transaction.
   *
   * @param $transaction
   *   A commerce transaction.
   *
   * @return object
   *   A commerce transaction object.
   */
  public function refund($transaction);

  /**
   * Create a new void transaction.
   *
   * @param $transaction
   *   A commerce transaction.
   *
   * @return array
   *   An array with the following keys:
   *   - success: bool indicating the success or failure of the void attempt.
   *   - message: Optional string.
   */
  public function void($transaction);

  /**
   * Called after the commerce transaction is saved.
   * Useful to a service that needs, for example, to send a message that the
   * transaction was committed.
   */
  public function saved();

  /**
   * Set the location for the transaction.
   *
   * @param $location_id
   *
   * @return $this
   */
  public function setLocation($location_id = NULL);

  /**
   * Set the register for the transaction.
   *
   * @param $register_id
   *
   * @return $this
   */
  public function setRegister($register_id = NULL);

  /**
   * Get a message from the transaction.
   *
   * @return string
   */
  public function getTransactionMessage();

  /**
   * Gets the payment type from the transaction.
   *
   * @return string
   *   The type of payment made in the transaction. Should be one of the types
   *   in getPaymentTypes().
   */
  public function getPaymentType();

  /**
   * Gets a list of payment types used by the service.
   *
   * This is used in mapping payment types from the terminal service to payment
   * methods in Drupal Commerce.
   * Eg:
   * array(
   *   'DEBIT ACCOUNT' => t('Debit'),
   *   'GIFTCARD' => t('Gift card'),
   *   'CASH' => t('Cash'),
   * );
   *
   * @return array
   *   An array of payment types. Use the exact string as the keys and a friendly
   *   name as the value.
   */
  public static function getPaymentTypes();

}
