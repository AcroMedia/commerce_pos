<?php

namespace Drupal\commerce_pos;

use Drupal\commerce_pos\Entity\RegisterInterface;

/**
 * Tracks the Float for each Register.
 *
 * The amount of money that is in the till at the beginning of each day.
 */
interface RegisterFloatInterface {

  /**
   * Add a float to the register.
   *
   * @param \Drupal\commerce_pos\Entity\RegisterInterface $register
   *   The register.
   * @param float $amount
   *   The amount in the register.
   */
  public function addFloat(RegisterInterface $register, $amount);

  /**
   * Deletes all floats for the given registers.
   *
   * @param array $registers
   *   The registers.
   */
  public function deleteFloat(array $registers);

  /**
   * Gets the float for the given register.
   *
   * @param \Drupal\commerce_pos\Entity\RegisterInterface $register
   *   The Register.
   * @param float|null $amount
   *   (optional) The amount.
   *
   * @return int
   *   The float.
   */
  public function getFloat(RegisterInterface $register, $amount = NULL);

  /**
   * Gets the floats for the given registers.
   *
   * @param array $registers
   *   The registers.
   * @param float|null $amount
   *   (optional)The amount.
   *
   * @return array
   *   The floats, keyed by register ID.
   */
  public function getFloatMultiple(array $registers, $amount = NULL);

}
