<?php

/**
 * CommercePos class definition.
 */

class CommercePos {
  const TRANSACTION_TYPE_SALE = 1;
  const TRANSACTION_TYPE_RETURN = 2;

  /**
   * Retrieves the current POS transaction for a user.
   *
   * "Current" is defined as in the process of being created, and not parked.
   *
   * @param int $type
   *   The type of current transaction to retrieve.
   * @param int $uid
   *   The user ID to retrieve the transaction for.
   * @param bool $reset
   *   If TRUE, the static cache will be reset.
   *
   * @return object|bool
   */
  public static function getCurrentTransaction($type, $uid, $reset = FALSE) {
    $current_transactions = &drupal_static('commerce_pos_current_transactions', array());

    if ($reset) {
      $current_transactions = array();
    }

    if (!isset($current_transactions[$uid][$type])) {
      $current_transactions[$uid][$type] = FALSE;
      $active_transactions = self::getAllActiveTransactions($uid, $reset);

      if (isset($active_transactions[$type]['commerce_pos_creating'])) {
        $transaction_id = $active_transactions[$type]['commerce_pos_creating'][0];
        $current_transactions[$uid][$type] = new CommercePosTransaction($transaction_id);
      }
    }

    return $current_transactions[$uid][$type];
  }

  /**
   * Sets the current transaction for a user.
   *
   * @param \CommercePosTransaction $transaction
   *
   * @throws \Exception
   */
  public static function setCurrentTransaction(CommercePosTransaction $transaction) {
    $current_transactions = &drupal_static('commerce_pos_current_transactions', array());

    if (!isset($current_transactions[$transaction->uid][$transaction->type])) {
      $current_transactions[$transaction->uid][$transaction->type] = $transaction;
    }
    else {
      throw new Exception(t('Cannot set current @type transaction for @uid, the user already has one.', array(
        '@uid' => $transaction->uid,
        '@type' => $transaction->type,
      )));
    }
  }

  /**
   * Creates a new POS transaction for a user.
   *
   * @param int $type
   *   The type of transaction to create.
   * @param int $uid
   *   The user ID of the employee/admin the transaction is being created for.
   *
   * @return CommercePosTransaction
   */
  public static function createNewTransaction($type, $uid) {
    $transaction = new CommercePosTransaction(NULL, $type, $uid);
    $transaction->save();
    return $transaction;
  }

  /**
   * Retrieves a list of all POS transactions for a user that are either parked
   * or are currently being created.
   *
   * @param int $uid
   *   The user ID to retrieve transactions for.
   * @param bool $reset
   *   If TRUE, the static cache will be reset.
   *
   * @return array
   */
  protected static function getAllActiveTransactions($uid, $reset = FALSE) {
    $transactions = &drupal_static('commerce_pos_all_active_transactions', array());

    if ($reset) {
      $transactions = array();
    }

    if (!isset($transactions[$uid])) {
      $transactions[$uid] = array();

      $query = db_select('commerce_pos_transaction', 't');
      $query->fields('t', array(
        'transaction_id',
        'type',
      ));
      $query->fields('o', array(
        'status',
      ));
      $query->leftJoin('commerce_order', 'o', 'o.order_id = t.order_id');

      $query->condition(db_or()
        ->condition('o.status', array(
          'commerce_pos_creating',
          'commerce_pos_parked',
        ), 'IN')
        ->condition('t.order_id', 0));

      $query->condition('t.uid', $uid);

      $result = $query->execute();

      foreach ($result as $row) {
        if (empty($row->status)) {
          $row->status = 'commerce_pos_creating';
        }

        $transactions[$uid][$row->type][$row->status][] = $row->transaction_id;
      }
    }

    return $transactions[$uid];
  }
}
