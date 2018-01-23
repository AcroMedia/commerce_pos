<?php

namespace Drupal\commerce_pos_receipt\Controller;

use Drupal\commerce_order\Entity\OrderInterface;
use Drupal\commerce_pos_receipt\Ajax\CompleteOrderCommand;
use Drupal\commerce_pos_receipt\Ajax\PrintReceiptCommand;
use Drupal\commerce_price\Entity\Currency;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\HtmlCommand;
use Drupal\Core\Ajax\SettingsCommand;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Url;

/**
 * Class PrintController.
 */
class PrintController extends ControllerBase {

  /**
   * A controller callback.
   */
  public function ajaxReceipt(OrderInterface $commerce_order, $print_or_email) {
    $renderer = \Drupal::service('renderer');

    $build = $this->showReceipt($commerce_order);
    unset($build['#receipt']['print']);
    $build = $renderer->render($build);

    $response = new AjaxResponse();

    // If the user opted to get an email with the receipt.
    if ($print_or_email != 'print') {
      $this->sendEmailReceipt($commerce_order, $build);

      // Finally, if the user only wants an email to be sent, we just call the
      // complete order command which submits the form as usual.
      if ($print_or_email == 'email') {
        $response->addCommand(new CompleteOrderCommand());
      }
    }

    // If the user opted to print the receipt.
    if ($print_or_email != 'email') {
      $module_handler = \Drupal::service('module_handler');
      $module_path = $module_handler->getModule('commerce_pos_receipt')->getPath();

      // TODO: could this be turned into 1 command, and if so, is that better?
      $response->addCommand(new HtmlCommand('#commerce-pos-receipt', $build));
      $response->addCommand(new SettingsCommand([
        'commercePosReceipt' => [
          'cssUrl' => Url::fromUri('base:' . $module_path . '/css/commerce_pos_receipt_print.css', ['absolute' => TRUE])->toString(),
        ],
      ], TRUE));
      $response->addCommand(new PrintReceiptCommand('#commerce-pos-receipt'));
    }

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

    // In the future add a setting to display group or individual for same skus.
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

  /**
   * Sends an email with the order receipt.
   *
   * @param object $commerce_order
   *   The order entity.
   * @param string $build
   *   The receipt markup.
   */
  public function sendEmailReceipt($commerce_order, $build) {
    $renderer = \Drupal::service('renderer');

    // Send an email with the receipt.
    $mail_manager = \Drupal::service('plugin.manager.mail');
    $module = 'commerce_pos_receipt';
    $key = 'commerce_pos_order_receipt';
    $to = $commerce_order->getEmail();
    $customer = $commerce_order->getCustomer();
    $themed_email_message = [
      '#theme' => 'commerce-pos-receipt-email',
      '#customer_name' => $customer->getAccountName(),
      '#order_id' => $commerce_order->id(),
      '#receipt_markup' => $build,
      '#site_name' => \Drupal::config('system.site')->get('name'),
    ];
    $params['message'] = $renderer->render($themed_email_message);
    $params['order_id'] = $commerce_order->id();
    $langcode = \Drupal::currentUser()->getPreferredLangcode();
    $send = TRUE;

    // Officially, send the email.
    $result = $mail_manager->mail($module, $key, $to, $langcode, $params, NULL, $send);

    $message_type = 'status';
    // If there was a problem sending the email.
    if ($result['result'] !== TRUE) {
      $message = t('There was a problem sending the email to @mail.', [
        '@mail' => $commerce_order->getEmail(),
      ]);
      $message_type = 'error';
    }
    // Else, if it was successful.
    else {
      $message = $this->t('An email with the receipt has been successfully sent to @mail.', [
        '@mail' => $commerce_order->getEmail(),
      ]);
    }

    // Display a message to the user regarding the result of sending the mail.
    drupal_set_message($message, $message_type);
    // Log the result in the watchdog as well.
    if ($message_type == 'error') {
      \Drupal::logger('commerce_pos_receipt')->error($message);
    }
    else {
      \Drupal::logger('commerce_pos_receipt')->notice($message);
    }
  }

  /**
   * Checks if the receipt should be printable. The order needs to be placed.
   *
   * @param \Drupal\commerce_order\Entity\OrderInterface $commerce_order
   *   The commerce order.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The access result.
   */
  public function checkAccess(OrderInterface $commerce_order) {
    return AccessResult::allowedIf($this->currentUser()->hasPermission('administer commerce_order') && $commerce_order->getPlacedTime())->cachePerPermissions()->cacheUntilEntityChanges($commerce_order);
  }

}
