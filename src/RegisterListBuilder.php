<?php

namespace Drupal\commerce_pos;

use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Entity\EntityInterface;

/**
 * Provides a listing of Register entities.
 */
class RegisterListBuilder extends EntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['label'] = $this->t('Register');
    $header['store'] = $this->t('Store');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    $row['label'] = $entity->label();
    $row['store'] = $entity->getStore()->getName();
    // You probably want a few more properties here...
    return $row + parent::buildRow($entity);
  }

}
