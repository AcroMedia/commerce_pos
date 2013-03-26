<div class="pos-order-info">
  <div class="order-number">Order: <span><?php print $order->order_id ? $order->order_id : t('New Order'); ?></span></div>
  <div class="order-customer">Customer: <span><?php print format_username(user_load($order->uid)); ?></span></div>
  <div class="order-status">Status: <?php print commerce_order_status_get_title($order->status); ?></div>
</div>