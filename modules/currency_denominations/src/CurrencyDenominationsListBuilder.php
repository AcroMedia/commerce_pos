<?php

namespace Drupal\commerce_pos_currency_denominations;

use Drupal\Core\Config\Entity\ConfigEntityListBuilder;
use Drupal\Core\Entity\EntityInterface;

/**
 * Defines the list builder for currencies.
 */
class CurrencyDenominationsListBuilder extends ConfigEntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header = [
      'currency' => $this->t('Currency'),
    ];
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    $row = [
      'currency' => $entity->label(),
    ];
    return $row + parent::buildRow($entity);
  }

}
