<?php

namespace Drupal\commerce_pos\Form;

use Drupal\commerce_price\Price;
use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\commerce_price\Entity\Currency;
use Drupal\commerce_order\Entity\Order;

/**
 * Provides the main POS form for using the POS to checkout customers.
 */
class POSForm extends ContentEntityForm {

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
    $form['#tree'] = TRUE;
    $form['#theme'] = 'commerce_pos_form';
    $form['#attached']['library'][] = 'commerce_pos/form';

    $step = $form_state->get('step');
    $step = $step ?: 'order';
    $form_state->set('step', $step);

    if ($step == 'order') {
      $form = $this->buildOrderForm($form, $form_state);
    }
    elseif ($step == 'payment') {
      $form = $this->buildPaymentForm($form, $form_state);
    }

    $this->addTotalsDisplay($form);

    return $form;
  }

  /**
   * Build the POS Order Form.
   */
  protected function buildOrderForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);

    $form['customer'] = [
      '#type' => 'container',
    ];

    $form['uid']['#group'] = 'customer';
    $form['mail']['#group'] = 'customer';

    $form['list'] = [
      '#type' => 'container',
    ];

    $form['actions']['submit']['#value'] = t('Add Payment');
    // Ensure the user is redirected back to this page after deleting an order.
    if (isset($form['actions']['delete']['#url']) && $form['actions']['delete']['#url'] instanceof Url) {
      $form['actions']['delete']['#url']->mergeOptions([
        'query' => [
          'destination' => Url::fromRoute('commerce_pos.main')->toString(),
        ],
      ]);
    }

    return $form;
  }

  /**
   * Build the payment form, this is the second and final step of a POS order.
   */
  public function buildPaymentForm(array $form, FormStateinterface $form_state) {
    // @todo not really the right way to do this. I think the
    // commerce_pos_keypad should override the template to add the library in.
    if ($this->moduleHandler->moduleExists('commerce_pos_keypad')) {
      $form['#attached']['library'][] = 'commerce_pos_keypad/keypad';
    }
    /* @var \Drupal\commerce_order\Entity\Order $order */
    $order = $this->entity;
    $wrapper_id = 'commerce-pos-pay-form-wrapper';
    $form_state->wrapper_id = $wrapper_id;
    $form['#prefix'] = '<div id="' . $wrapper_id . '" class="sale">';
    $form['#suffix'] = '</div>';
    $form['#validate'][] = '::validatePaymentForm';

    $form['payment_gateway'] = [
      '#type' => 'container',
      '#tree' => TRUE,
    ];

    /** @var \Drupal\commerce_payment\PaymentGatewayStorageInterface $payment_gateway_storage */
    $payment_gateway_storage = $this->entityTypeManager->getStorage('commerce_payment_gateway');
    $payment_gateways = $payment_gateway_storage->loadMultipleForOrder($order);
    $order_balance = $this->getOrderBalance();
    $balance_paid = $order_balance->getNumber() == 0;

    $payment_ajax = [
      'wrapper' => 'commerce-pos-sale-keypad-wrapper',
      'callback' => '::keypadAjaxRefresh',
      'effect' => 'fade',
    ];

    foreach ($payment_gateways as $payment_gateway) {
      $form['payment_options'][$payment_gateway->id()] = [
        '#type' => 'button',
        '#value' => $payment_gateway->label(),
        '#name' => 'commerce-pos-payment-option-' . $payment_gateway->id(),
        '#ajax' => $payment_ajax,
        '#payment_option_id' => $payment_gateway->id(),
        '#disabled' => $balance_paid,
        '#limit_validation_errors' => [],
      ];
    }

    $form['keypad'] = [
      '#type' => 'container',
      '#id' => 'commerce-pos-sale-keypad-wrapper',
      '#tree' => TRUE,
    ];

    // If no triggering element is set, grab the default payment method.
    $default_payment_gateway = \Drupal::config('commerce_pos.settings')->get('default_payment_gateway') ?: 'pos_cash';
    $triggering_element = $form_state->getTriggeringElement();
    if (!empty($default_payment_gateway) && !empty($payment_gateways[$default_payment_gateway]) && empty($triggering_element['#payment_option_id'])) {
      $triggering_element['#payment_option_id'] = $default_payment_gateway;
    }

    if (!empty($triggering_element['#payment_option_id']) && !$balance_paid) {
      $option_id = $triggering_element['#payment_option_id'];

      $number_formatter_factory = \Drupal::service('commerce_price.number_formatter_factory');
      $number_formatter = $number_formatter_factory->createInstance();
      $order_balance_amount_format = $number_formatter->formatCurrency($order_balance->getNumber(), Currency::load($order_balance->getCurrencyCode()));
      $keypad_amount = preg_replace('/[^0-9\.,]/', '', $order_balance_amount_format);

      $form['keypad']['amount'] = [
        '#type' => 'textfield',
        '#title' => t('Enter @title Amount', [
          '@title' => $payment_gateways[$option_id]->label(),
        ]),
        '#required' => TRUE,
        '#default_value' => $keypad_amount,
        '#commerce_pos_keypad' => TRUE,
        '#attributes' => [
          'autofocus' => 'autofocus',
          'autocomplete' => 'off',
          'class' => [
            'commerce-pos-payment-keypad-amount',
            'commerce-pos-keypad-keypad'
          ],
        ],
      ];

      $form['#attached']['drupalSettings']['commerce_pos'] = [
        'commercePosPayment' => [
          'focusInput' => TRUE,
          'selector' => '.commerce-pos-payment-keypad-amount',
        ],
      ];

      $form['keypad']['add'] = [
        '#type' => 'submit',
        '#value' => t('Add'),
        '#name' => 'commerce-pos-pay-keypad-add',
        '#submit' => ['::submitForm'],
        '#payment_gateway_id' => $option_id,
        '#element_key' => 'add-payment',
      ];
    }

    $form['actions']['back'] = [
      '#type' => 'submit',
      '#value' => t('Back To Order'),
      '#name' => 'commerce-pos-back-to-order',
      '#submit' => ['::submitForm'],
      '#element_key' => 'back-to-order',
    ];

    $form['actions']['finish'] = [
      '#type' => 'submit',
      '#value' => t('Finish'),
      '#disabled' => !$balance_paid,
      '#name' => 'commerce-pos-finish',
      '#submit' => ['::submitForm'],
      '#element_key' => 'finish-order',
    ];

    return $form;
  }

  /**
   * AJAX callback for the Pay form keypad.
   */
  public function keypadAjaxRefresh($form, &$form_state) {
    return $form['keypad'];
  }

  /**
   * AJAX callback for the payment form.
   */
  public function ajaxRefresh($form, &$form_state) {
    return $form;
  }

  /**
   * Validate the values in the payment form.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public function validatePaymentForm(array $form, FormStateInterface $form_state) {
    $triggering_element = $form_state->getTriggeringElement();
    if ($triggering_element['#name'] == 'commerce-pos-pay-keypad-add') {
      $keypad_amount = $form_state->getValue('keypad')['amount'];

      $order_balance = $this->getOrderBalance()->getNumber();

      if (!is_numeric($keypad_amount)) {
        $form_state->setError($form['keypad']['amount'], t('Payment amount must be a number.'));
      }
      // TODO: remove this when we support change.
      elseif ($keypad_amount > $order_balance) {
        $form_state->setError($form['keypad']['amount'], t('Paid amount must not exceed remaining balance'));
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $triggering_element = $form_state->getTriggeringElement();
    $step = $form_state->get('step');
    if ($step == 'order') {
      parent::submitForm($form, $form_state);
      $this->entity->save();
      $form_state->set('step', 'payment');
      $form_state->setRebuild(TRUE);
    }

    if ($step == 'payment') {
      if ($triggering_element['#element_key'] == 'add-payment') {
        $this->submitPayment($form, $form_state);
      }
      elseif ($triggering_element['#element_key'] == 'back-to-order') {
        $form_state->set('step', 'order');
        $form_state->setRebuild(TRUE);
      }
      elseif ($triggering_element['#element_key'] == 'finish-order') {
        $this->finishOrder($form, $form_state);
      }
    }

  }

  /**
   * Add a payment to the pos order.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  protected function submitPayment(array &$form, FormStateInterface $form_state) {
    $triggering_element = $form_state->getTriggeringElement();
    $store = $this->entity->getStore();
    $default_currency = $store->getDefaultCurrency();
    $payment_gateway = $triggering_element['#payment_gateway_id'];
    $values = [
      'payment_gateway' => $payment_gateway,
      'order_id' => $this->entity->id(),
      'state' => 'pending',
      'amount' => [
        'number' => $form_state->getValue('keypad')['amount'],
        'currency_code' => $default_currency->getCurrencyCode(),
      ],
    ];

    $payment_storage = $this->entityTypeManager->getStorage('commerce_payment');
    $payment = $payment_storage->create($values);
    $payment->save();
    $form_state->setRebuild(TRUE);
  }

  /**
   * Finish the current order.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  protected function finishOrder(array &$form, FormStateInterface $form_state) {
    $this->completePayments();

    $order = $this->entity;
    $transition = $order->getState()->getWorkflow()->getTransition('place');
    $order->getState()->applyTransition($transition);
    $order->save();

    $this->clearOrder();
  }

  /**
   * Build the totals display for the sidebar.
   */
  protected function addTotalsDisplay(array &$form) {
    /* @var \Drupal\commerce_order\Entity\Order $order */
    $order = $this->entity;
    $store = $order->getStore();
    $default_currency = $store->getDefaultCurrency();
    $totals = [];
    // Collecting the Subtotal.
    $form['totals'] = [
      '#type' => 'container',
    ];

    $number_formatter_factory = \Drupal::service('commerce_price.number_formatter_factory');
    $number_formatter = $number_formatter_factory->createInstance();

    $sub_total_price = $order->getSubtotalPrice();
    if (!empty($sub_total_price)) {
      $currency = Currency::load($sub_total_price->getCurrencyCode());
      $formatted_amount = $number_formatter->formatCurrency($sub_total_price->getNumber(), $currency);
    }
    else {
      $formatted_amount = $number_formatter->formatCurrency(0, $default_currency);
    }

    $totals[] = [$this->t('Subtotal'), $formatted_amount];

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
    if (!empty($total_price)) {
      $currency = Currency::load($total_price->getCurrencyCode());
      $formatted_amount = $number_formatter->formatCurrency($total_price->getNumber(), $currency);
    }
    else {
      $formatted_amount = $number_formatter->formatCurrency(0, $default_currency);
    }

    $totals[] = [$this->t('Total'), $formatted_amount];

    $form['totals']['totals'] = [
      '#type' => 'table',
      '#rows' => $totals,
    ];

    // Collect payments.
    $payments = [];
    $payment_totals = [];
    foreach ($this->getOrderPayments() as $payment) {
      $amount = $payment->getAmount();
      $voided = $payment->getState()->value == 'voided' ? $this->t(' (Void)') : '';
      $payments[] = [
        $payment->getPaymentGateway()->label() . $voided,
        $number_formatter->formatCurrency($amount->getNumber(), Currency::load($amount->getCurrencyCode())),
      ];
      if (!isset($payment_totals[$amount->getCurrencyCode()])) {
        // Initialise the payment total.
        $payment_totals[$amount->getCurrencyCode()] = 0;
      }
      $payment_totals[$amount->getCurrencyCode()] += $amount->getNumber();
    }
    $form['totals']['payments'] = [
      '#type' => 'table',
      '#rows' => $payments,
    ];

    // Collect the balances.
    $balances = [];
    foreach ($payment_totals as $currency_code => $amount) {
      $balances[] = [$this->t('Total Paid'), $number_formatter->formatCurrency($amount, Currency::load($currency_code))];
    }
    $remaining_balance = $this->getOrderBalance();
    if ($remaining_balance->getNumber() > 0) {
      $currency = Currency::load($remaining_balance->getCurrencyCode());
      $formatted_amount = $number_formatter->formatCurrency($remaining_balance->getNumber(), $currency);
      $balances[] = ['class' => 'commerce-pos--totals--to-pay', 'data' => [$this->t('To Pay'), $formatted_amount]];
    }

    $form['totals']['balance'] = [
      '#type' => 'table',
      '#rows' => $balances,
    ];
  }

  /**
   * Get the current balance of the order.
   *
   * Once https://www.drupal.org/node/2804227 is in commerce we should be able
   * to do this directly from the order.
   *
   * @return \Drupal\commerce_price\Price
   *   The total remaining balance amount.
   */
  protected function getOrderBalance() {
    $payments = $this->getOrderPayments();
    $total_price = $this->entity->getTotalPrice();
    $total_price_amount = !empty($total_price) ? $total_price->getNumber() : 0;
    $currency_code = !empty($total_price) ? $total_price->getCurrencyCode() : $this->entity->getStore()->getDefaultCurrency()->getCurrencyCode();
    $balance_paid_amount = 0;

    foreach ($payments as $payment) {
      if (!in_array($payment->getState()->value, ['voided', 'refunded'])) {
        $balance_paid_amount += $payment->getBalance()->getNumber();
      }
    }

    $balance_remaining = (string) ($total_price_amount - $balance_paid_amount);

    return new Price($balance_remaining, $currency_code);
  }

  /**
   * Get an array of payment entities for the current order.
   *
   * @return array
   *   The Payment entities attached to this order.
   */
  protected function getOrderPayments() {
    $payment_storage = $this->entityTypeManager->getStorage('commerce_payment');
    return $payment_storage->loadMultipleByOrder($this->entity);
  }

  /**
   * Set the order's payments to completed.
   */
  protected function completePayments() {
    foreach ($this->getOrderPayments() as $payment) {
      if ($payment->getState()->value == 'pending') {
        $payment->setState('completed');
        $payment->save();
      }
    }

  }

  /**
   * Replace the existing order with a new one.
   */
  protected function clearOrder() {
    $order = Order::create([
      'type' => 'pos',
      'field_cashier' => \Drupal::currentUser()->id(),
    ]);

    $order->setStoreId($this->entity->getStoreId());
    $order->save();

    \Drupal::service('commerce_pos.current_order')->set($order);

  }

}
