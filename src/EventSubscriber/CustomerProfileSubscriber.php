<?php

namespace Drupal\commerce_pos\EventSubscriber;

use Drupal\commerce_tax\Event\CustomerProfileEvent;
use Drupal\profile\Entity\Profile;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Supplies a backup customer profile used for calculating tax.
 */
class CustomerProfileSubscriber implements EventSubscriberInterface {

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    return [
      'commerce_tax.customer_profile' => ['onCustomerProfile'],
    ];
  }

  /**
   * Supplies a backup customer profile used for calculating tax.
   *
   * By default orders are taxed using the billing profile, but
   * pos orders probably don't have a billable address, so the store
   * address should be used.
   *
   * @param \Drupal\commerce_tax\Event\CustomerProfileEvent $event
   *   The transition event.
   */
  public function onCustomerProfile(CustomerProfileEvent $event) {
    $order_item = $event->getOrderItem();
    $order = $order_item->getOrder();

    if ($order->bundle() != 'pos') {
      return;
    }
    if (!empty($order->getBillingProfile())) {
      return;
    }

    $store = $order->getStore();
    $profile = Profile::create([
      'type' => 'customer',
      'uid' => 0,
      'address' => $store->getAddress(),
    ]);
    $event->setCustomerProfile($profile);
  }

}
