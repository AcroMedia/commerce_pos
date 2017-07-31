<?php

namespace Drupal\commerce_pos_currency_denominations;

/**
 * Represents a currency denomination.
 */
final class CurrencyDenomination {

  /**
   * The denomination ID.
   *
   * @var string
   */
  protected $id;

  /**
   * The denomination label.
   *
   * @var string
   */
  protected $label;

  /**
   * The denomination amount.
   *
   * @var int
   */
  protected $amount;

  /**
   * Constructs a new CurrencyDenomination object.
   *
   * @param array $definition
   *   The definition.
   */
  public function __construct(array $definition) {
    foreach (['id', 'label', 'amount'] as $required_property) {
      if (empty($definition[$required_property])) {
        throw new \InvalidArgumentException(sprintf('Missing required property %s.', $required_property));
      }
    }

    $this->id = $definition['id'];
    $this->label = $definition['label'];
    $this->amount = $definition['amount'];
  }

  /**
   * Gets the denomination ID.
   *
   * @return string
   *   The denomination ID.
   */
  public function getId() {
    return $this->id;
  }

  /**
   * Gets the denomination label.
   *
   * @return string
   *   The denomination label.
   */
  public function getLabel() {
    return $this->label;
  }

  /**
   * Gets the denomination amount.
   *
   * @return int
   *   The denomination amount.
   */
  public function getAmount() {
    return $this->amount;
  }

}
