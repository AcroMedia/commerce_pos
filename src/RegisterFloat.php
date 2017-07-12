<?php

namespace Drupal\commerce_pos;

use Drupal\commerce\EntityHelper;
use Drupal\commerce_pos\Entity\RegisterInterface;
use Drupal\Core\Database\Connection;

/**
 * Class RegisterFloat.
 */
class RegisterFloat implements RegisterFloatInterface {

  /**
   * The database connection to use.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $connection;

  /**
   * Constructs a new RegisterFloat object.
   *
   * @param \Drupal\Core\Database\Connection $connection
   */
  public function __construct(Connection $connection) {
    $this->connection = $connection;
  }

  /**
   * {@inheritdoc}
   */
  public function addFloat(RegisterInterface $register, $amount) {
    $this->connection->insert('commerce_pos_float')
      ->fields([
        'register_id' => $register->id(),
        'amount' => $amount
      ])
      ->execute();
  }

  /**
   * {@inheritdoc}
   */
  public function deleteFloat(array $registers) {
    $this->connection->delete('commerce_pos_float')
      ->condition('register_id', EntityHelper::extractIds($registers), 'IN')
      ->execute();
  }

  /**
   * {@inheritdoc}
   */
  public function getFloat(RegisterInterface $register, $amount = NULL) {
    $usages = $this->getFloatMultiple([$register], $amount);
    return $usages[$register->id()];
  }

  /**
   * {@inheritdoc}
   */
  public function getFloatMultiple(array $registers, $amount = NULL) {
    if (empty($registers)) {
      return [];
    }
    $register_ids = EntityHelper::extractIds($registers);

    // Setup the query
    $query = $this->connection->select('commerce_pos_float', 'cpf');
    $query->addField('cpf', 'register_id');
    $query->addField('cpf', 'amount');
    $query->condition('register_id', $register_ids, 'IN');
    if(!empty($amount)) {
      $query->condition('amount', $amount);
    }
    $query->groupBy('register_id');

    $result = $query->execute()->fetchAllAssoc('register_id', \PDO::FETCH_ASSOC);

    return $result;
  }

}
