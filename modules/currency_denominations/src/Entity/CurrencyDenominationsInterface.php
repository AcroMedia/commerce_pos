<?php

namespace Drupal\commerce_pos_currency_denominations\Entity;

use Drupal\Core\Config\Entity\ConfigEntityInterface;

/**
 * Defines the interface for currency denominations.
 */
interface CurrencyDenominationsInterface extends ConfigEntityInterface {

  /**
   * Gets the currency code.
   *
   * @return string
   *   The currency code.
   */
  public function getCurrencyCode();

  /**
   * Gets the denominations.
   *
   * @return \Drupal\commerce_pos_currency_denominations\CurrencyDenomination[]
   */
  public function getDenominations();

}
