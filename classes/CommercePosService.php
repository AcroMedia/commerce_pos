<?php

/**
 * @file
 * PosService class definition.
 */

/**
 * Service for basic POS functions, such as getting transactions and allowable types.
 */
class CommercePosService {
  const TRANSACTION_TYPE_SALE = 'sale';
  const TRANSACTION_TYPE_RETURN = 'return';
  const TRANSACTION_TYPE_EXCHANGE = 'exchange';

  static private $transactions = array();

  /**
   * Retrieves the current POS transaction for a user.
   *
   * "Current" is defined as in the process of being created, and not parked.
   *
   * @param int $cashier_id
   *   The cashier ID to retrieve the transaction for.
   * @param bool $reset
   *   If TRUE, the static cache will be reset.
   *
   * @return object|bool
   *   The current transaction if available or false if not available
   */
  public static function getCurrentTransaction($cashier_id, $reset = FALSE) {
    $current_transactions = &drupal_static('commerce_pos_current_transactions', array());

    if ($reset) {
      $current_transactions = array();
    }

    if (!isset($current_transactions[$cashier_id])) {
      $current_transactions[$cashier_id] = FALSE;
      $active_transactions = self::getAllActiveTransactions($cashier_id, $reset);

      if (isset($active_transactions['commerce_pos_in_progress'])) {
        $transaction_id = $active_transactions['commerce_pos_in_progress'][0];
        $current_transactions[$cashier_id] = self::loadTransaction($transaction_id);
      }
    }

    return $current_transactions[$cashier_id];
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
  public static function getParkedTransactions($cashier_id) {
    $transactions = self::getAllActiveTransactions($cashier_id);
    return !empty($transactions['commerce_pos_parked']) ? $transactions['commerce_pos_parked'] : array();
  }

  /**
   * Retrieves all POS transactions for a user that are either parked or are currently being created.
   *
   * @param int $cashier_id
   *   The cashier ID to retrieve transactions for.
   * @param bool $reset
   *   If TRUE, the static cache will be reset.
   *
   * @return array
   *   all the current active transaction ids, grouped by type and status
   */
  protected static function getAllActiveTransactions($cashier_id, $reset = FALSE) {
    $transactions = &drupal_static('commerce_pos_all_active_transactions', array());

    if ($reset) {
      $transactions = array();
    }

    if (!isset($transactions[$cashier_id])) {
      $transactions[$cashier_id] = array();

      $query = db_select('commerce_pos_transaction', 't');
      $query->fields('t', array('transaction_id'));
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

      $query->condition('t.cashier', $cashier_id);

      $result = $query->execute();

      foreach ($result as $row) {
        if (empty($row->status)) {
          $row->status = 'commerce_pos_in_progress';
        }

        $transactions[$cashier_id][$row->status][] = $row->transaction_id;
      }
    }

    return $transactions[$cashier_id];
  }

  /**
   * Retrieves a list of commerce_product types that can be added to a POS transaction.
   */
  public static function allowedProductTypes() {
    $types = array();

    foreach (variable_get('commerce_pos_available_products', array('product' => '1')) as $type => $allowed) {
      if ($allowed) {
        $types[] = $type;
      }
    }

    return $types;
  }

  /**
   * Retrieves a list of POS transaction types.
   */
  public static function transactionTypes() {
    return array(
      self::TRANSACTION_TYPE_SALE => t('Sale'),
      self::TRANSACTION_TYPE_RETURN => t('Return'),
      self::TRANSACTION_TYPE_EXCHANGE => t('Exchange'),
    );
  }

}
