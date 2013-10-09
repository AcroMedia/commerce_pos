<div class="pos-order-info">
  <div class="order-number"><?php print t('Order:'); ?> <span><?php print $order->order_id ? $order->order_id : t('New Order'); ?></span></div>
  <div class="order-customer"><?php print t('Customer:'); ?> <span><?php print format_username(user_load($order->uid)); ?></span></div>
  <div class="order-status"><?php print t('Status:'); ?> <span><?php print commerce_order_status_get_title($order->status); ?></span></div>
</div>