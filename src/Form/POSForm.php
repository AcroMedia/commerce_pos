<?php

namespace Drupal\commerce_pos\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\commerce_price\Entity\Currency;

/**
 * Provides the main POS form for using the POS to checkout customers.
 */
class POSForm extends ContentEntityForm {

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity.manager'),
      $container->get('entity_type.bundle.info'),
      $container->get('datetime.time'),
      $container->get('current_user')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'commerce_pos';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    /* @var \Drupal\commerce_order\Entity\Order $order */
    $order = $this->entity;
    $totals = [];

    $form = parent::buildForm($form, $form_state);

    $form['#tree'] = TRUE;
    $form['#theme'] = 'commerce_pos_form';
    $form['#attached']['library'][] = 'commerce_pos/form';

    // Changed must be sent to the client, for later overwrite error checking.
    $form['changed'] = [
      '#type' => 'hidden',
      '#default_value' => $order->getChangedTime(),
    ];

    $form['customer'] = [
      '#type' => 'container',
    ];

    $form['uid']['#group'] = 'customer';
    $form['mail']['#group'] = 'customer';

    // Collecting the Subtotal.
    $form['totals'] = [
      '#type' => 'container',
    ];

    $number_formatter_factory = \Drupal::service('commerce_price.number_formatter_factory');
    $number_formatter = $number_formatter_factory->createInstance();

    $sub_total_price = $order->getSubtotalPrice();
    $currency = Currency::load($sub_total_price->getCurrencyCode());
    $formatted_amount = $number_formatter->formatCurrency($sub_total_price->getNumber(), $currency);

    $totals[] = ['Subtotal', $formatted_amount];

    // Commerce appears to have a bug where if not adjustments exist, it will return a
    // 0 => null array, which will still trigger a foreach loop.
    foreach ($order->collectAdjustments() as $key => $adjustment) {
      if (!empty($adjustment)) {
        $amount = $adjustment->getAmount();
        $currency = Currency::load($amount->getCurrencyCode());
        $formatted_amount = $number_formatter->formatCurrency($amount->getNumber(), $currency);

        $totals[] = [
          $adjustment->getLabel(),
          $formatted_amount,
        ];
      }
    }

    // Collecting the total price on the cart.
    $total_price = $order->getTotalPrice();
    $currency = Currency::load($amount->getCurrencyCode());
    $formatted_amount = $number_formatter->formatCurrency($total_price->getNumber(), $currency);

    $totals[] = ['Total', $formatted_amount];

    $form['totals']['totals'] = [
      '#type' => 'table',
      '#rows' => $totals,
    ];

    $form['list'] = [
      '#type' => 'container',
    ];

    return $form;
  }

}
