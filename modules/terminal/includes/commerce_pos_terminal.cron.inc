<?php

/**
 * @file
 * Functions used in cron runs.
 */

/**
 * Remove old records from the pending requests table.
 *
 * @param int $time
 *   Allows a calling function to override the amount of time to wait before
 *   cleaning up transactions.
 *
 * @return int
 */
function commerce_pos_terminal_request_cleanup($time = NULL) {
  $records_deleted = 0;

  if ($time !== NULL && is_numeric($time)) {
    $cleanup_wait = $time;
  }
  else {
    $cleanup_wait = variable_get('commerce_pos_terminal_transaction_cleanup_wait', COMMERCE_POS_TERMINAL_CLEANUP_WAIT_DEFAULT);
  }

  if ($cleanup_wait !== COMMERCE_POS_TERMINAL_CLEANUP_WAIT_NEVER) {
    $query = db_select('commerce_pos_terminal_pending_requests', 'pr')
      ->fields('pr')
      ->condition('pr.created', REQUEST_TIME - $cleanup_wait, '<=');

    foreach ($query->execute() as $row) {
      if (commerce_pos_terminal_request_cleanup_request($row)) {
        $delete[] = $row->id;
      }
    }

    if (!empty($delete)) {
      $records_deleted = db_delete('commerce_pos_terminal_pending_requests')
        ->condition('id', $delete, 'IN')
        ->execute();
    }
  }

  return $records_deleted;
}

/**
 * Perform cleanup on a request.
 * - Modify the transaction referred to by the record if necessary.
 * - Decide if the record should be deleted.
 *
 * @param $record
 *
 * @return bool
 *   TRUE if the record should be removed from the database.
 */
function commerce_pos_terminal_request_cleanup_request($record) {
  $since_created = REQUEST_TIME - $record->created;

  // Set a pending transaction's status to timed out. Otherwise, assume the
  // transaction has already been dealt with.
  if ($record->status == COMMERCE_PAYMENT_STATUS_PENDING) {
    $transaction = commerce_payment_transaction_load($record->transaction_id);
    $transaction->status = COMMERCE_PAYMENT_STATUS_TERMINAL_TIMED_OUT;
    $transaction->message = t('Transaction timed out after no response for @seconds seconds.', array('@seconds' => $since_created));
    commerce_payment_transaction_save($transaction);
  }

  return TRUE;
}
