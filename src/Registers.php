<?php

namespace Drupal\commerce_pos;

/**
 * Get all available registers.
 */
class Registers {

  /**
   * Get the list of registers.
   *
   * @return array
   *   An array of register entities.
   */
  public function getRegisters() {
    $query = \Drupal::entityQuery('commerce_pos_register');

    $ids = $query->execute();

    $registers = \Drupal::entityTypeManager()->getStorage('commerce_pos_register')->loadMultiple($ids);

    return $registers;
  }

}
