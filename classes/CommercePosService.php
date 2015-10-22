<?php

/**
 * @file
 * PosService class definition.
 */

class CommercePosService {
  const TRANSACTION_TYPE_SALE = 1;
  const TRANSACTION_TYPE_RETURN = 2;

  static $transactions = array();

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

      if (isset($active_transactions[$type]['commerce_pos_in_progress'])) {
        $transaction_id = $active_transactions[$type]['commerce_pos_in_progress'][0];
        $current_transactions[$uid][$type] = self::loadTransaction($transaction_id);
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
    $current_transaction = self::getCurrentTransaction($transaction->type, $transaction->uid);

    if ($current_transaction->transactionId != $transaction->transactionId) {
      throw new Exception(t('Cannot set current @type transaction for @uid, the user already has one.', array(
        '@uid' => $transaction->uid,
        '@type' => $transaction->type,
      )));
    }
    else {
      if (empty($transaction->transactionId)) {
        // @TODO: make sure this is actually updating the static value.
        $current_transaction = $transaction;
      }
    }
  }

  /**
   * Load a Commerce POS transaction.
   */
  public static function loadTransaction($transaction_id, $reset = FALSE) {
    if ($reset) {
      self::$transactions = array();
    }

    if (!isset(self::$transactions[$transaction_id])) {
      self::$transactions[$transaction_id] = new CommercePosTransaction($transaction_id);
    }

    return self::$transactions[$transaction_id];
  }

  /**
   * Looks up the POS transaction for a specific order.
   */
  public static function getOrderTransaction($order_id) {
    $result = db_query('SELECT transaction_id FROM {commerce_pos_transaction}
      WHERE order_id = :order_id', array(
      ':order_id' => $order_id,
    ));

    if ($transaction_id = $result->fetchField()) {
      return self::loadTransaction($transaction_id);
    }

    return FALSE;
  }

  /**
   * Retrieves a list of all "parked" transactions for a user.
   */
  public static function getParkedTransactions($type, $uid) {
    $transactions = self::getAllActiveTransactions($uid);
    return !empty($transactions[$type]['commerce_pos_parked']) ? $transactions[$type]['commerce_pos_parked'] : array();
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
          'commerce_pos_in_progress',
          'commerce_pos_parked',
        ), 'IN')
        ->condition('t.order_id', 0));

      $query->condition('t.uid', $uid);

      $result = $query->execute();

      foreach ($result as $row) {
        if (empty($row->status)) {
          $row->status = 'commerce_pos_in_progress';
        }

        $transactions[$uid][$row->type][$row->status][] = $row->transaction_id;
      }
    }

    return $transactions[$uid];
  }

  /**
   * Retrieves a list of commerce_product types that can be added to a POS
   * transaction.
   */
  public static function allowedProductTypes() {
    $types = array();

    foreach (variable_get('commerce_pos_available_products', array()) as $type => $allowed) {
      if ($allowed) {
        $types[] = $type;
      }
    }

    return $types;
  }
}
