<?php

/**
 * Class CommercePosTerminalExampleService
 */
class CommercePosTerminalExampleService implements CommercePosTerminalServiceInterface {

  /**
   * The message for the transaction.
   *
   * @var string
   */
  protected $message = '';

  /**
   * CommercePosTerminalExampleService constructor.
   */
  public function __construct() {
  }

  /**
   * {@inheritdoc}
   */
  public function getPaymentType() {
    return 'credit';
  }

  /**
   * {@inheritdoc}
   */
  public static function getPaymentTypes() {
    return array(
      'credit' => t('Credit'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function purchase($transaction) {
    $amount = $transaction->amount / 100;
    $cents = $amount - floor($amount);
    if ($cents > 0) {
      sleep($cents * 100);
    }

    if ($amount % 2 === 0) {
      $this->message = 'Even amounts are always successful';
      $transaction->status = COMMERCE_PAYMENT_STATUS_SUCCESS;
      $transaction->remote_status = 'success';
    }
    else {
      $this->message = 'Odd amounts are always unsuccessful';
      $transaction->status = COMMERCE_PAYMENT_STATUS_FAILURE;
      $transaction->remote_status = 'fail';
    }

    $transaction->message = $this->message;
    $transaction->remote_id = rand(1000, 9999);
    return $transaction;
  }

  /**
   * {@inheritdoc}
   */
  public function refund($transaction) {
    return $this->purchase($transaction);
  }

  /**
   * {@inheritdoc}
   */
  public function void($transaction) {
    return array(
      'success' => TRUE,
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getTransactionMessage() {
    return $this->message;
  }

  /**
   * {@ineritdoc}
   */
  public function saved() {
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setLocation($location_id = NULL) {
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setRegister($register_id = NULL) {
    return $this;
  }

}
