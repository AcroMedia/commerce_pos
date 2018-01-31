<?php

namespace Drupal\commerce_pos\Entity;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\commerce_price\Price;

/**
 * Provides an interface for defining Register entities.
 */
interface RegisterInterface extends ContentEntityInterface {

  /**
   * Gets the store.
   *
   * @return \Drupal\commerce_store\Entity\StoreInterface|null
   *   The store entity, or null.
   */
  public function getStore();

  /**
   * Sets the store.
   *
   * @param \Drupal\commerce_store\Entity\StoreInterface $store
   *   The store entity.
   *
   * @return $this
   */
  public function setStore(StoreInterface $store);

  /**
   * Gets the store ID.
   *
   * @return int
   *   The store ID.
   */
  public function getStoreId();

  /**
   * Sets the store ID.
   *
   * @param int $store_id
   *   The store ID.
   *
   * @return $this
   */
  public function setStoreId($store_id);

  /**
   * Get the float when from when the register was last opened.
   *
   * @return \Drupal\commerce_price\Price
   *   A commerce price object of the current opening float
   */
  public function getOpeningFloat();

  /**
   * Set the opening float, usually when opening the register.
   *
   * @param \Drupal\commerce_price\Price $opening_float
   *   A price object of the opening float, requires number and currency.
   *
   * @return $this
   */
  public function setOpeningFloat(Price $opening_float);

  /**
   * Get the default float to pre-fill the opening float.
   *
   * @return \Drupal\commerce_price\Price
   *   A commerce price object of the default float
   */
  public function getDefaultFloat();

  /**
   * Set the default float that will pre-populate the opening float form.
   *
   * @param \Drupal\commerce_price\Price $default_float
   *   A price object of the default float, requires number and currency.
   *
   * @return $this
   */
  public function setDefaultFloat(Price $default_float);

  /**
   * Set the register to open.
   *
   * @return $this
   */
  public function open();

  /**
   * Set the register to closed.
   *
   * @return $this
   */
  public function close();

  /**
   * Check if the register is open.
   *
   * @return bool
   *   True if the register is set to open, false otherwise.
   */
  public function isOpen();

}
