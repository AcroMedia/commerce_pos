<?php

namespace Drupal\commerce_pos\Form;

use Drupal\commerce_price\Price;
use Drupal\commerce_store\CurrentStore;
use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\commerce_price\Entity\Currency;
use Drupal\commerce_order\Entity\Order;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides the main POS form for using the POS to checkout customers.
 */
class POSForm extends ContentEntityForm {

  /**
   * The current store object.
   *
   * @var \Drupal\commerce_store\CurrentStore
   */
  protected $currentStore;

  /**
   * Constructs a new POSForm object.
   *
   * @param \Drupal\Core\Entity\EntityManagerInterface $entity_manager
   *   The entity manager.
   * @param \Drupal\Core\Entity\EntityTypeBundleInfoInterface $entity_type_bundle_info
   *   The entity type bundle info.
   * @param \Drupal\Component\Datetime\TimeInterface $time
   *   The time.
   * @param \Drupal\commerce_store\CurrentStore $current_store
   *   The current store object.
   */
  public function __construct(EntityManagerInterface $entity_manager, EntityTypeBundleInfoInterface $entity_type_bundle_info, TimeInterface $time, CurrentStore $current_store) {
    parent::__construct($entity_manager, $entity_type_bundle_info, $time);

    $this->currentStore = $current_store;
    $this->logStorage = $entity_manager->getStorage('commerce_log');
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity.manager'),
      $container->get('entity_type.bundle.info'),
      $container->get('datetime.time'),
      $container->get('commerce_store.current_store')
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
    $form['#tree'] = TRUE;
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

    // Add order note form.
    $form += $this->buildNoteForm($form, $form_state);

    $this->addTotalsDisplay($form, $form_state);

    return $form;
  }

