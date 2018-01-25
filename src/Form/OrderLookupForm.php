<?php

namespace Drupal\commerce_pos\Form;

use Drupal\commerce_order\Entity\Order;
use Drupal\commerce_price\Entity\Currency;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\HtmlCommand;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Drupal\Core\Url;

/**
 * Provides an order lookup form to search orders.
 */
class OrderLookupForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'commerce_pos_order_lookup';
  }

  /**
   * Build the order lookup form.
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    // The order search elements.
    $form['order_lookup'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Order Lookup'),
    ];

    // The search box to look up by order number, customer name or email.
    $form['order_lookup']['search_box'] = [
      '#type' => 'textfield',
      '#maxlength' => 50,
      '#size' => 25,
      '#description' => $this->t('Search by order number, customer name or customer email.'),
      '#ajax' => [
        'callback' => '::orderLookupAjaxRefresh',
        'event' => 'input',
        'progress' => [
          'type' => 'throbber',
          'message' => t('Searching orders...'),
        ],
      ],
    ];

    // Display the results of the lookup below.
    $form['order_lookup']['results'] = [
      '#type' => 'container',
      '#prefix' => '<div id="order-lookup-results">',
      '#suffix' => '</div>',
    ];

    $triggering_element = $form_state->getTriggeringElement();
    if (empty($triggering_element)) {
      $lookup_results = $this->recentPosOrders();

      $form['order_lookup']['results']['result'] = [
        '#type' => 'item',
        '#markup' => $lookup_results,
        '#prefix' => '<div id="order-lookup-results">',
        '#suffix' => '</div>',
      ];
    }

    return $form;
  }

  /**
   * Submit callback for the order lookup form.
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // No submit actually needed as this form is ajax refresh only.
  }

  /**
   * Ajax callback for the order lookup submit button.
   */
  public function orderLookupAjaxRefresh(array $form, FormStateInterface &$form_state) {
    $search_text = $form_state->getValue('search_box');

    $results = $this->searchOrderResults($search_text);

    $response = new AjaxResponse();
    $response->addCommand(new HtmlCommand('#order-lookup-results', $results));

    return $response;
  }

  /**
   * Looks up an order based on a search criteria and returns the results.
   *
   * @param string $search_text
   *   The search criteria. Could be an order ID, customer name, or email.
   *
   * @return mixed
   *   Returns the markup for a themed table with the order results.
   */
  public function searchOrderResults($search_text) {
    if ($search_text == '') {
      return $this->recentPosOrders();
    }

    $result_limit = \Drupal::config('commerce_pos.settings')->get('order_lookup_limit');

    // Create the query now.
    $query = \Drupal::entityQuery('commerce_order');
    $query = $query->condition('state', 'draft', '!=');
    $query = $query->condition('type', 'pos');
    $query = $query->sort('order_id', 'DESC')
      ->range(0, !empty($result_limit) ? $result_limit : 10);

    if (is_numeric($search_text)) {
      $query->condition('order_id', $search_text);
    }
    else {
      $conditions = $query->orConditionGroup();
      // First check if we can find a user by this name.
      $user = user_load_by_name($search_text);
      if ($user) {
        $conditions = $conditions->condition('uid', $user->id());
      }
      $conditions = $conditions->condition('mail', $search_text);

      $query->condition($conditions);
    }

    $result = $query->execute();
    if (!empty($result)) {
      $orders = Order::loadMultiple($result);
    }

    // If we've got an order, let's output the details in a table.
    if ($orders) {
      $order_markup = $this->buildOrderTable($orders);
    }
    else {
      $order_markup = $this->t('The order could not be found or does not exist.');
    }

    return $order_markup;
  }

  /**
   * Fetches and returns a themed table with the most recent POS orders.
   *
   * @return string
   *   The html markup for the themed table.
   */
  public function recentPosOrders() {
    $result_limit = \Drupal::config('commerce_pos.settings')->get('order_lookup_limit');

    // Let's query the db for the most recent orders.
    $query = \Drupal::entityQuery('commerce_order')
      ->condition('type', 'pos')
      ->condition('state', 'draft', '!=')
      ->range(0, !empty($result_limit) ? $result_limit : 10)
      ->sort('order_id', 'DESC');
    $result = $query->execute();

    if (!empty($result)) {
      $orders = Order::loadMultiple($result);
    }

    // If we've got an order, let's output the details in a table.
    if (isset($orders)) {
      $order_markup = $this->buildOrderTable($orders);
    }
    else {
      $order_markup = $this->t('There are currently no POS orders.');
    }

    return $order_markup;
  }

  /**
   * Return a themed table with the order details.
   *
   * @param array $orders
   *   An array of order entities.
   *
   * @return string
   *   The markup for the themed table.
   */
  public function buildOrderTable(array $orders) {
    $number_formatter_factory = \Drupal::service('commerce_price.number_formatter_factory');
    $number_formatter = $number_formatter_factory->createInstance();

    $header = [
      t('Order ID'),
      t('Status'),
      t('Customer'),
      t('Total'),
    ];

    $rows = [];
    foreach ($orders as $order) {

      /* @var \Drupal\commerce_order\Entity\Order $order */
      // The link to the order.
      $order_url = Url::fromRoute('entity.commerce_order.canonical', ['commerce_order' => $order->id()], [
        'attributes' => [
          'target' => '_blank',
        ],
      ]);
      $customer_url = Url::fromRoute('entity.user.canonical', [
        'user' => $order->getCustomer()
          ->id(),
      ], [
        'attributes' => [
          'target' => '_blank',
        ],
      ]);

      // Format the total price of the order.
      $store = $order->getStore();
      $default_currency = $store->getDefaultCurrency();
      $total_price = $order->getTotalPrice();
      if (!empty($total_price)) {
        $currency = Currency::load($total_price->getCurrencyCode());
        $formatted_amount = $number_formatter->formatCurrency($total_price->getNumber(), $currency);
      }
      else {
        $formatted_amount = $number_formatter->formatCurrency(0, $default_currency);
      }

      // Add each row to the rows array.
      $rows[] = [
        Link::fromTextAndUrl($order->id(), $order_url),
        $order->getState()->getLabel(),
        Link::fromTextAndUrl($order->getCustomer()
          ->getDisplayName(), $customer_url),
        $formatted_amount,
      ];
    }

    $output = [
      '#theme' => 'table',
      '#header' => $header,
      '#rows' => $rows,
    ];
    $output['pager'] = [
      '#type' => 'pager',
    ];

    return \Drupal::service('renderer')->render($output);
  }

}
