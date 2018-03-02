<?php

namespace Drupal\commerce_pos\Controller;

use Drupal\commerce_pos\Form\POSForm;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Form\FormState;
use Drupal\commerce_order\Entity\Order;
use Drupal\user\Entity\User;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\user\PrivateTempStoreFactory;

/**
 * Provides main POS page.
 */
class POS extends ControllerBase {

  /**
   * The commerce_pos private temp store key for the current order ID.
   */
  const CURRENT_ORDER_KEY = 'current_order_id';

  /**
   * The container object.
   *
   * @var \Symfony\Component\DependencyInjection\ContainerInterface
   */
  protected $container;

  /**
   * The tempstore object.
   *
   * @var \Drupal\user\PrivateTempStore
   */
  protected $tempStore;

  /**
   * Constructs a new POS object.
   *
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   *   The service container.
   * @param \Drupal\user\PrivateTempStoreFactory $temp_store_factory
   *   The tempstore factory.
   */
  public function __construct(ContainerInterface $container, PrivateTempStoreFactory $temp_store_factory) {
    $this->container = $container;
    $this->tempStore = $temp_store_factory->get('commerce_pos');
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container,
      $container->get('user.private_tempstore')
    );
  }

  /**
   * Builds the POS form.
   *
   * @param \Drupal\commerce_order\Entity\Order $commerce_order
   *   The order to edit.
   *
   * @return array
   *   A renderable array containing the POS form.
   */
  public function content(Order $commerce_order = NULL) {
    $register = \Drupal::service('commerce_pos.current_register')->get();

    if (empty($register) || !$register->isOpen()) {
      // If we're opening a new register, clear our current order. If it exists
      // we don't want to pick up an older order at this point.
      $this->tempStore->set('current_order_id', FALSE);

      return $this->formBuilder()->getForm('\Drupal\commerce_pos\Form\RegisterSelectForm');
    }

    $store_id = $register->getStoreId();

    // If no order has been passed through and we have a current order ID. Check
    // the validity of the current order.
    $current_order_id = $this->tempStore->get('current_order_id');
    if (!$commerce_order && $current_order_id) {
      $commerce_order = Order::load($current_order_id);
    }

    // Create a new draft order if we still don't have an order.
    if (!$commerce_order) {
      $commerce_order = Order::create([
        'type' => 'pos',
        'store_id' => $store_id,
        'uid' => User::getAnonymousUser()->id(),
        'field_cashier' => $this->currentUser()->id(),
        'field_register' => $register->id(),
      ]);
      // Immediately create a new draft order.
      $commerce_order->save();
    }
    elseif ($commerce_order->field_register->entity->id() != $register->id() && $commerce_order->getState()->value != 'completed') {
      // The order is not on this register so it needs to be updated.
      $commerce_order->set('field_register', $register->id());
      $commerce_order->save();
    }

    // Store the current order ID in the private store so that a cashier can
    // easily return to the same order.
    $this->tempStore->set('current_order_id', $commerce_order->id());

    $form_object = POSForm::create($this->container);
    $form_object->setEntity($commerce_order);

    $form_object
      ->setModuleHandler($this->moduleHandler())
      ->setEntityTypeManager($this->entityTypeManager())
      ->setOperation('pos')
      ->setEntityManager($this->entityManager());

    $form_state = (new FormState())->setFormState([]);

    // Save the existing order items in the order to the form state so we could
    // keep track of what changed during this particular transaction.
    $initial_items_on_order = [];
    foreach ($commerce_order->getItems() as $order_item) {
      /** @var \Drupal\commerce_order\Entity\OrderItem $order_item */
      $initial_items_on_order[$order_item->id()] = $order_item->getPurchasedEntityId();
    }
    $form_state->set('initial_items_on_order', $initial_items_on_order);

    // Set the step to edit order, if we're editing a completed order.
    if ($commerce_order->getState()->getValue()['value'] == 'completed') {
      $form_state->set('is_edit_order', TRUE);
    }

    return $this->formBuilder()->buildForm($form_object, $form_state);
  }

}
