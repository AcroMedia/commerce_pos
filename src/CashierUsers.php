<?php

namespace Drupal\commerce_pos;

use Drupal\Core\Database\Connection;

/**
 * Cashier Users Class.
 */
class CashierUsers {

  /**
   * The database connection to use.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $connection;

  /**
   * Construct a new CashierUsers Object.
   *
   * @param \Drupal\Core\Database\Connection $connection
   *   Pass in the connection via dependency injection, standard for fields.
   */
  public function __construct(Connection $connection) {
    $this->connection = $connection;
  }

  /**
   * Get the list of users having the 'pos_cashier' role.
   *
   * @return array
   *   An array of User Ids having 'pos_cashier' role.
   */
  public function getCashiers() {
    $cashiers = [];
    $cashier_id = $this->connection->select("user__roles", "ur")
      ->fields("ur", ["entity_id"])
      ->condition("ur.roles_target_id", "pos_cashier", "=")
      ->execute();
    foreach ($cashier_id as $id) {
      $cashiers[$id->entity_id] = $id->entity_id;
    }

    return $cashiers;
  }

}
