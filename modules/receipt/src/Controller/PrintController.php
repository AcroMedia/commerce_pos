<?php

namespace Drupal\commerce_pos_receipt\Controller;

use Drupal\commerce_order\Entity\Order;
use Drupal\commerce_order\Entity\OrderInterface;
use Drupal\commerce_pos_receipt\Ajax\PrintReceiptCommand;
use Drupal\commerce_price\Entity\Currency;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\HtmlCommand;
use Drupal\Core\Ajax\SettingsCommand;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * Class PrintController.
 */
class PrintController extends ControllerBase {

  /**
   *
   */
  public function ajaxForm(array &$form, FormStateInterface $form_state) {
    \Drupal::logger('commerce_pos_receipt')->notice('receipt ajax wtf');
    // The order is also available from the form_state in the build info, I am unsure if I should use that instead?
    $order_id = $form_state->getValue('order_id');
    $order = Order::load($order_id);

    $deferred_element = $form_state->getTriggeringElement();

    return $this->ajaxReceipt($order, $deferred_element['#name']);
  }

  /**
   * AJAX receipt function, can be used via URL or callback via appropriate methods.
   */
  public function ajaxReceipt(OrderInterface $commerce_order) {
    $renderer = \Drupal::service('renderer');

    $build = $this->showReceipt($commerce_order);
    unset($build['#receipt']['print']);
    $module_handler = \Drupal::service('module_handler');
    $module_path = $module_handler->getModule('commerce_pos_receipt')->getPath();

    $response = new AjaxResponse();

    // TODO: could this be turned into 1 command, and if so, is that better?
    $response->addCommand(new HtmlCommand('#commerce-pos-receipt', $renderer->render($build)));
    $response->addCommand(new SettingsCommand([
      'commercePosReceipt' => [
        'cssUrl' => Url::fromUri('base:' . $module_path . '/css/commerce_pos_receipt_print.css', ['absolute' => TRUE])->toString(),
      ],
    ], TRUE));
    $response->addCommand(new PrintReceiptCommand('#commerce-pos-receipt'));

    return $response;
  }

  /**
   * A controller callback.
   */
  public function showReceipt(OrderInterface $commerce_order) {

    $number_formatter_factory = \Drupal::service('commerce_price.number_formatter_factory');
    $number_formatter = $number_formatter_factory->createInstance();

    $sub_total_price = $commerce_order->getSubtotalPrice();
    $currency = Currency::load($sub_total_price->getCurrencyCode());
    $formatted_amount = $number_formatter->formatCurrency($sub_total_price->getNumber(), $currency);

    //In the future add a setting to display group or individual for same skus
    $items = $commerce_order->getItems();
    foreach ($items as $item) {
      $totals[] = [
        $item->getTitle() . ' (' . $item->getQuantity() . ')',
        $number_formatter->formatCurrency($item->getAdjustedTotalPrice()->getNumber(), $currency),
      ];
    }

    $totals[] = ['Subtotal', $formatted_amount];

    // Commerce appears to have a bug where if no adjustments exist, it will
    // return a 0 => null array, which will still trigger a foreach loop.
    foreach ($commerce_order->collectAdjustments() as $key => $adjustment) {
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
    $total_price = $commerce_order->getTotalPrice();
    $formatted_amount = $number_formatter->formatCurrency($total_price->getNumber(), $currency);
    $totals[] = ['Total', $formatted_amount];

    $payment_storage = \Drupal::entityTypeManager()->getStorage('commerce_payment');
    $payments = $payment_storage->loadMultipleByOrder($commerce_order);
    foreach ($payments as $payment) {
      $totals[] = ['Payment', $payment->getState()->getLabel()];
    }
    $ajax_url = URL::fromRoute('commerce_pos_receipt.ajax', ['commerce_order' => $commerce_order->id()], [
      'attributes' => [
        'class' => ['use-ajax', 'button'],
      ],
    ]);

    $config = \Drupal::config('commerce_pos_receipt.settings');
    $build = ['#theme' => 'commerce_pos_receipt'];
    $build['#receipt'] = [
      'header' => [
        '#markup' => check_markup($config->get('header'), $config->get('header_format')),
      ],
      'body' => [
        '#type' => 'table',
        '#rows' => $totals,
      ],
      'footer' => [
        '#markup' => check_markup($config->get('footer'), $config->get('footer_format')),
      ],
      'print' => [
        '#title' => t('Print receipt'),
        '#prefix' => '<div id="commerce-pos-receipt"></div>',
        '#type' => 'link',
        '#url' => $ajax_url,
      ],
    ];
    return $build;
  }

}
