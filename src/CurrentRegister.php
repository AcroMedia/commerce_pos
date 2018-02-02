<?php

namespace Drupal\commerce_pos;

use Drupal\commerce_pos\Entity\Register;

/**
 * Manage the currently set register.
 */
class CurrentRegister {

  /**
   * Gets the active order_id stored in the session and loads it.
   *
   * @return \Drupal\commerce_pos\Entity\Register|null
   *   An entity object. NULL if no matching entity is found or cookie doesn't exist.
   */
  public function get() {
    if (empty($_COOKIE['commerce_pos_register'])) {
      return NULL;
    }

    $register_id = $_COOKIE['commerce_pos_register'];

    $register = Register::load($register_id);

    if (!$register) {
      // We couldn't load this register, assume it has been removed
      // somehow and clear our current register.
      $this->clear();

      return NULL;
    }

    return $register;
  }

  /**
   * Takes a provided order and sets its ID as the current one.
   *
   * @param \Drupal\commerce_pos\Entity\Register $register
   *   The register you wish to set as the current one.
   */
  public function set(Register $register) {
    // Sets the cookie out 1 year.
    setcookie('commerce_pos_register', $register->id(), time() + 31557600, '/');
  }

  /**
   * Unset the cookie so there is no current register.
   */
  public function clear() {
    unset($_COOKIE['commerce_pos_register']);
  }

}
