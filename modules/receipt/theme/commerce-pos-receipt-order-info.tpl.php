<?php

/**
 * @file
 * Default template for the receipt's order information.
 *
 * Available variables:
 *   - $order: The Commerce order that the receipt is for.
 */

?>

<div class="pos-order-info">
  <div class="order-number"><?php print t('Order:'); ?> <span><?php print $order->order_id ? $order->order_id : t('New Order'); ?></span></div>

  <?php if (!empty($order->uid)) { ?>
    <div class="order-customer"><?php print t('Customer:'); ?> <span><?php print format_username(user_load($order->uid)); ?></span></div>
  <?php } ?>
</div>
