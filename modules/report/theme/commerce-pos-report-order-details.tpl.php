<?php

/**
 * @file
 * Default template file for order details on the Commerce POS Report journal role.
 */
?>

<div class="commerce-pos-report-order-details-container">
  <?php if ($print_link) { ?>
    <div class="commerce-pos-report-order-details-print">
      <?php print $print_link; ?>
    </div>
  <?php } ?>

  <table>
    <tr>
      <td><?php print t('Order No:') ?></td>
      <td><?php print $order->order_number; ?></td>
      <td><?php print t('Payment Type:'); ?></td>
      <td><?php print $payment_type; ?></td>
    </tr>
    <tr>
      <td><?php print t('Date:') ?></td>
      <td><?php print $transaction_date; ?></td>
      <td><?php print t('Cashier:') ?></td>
      <td><?php print $cashier; ?></td>
    </tr>
    <tr>
      <td><?php print t('Customer:') ?></td>
      <td colspan="3"><?php print $customer; ?></td>
    </tr>
  </table>

  <div class="commerce-pos-report-order-details-line-items">
    <?php print render($line_items); ?>
  </div>

  <?php if ($messages) { ?>
    <div class="commerce-pos-report-order-details-messages">
      <h3><?php print t('Order Notes') ?></h3>
      <?php print $messages; ?>
    </div>
  <?php } ?>

  <div class="commerce-pos-report-order-details-total">
    <?php print render($order_total); ?>
    <div class="commerce-pos-report-order-details-payments">
      <?php print $balance_summary; ?>
    </div>
  </div>
</div>