  /**
   * Build the POS Order Form.
   */
  protected function buildOrderForm(array $form, FormStateInterface $form_state) {
    /* @var \Drupal\commerce_order\Entity\Order $order */
    $order = $this->entity;
    $form_state->set('commerce_pos_order_id', $order->id());

    $form = parent::buildForm($form, $form_state);
    $form['#theme'] = 'commerce_pos_form_order';

    $form['customer'] = [
      '#type' => 'container',
    ];

    $form['uid']['#group'] = 'customer';
    $form['mail']['#group'] = 'customer';

    $form['list'] = [
      '#type' => 'container',
    ];

    $form['actions']['submit']['#value'] = t('Payments and Completion');
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
    /* @var \Drupal\commerce_order\Entity\Order $order */
    $order = $this->entity;
    $wrapper_id = 'commerce-pos-pay-form-wrapper';
    $form_state->wrapper_id = $wrapper_id;

    $form['#theme'] = 'commerce_pos_form_payment';
    $form['#prefix'] = '<div id="' . $wrapper_id . '" class="sale">';
    $form['#suffix'] = '</div>';
    $form['#validate'][] = '::validatePaymentForm';

    // Is this too clunky?
    $parent_form = parent::buildForm($form, $form_state);
    $form['mail'] = $parent_form['mail'];

    // Change the contact email field into an ajax field so that any changes
    // to the email automatically get saved to the order.
    $form['mail']['#prefix'] = '<div id="order-mail-wrapper">';
    $form['mail']['#suffix'] = '</div>';
    $form['mail']['widget'][0]['value']['#element_key'] = 'order-mail';
    $form['mail']['widget'][0]['value']['#limit_validation_errors'] = [
      ['mail'],
    ];
    $form['mail']['widget'][0]['value']['#ajax'] = [
      'wrapper' => 'order-mail-wrapper',
      'callback' => '::emailAjaxRefresh',
      'event' => 'change',
    ];

    // Save the email if it has been changed.
    $triggering_element = $form_state->getTriggeringElement();
    if (isset($triggering_element['#element_key']) && $triggering_element['#element_key'] == 'order-mail') {
      $order->setEmail($form_state->getValue('mail'));
      $order->save();
    }

    $form['order_id'] = [
      '#type' => 'value',
      '#value' => $order->id(),
    ];

    $form['payment_gateway'] = [
      '#type' => 'container',
      '#tree' => TRUE,
    ];

    /** @var \Drupal\commerce_payment\PaymentGatewayStorageInterface $payment_gateway_storage */
    $payment_gateway_storage = $this->entityTypeManager->getStorage('commerce_payment_gateway');
    $payment_gateways = $payment_gateway_storage->loadMultipleForOrder($order);
    $order_balance = $this->getOrderBalance();
    $balance_paid = $order_balance->getNumber() <= 0;

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
    $default_payment_gateway = \Drupal::config('commerce_pos.settings')
      ->get('default_payment_gateway') ?: 'pos_cash';
    if (!empty($default_payment_gateway) && !empty($payment_gateways[$default_payment_gateway]) && empty($triggering_element['#payment_option_id'])) {
      $triggering_element['#payment_option_id'] = $default_payment_gateway;
    }

    if (!empty($triggering_element['#payment_option_id']) && !$balance_paid) {
      $option_id = $triggering_element['#payment_option_id'];

      $number_formatter_factory = \Drupal::service('commerce_price.number_formatter_factory');
      $number_formatter = $number_formatter_factory->createInstance();
      $order_balance_amount_format = $number_formatter->formatCurrency($order_balance->getNumber(), Currency::load($order_balance->getCurrencyCode()));
      $keypad_amount = preg_replace('/[^0-9\.,]/', '', $order_balance_amount_format);
      // Fetching fraction digit to set as step.
      $fraction_digits = $this->currentStore->getStore()
        ->getDefaultCurrency()
        ->getFractionDigits();
      $form['keypad']['amount'] = [
        '#type' => 'number',
        '#title' => t('Enter @title Amount', [
          '@title' => $payment_gateways[$option_id]->label(),
        ]),
        '#step' => pow(0.1, $fraction_digits),
        '#required' => TRUE,
        '#default_value' => $keypad_amount,
        '#commerce_pos_keypad' => TRUE,
        '#attributes' => [
          'autofocus' => 'autofocus',
          'autocomplete' => 'off',
          'class' => [
            'commerce-pos-payment-keypad-amount',
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
        '#value' => t('Add Payment'),
        '#name' => 'commerce-pos-pay-keypad-add',
        '#submit' => ['::submitForm'],
        '#payment_gateway_id' => $option_id,
        '#element_key' => 'add-payment',
      ];
    }

    $form['actions'] = [
      '#type' => 'actions',
    ];

    $form['actions']['finish'] = [
      '#type' => 'submit',
      '#value' => t('Complete Order'),
      '#disabled' => !$balance_paid,
      '#name' => 'commerce-pos-finish',
      '#submit' => ['::submitForm'],
      '#element_key' => 'finish-order',
      '#button_type' => 'primary',
    ];

    $form['actions']['back'] = [
      '#type' => 'submit',
      '#value' => t('Back To Order'),
      '#name' => 'commerce-pos-back-to-order',
      '#submit' => ['::submitForm'],
      '#element_key' => 'back-to-order',
    ];

    return $form;
  }

  /**
   * Build the elements for the order note form.
   */
  protected function buildNoteForm(array $form, FormStateInterface $form_state) {
    $form['add_note'] = [
      '#type' => 'container',
      '#prefix' => '<div id="commerce-pos-add-note-wrapper">',
      '#suffix' => '</div>',
      '#weight' => 100,
    ];

    $triggering_element = $form_state->getTriggeringElement();
    // Add note submit was clicked.
    if ($triggering_element['#element_key'] == 'add-note-submit') {
      $this->saveOrderComment($this->entity, $form_state->getValue('add_note')['note_text']);
    }

    // 'Add Note' was clicked.
    if (!empty($triggering_element) && $triggering_element['#element_key'] == 'add-note') {
      $form['add_note']['note_text'] = [
        '#type' => 'textarea',
        '#title' => t('Add Note'),
        '#required' => TRUE,
      ];

      $form['add_note']['submit'] = [
        '#type' => 'button',
        '#value' => t('Submit'),
        '#ajax' => [
          'wrapper' => 'commerce-pos-add-note-wrapper',
          'callback' => '::addNoteAjaxRefresh',
          'effect' => 'fade',
        ],
        '#limit_validation_errors' => [['add_note']],
        '#element_key' => 'add-note-submit',
      ];

      $form['add_note']['cancel'] = [
        '#type' => 'button',
        '#value' => t('Cancel'),
        '#ajax' => [
          'wrapper' => 'commerce-pos-add-note-wrapper',
          'callback' => '::addNoteAjaxRefresh',
          'effect' => 'fade',
        ],
        '#limit_validation_errors' => [],
        '#element_key' => 'add-note-cancel',
      ];
    }
    else {
      $form['add_note']['note'] = [
        '#type' => 'button',
        '#value' => t('Add Note'),
        '#name' => 'add-note',
        '#element_key' => 'add-note',
        '#ajax' => [
          'wrapper' => 'commerce-pos-add-note-wrapper',
          'callback' => '::addNoteAjaxRefresh',
          'effect' => 'fade',
        ],
        '#limit_validation_errors' => [],
      ];
    }

    return $form;
  }

  /**
   * Adds a commerce log to an order.
   *
   * @param object $order
   *   The order entity.
   * @param string $comment
   *   The order comment.
   */
  public function saveOrderComment($order, $comment) {
    $this->logStorage->generate($order, 'order_comment', [
      'comment' => $comment,
    ])->save();
    drupal_set_message($this->t('Successfully saved order comment.'));
  }

  /**
   * AJAX callback for the add note form.
   */
  public function addNoteAjaxRefresh($form, &$form_state) {
    return $form['add_note'];
  }

  /**
   * AJAX callback for the Pay form keypad.
   */
  public function keypadAjaxRefresh($form, &$form_state) {
    return $form['keypad'];
  }

  /**
   * AJAX callback for the Pay form keypad.
   */
  public function emailAjaxRefresh($form, &$form_state) {
    return $form['mail'];
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

      if (!is_numeric($keypad_amount)) {
        $form_state->setError($form['keypad']['amount'], t('Payment amount must be a number.'));
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
      if ($triggering_element['#element_key'] !== 'remove-payment') {
        $form_state->set('step', 'payment');
      }
      $form_state->setRebuild(TRUE);
    }

    if ($step == 'payment') {
      if ($triggering_element['#element_key'] == 'add-payment') {
        $this->submitPayment($form, $form_state);
        // Save the payment, in case we leave and go to another screen. Missing a payment would be bad
        // also helps if we're loading it somewhere else, like for the receipt trickyness.
        $this->entity->save();
      }
      elseif ($triggering_element['#element_key'] == 'back-to-order') {
        $form_state->set('step', 'order');
        $form_state->setRebuild(TRUE);
      }
      elseif ($triggering_element['#element_key'] == 'finish-order') {
        $this->finishOrder($form, $form_state);
      }
    }

    if ($triggering_element['#element_key'] == 'remove-payment') {
      $this->voidPayment($form, $form_state);
      // Save the payment, in case we leave and go to another screen. Missing a payment would be bad
      // also helps if we're loading it somewhere else, like for the receipt trickyness.
      $this->entity->save();
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

    // Right now all the payment methods are manual, we'll have to change this up
    // once we want to support integrated payment methods.
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
   * Void a payment to the pos order.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  protected function voidPayment(array &$form, FormStateInterface $form_state) {
    $triggering_element = $form_state->getTriggeringElement();

    $order = $this->entity;
    /** @var \Drupal\commerce_payment\PaymentStorageInterface $payment_storage */
    $payment_storage = $this->entityTypeManager->getStorage('commerce_payment');
    $payments = $payment_storage->loadMultipleByOrder($order);

    // Get the payment id from the triggering element.
    $payment_id = $triggering_element['#payment_id'];
    $payment_gateway_id = $triggering_element['#payment_gateway_id'];
    /** @var \Drupal\commerce_payment\Entity\Payment $payment */
    $payment = $payments[$payment_id];
    /** @var \Drupal\commerce_payment\Plugin\Commerce\PaymentGateway\Manual $payment_gateway */
    $plugin_manager = \Drupal::service('plugin.manager.commerce_payment_gateway');
    // Right now all the payment methods are manual, we'll have to change this up
    // once we want to support integrated payment methods.
    $payment_gateway = $plugin_manager->createInstance('manual');
    $payment_gateway->voidPayment($payment);
    drupal_set_message($this->t('Payment Voided'));
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
  protected function addTotalsDisplay(array &$form, FormStateInterface $form_state) {
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

    // Commerce appears to have a bug where if not adjustments exist, it
    // will return a 0 => null array, which will still trigger a foreach loop.
    // a foreach loop.
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
    $payment_totals = [];
    $form['totals']['payments'] = [
      '#type' => 'table',
    ];
    foreach ($this->getOrderPayments() as $payment) {
      $amount = $payment->getAmount();
      $rendered_amount = $payment->getState()->value === 'voided' ? $this->t('VOID') : $number_formatter->formatCurrency($amount->getNumber(), Currency::load($amount->getCurrencyCode()));
      $remove_button = [];
      if ($payment->getState()->value !== 'voided') {
        // TODO change to a link.
        $remove_button = [
          '#type' => 'submit',
          '#value' => t('void'),
          '#name' => 'commerce-pos-pay-keypad-remove',
          '#submit' => ['::submitForm'],
          '#payment_id' => $payment->id(),
          '#payment_gateway_id' => $payment->get('payment_gateway')->target_id,
          '#element_key' => 'remove-payment',
          '#attributes' => [
            'class' => [
              'commerce-pos-pay-keypad-remove',
            ],
          ],
        ];
        if (!isset($payment_totals[$amount->getCurrencyCode()])) {
          // Initialise the payment total.
          $payment_totals[$amount->getCurrencyCode()] = 0;
        }
        $payment_totals[$amount->getCurrencyCode()] += $amount->getNumber();
      }

      $form['totals']['payments'][$payment->id()] = [
        'gateway' => ['#plain_text' => $payment->getPaymentGateway()->label()],
        'amount' => [
          'amount' => ['#plain_text' => $rendered_amount],
          'void_link' => $remove_button,
        ],
      ];
    }

    // Collect the balances.
    $balances = [];
    foreach ($payment_totals as $currency_code => $amount) {
      $balances[] = [
        $this->t('Total Paid'),
        $number_formatter->formatCurrency($amount, Currency::load($currency_code)),
      ];
    }
    $remaining_balance = $this->getOrderBalance();

    $currency = Currency::load($remaining_balance->getCurrencyCode());
    $to_pay = $remaining_balance->getNumber();
    if ($to_pay < 0) {
      $to_pay = 0;
    }
    $formatted_amount = $number_formatter->formatCurrency($to_pay, $currency);
    $balances[] = [
      'class' => 'commerce-pos--totals--to-pay',
      'data' => [$this->t('To Pay'), $formatted_amount],
    ];

    $change = -$remaining_balance->getNumber();
    if ($change < 0) {
      $change = 0;
    }
    $formatted_change_amount = $number_formatter->formatCurrency($change, $currency);
    $balances[] = [
      'class' => 'commerce-pos--totals--to-pay',
      'data' => [$this->t('Change'), $formatted_change_amount],
    ];

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
    $currency_code = !empty($total_price) ? $total_price->getCurrencyCode() : $this->entity->getStore()
      ->getDefaultCurrency()
      ->getCurrencyCode();
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
