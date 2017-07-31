<?php

namespace Drupal\commerce_pos_currency_denominations\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;

class CurrencyDenominationsForm extends EntityForm {

  /**
   * The currency denominations storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $storage;

  /**
   * Creates a new CurrencyDenominationsForm object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    $this->storage = $entity_type_manager->getStorage('commerce_pos_currency_denominations');
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    /** @var \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager */
    $entity_type_manager = $container->get('entity_type.manager');

    return new static($entity_type_manager);
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);
    /** @var \Drupal\commerce_price\Entity\CurrencyInterface $currency_denominations */
    $currency_denominations = $this->entity;

    return $form;
  }

  /**
   * Validates the currency code.
   */
  public function validateCurrencyCode(array $element, FormStateInterface $form_state, array $form) {
    $currency_code = $element['#value'];
    if (!preg_match('/^[A-Z]{3}$/', $currency_code)) {
      $form_state->setError($element, $this->t('The currency code must consist of three uppercase letters.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $currency_denominations = $this->entity;
    $currency_denominations->save();
    drupal_set_message($this->t('Saved the %label currency denominations.', [
      '%label' => $currency_denominations->label(),
    ]));
    $form_state->setRedirect('entity.commerce_pos_currency_denominations.collection');
  }

}
