<?php

namespace Drupal\commerce_pos_currency_denominations\Entity;

use Drupal\commerce_pos_currency_denominations\CurrencyDenomination;
use Drupal\Core\Config\Entity\ConfigEntityBase;

/**
 * Defines the currency denominations entity class.
 *
 * @ConfigEntityType(
 *   id = "commerce_pos_currency_denominations",
 *   label = @Translation("Currency denominations"),
 *   label_collection = @Translation("Currency denominations"),
 *   handlers = {
 *     "form" = {
 *       "add" = "Drupal\commerce_pos_currency_denominations\Form\CurrencyDenominationsForm",
 *       "edit" = "Drupal\commerce_pos_currency_denominations\Form\CurrencyDenominationsForm",
 *       "delete" = "Drupal\Core\Entity\EntityDeleteForm"
 *     },
 *     "route_provider" = {
 *       "default" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *     "list_builder" = "Drupal\commerce_pos_currency_denominations\CurrencyDenominationsListBuilder",
 *   },
 *   admin_permission = "administer commerce_pos_currency_denominations",
 *   config_prefix = "commerce_pos_currency_denominations",
 *   entity_keys = {
 *     "id" = "currencyCode",
 *     "label" = "currencyCode",
 *     "uuid" = "uuid"
 *   },
 *   config_export = {
 *     "currencyCode",
 *     "denominations",
 *   },
 *   links = {
 *     "add-form" = "/admin/commerce/config/currency_denominations/add",
 *     "edit-form" = "/admin/commerce/config/currency_denominations/{commerce_pos_currency_denominations}",
 *     "delete-form" = "/admin/commerce/config/currency_denominations/{commerce_pos_currency_denominations}/delete",
 *     "collection" = "/admin/commerce/config/currency_denominations"
 *   }
 * )
 */
class CurrencyDenominations extends ConfigEntityBase implements CurrencyDenominationsInterface {

  /**
   * The currency code.
   *
   * @var string
   */
  protected $currencyCode;

  /**
   * The denominations.
   *
   * @var array
   */
  protected $denominations;

  /**
   * {@inheritdoc}
   */
  public function id() {
    return $this->currencyCode;
  }

  /**
   * {@inheritdoc}
   */
  public function getCurrencyCode() {
    return $this->currencyCode;
  }

  /**
   * {@inheritdoc}
   */
  public function getDenominations() {
    $denominations = [];
    foreach ($this->denominations as $denomination) {
      $denominations[] = new CurrencyDenomination($denomination);
    }
    return $denominations;
  }

}
