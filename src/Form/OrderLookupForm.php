<?php

namespace Drupal\commerce_pos\Form;

use Drupal\commerce_order\Entity\Order;
use Drupal\commerce_price\Entity\Currency;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\HtmlCommand;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Url;
use Drupal\user\Entity\User;

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
    $form['#attached']['library'][] = 'commerce_pos/global';
    $form['#attached']['library'][] = 'commerce_pos/order_lookup';
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
          'message' => $this->t('Searching orders...'),
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
      $form['order_lookup']['results']['result'] = $this->searchOrderResults();
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
   * @param string $state
   *   (optional) The order state to match. Defaults to 'draft'.
   * @param string $operator
   *   (optional) The operator to use when matching on state. Defaults to '!='.
   * @param \Drupal\Core\StringTranslation\TranslatableMarkup $empty_message
   *   (optional) A translated search string to display if no results are
   *   returned.
   *
   * @return \Drupal\Core\StringTranslation\TranslatableMarkup|array
   *   The render array or a translatable string.
   */
  public function searchOrderResults($search_text = '', $state = 'draft', $operator = '!=', TranslatableMarkup $empty_message = NULL) {
    $result_limit = $this->config('commerce_pos.settings')->get('order_lookup_limit');
    $do_like_search = $this->config('commerce_pos.settings')->get('order_lookup_like_search');

    // Create the query now.
    // If we're doing a like search, form the query differently.
    if ($do_like_search) {
      $query = \Drupal::database();
      $query = $query->select('commerce_order', 'o')
        ->condition('type', 'pos')
        ->condition('state', $state, $operator)
        ->orderBy('order_id', 'DESC')
        ->range(0, $result_limit)
        ->fields('o', ['order_id']);

      // If the search text was an order ID.
      if (is_numeric($search_text)) {
        $query->condition('order_id', $search_text);
      }
      // Else, we check if we have a matching customer name or email.
      else {
        $query->join('users_field_data', 'u', 'u.uid = o.uid');
        $query->condition($query->orConditionGroup()
          ->condition('u.name', '%' . $search_text . '%', 'LIKE')
          ->condition('u.mail', '%' . $search_text . '%', 'LIKE')
          ->condition('o.mail', '%' . $search_text . '%', 'LIKE')
        );
      }
      // Execute the query.
      $result = $query->execute()->fetchCol();
    }
    // Else, if we're doing an exact match search.
    else {
      $query = \Drupal::entityQuery('commerce_order');
      $query = $query->condition('state', $state, $operator);
      $query = $query->condition('type', 'pos');
      $query = $query->sort('order_id', 'DESC')
        ->range(0, !empty($result_limit) ? $result_limit : 10);

      if ($search_text) {
        // If the search text was an order ID.
        if (is_numeric($search_text)) {
          $query->condition('order_id', $search_text);
        }
        // Else, we check if we have a matching customer name or email.
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
      }

      $result = $query->execute();
    }

    // If we've got results, let's output the details in a table.
    if (!empty($result)) {
      $orders = Order::loadMultiple($result);
      $order_markup = $this->buildOrderTable($orders);
    }
    else {
      if ($search_text) {
        $order_markup = $this->t('The order could not be found or does not exist.');
      }
      elseif ($empty_message) {
        $order_markup = $empty_message;
      }
      else {
        $order_markup = $this->t('There are currently no POS orders.');
      }
      // Convert into something renderable.
      $order_markup = [
        '#markup' => $order_markup,
      ];
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
      $this->t('Order ID'),
      $this->t('Date'),
      $this->t('Status'),
      $this->t('Cashier'),
      $this->t('Customer'),
      $this->t('Contact Email'),
      $this->t('Total'),
      $this->t('Action'),
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
      $edit_url = Url::fromRoute('commerce_pos.main', ['commerce_order' => $order->id()]);

      $cashier = User::load($order->get('field_cashier')->getValue()[0]['target_id']);

      $cashier_url = Url::fromRoute('entity.user.canonical', [
        'user' => $cashier->id(),
      ], [
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

      // Form the customer link and email.
      $customer = [
        '#type' => 'inline_template',
        '#template' => '{{ user_link }} <br \> {{ user_email }}',
        '#context' => [
          'user_link' => Link::fromTextAndUrl($order->getCustomer()->getDisplayName(), $customer_url),
          'user_email' => $order->getCustomer()->getEmail(),
        ],
      ];

      // Now, add each row to the rows array.
      $rows[] = [
        Link::fromTextAndUrl($order->id(), $order_url),
        DrupalDateTime::createFromTimestamp($order->getChangedTime())->format('Y-m-d H:i'),
        $order->getState()->getLabel(),
        Link::fromTextAndUrl($cashier->getDisplayName(), $cashier_url),
        ['data' => $customer],
        $order->getEmail(),
        $formatted_amount,
        Link::fromTextAndUrl($this->t('edit'), $edit_url),
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

    return $output;
  }

}
