<?php

namespace Drupal\commerce_pos;

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
   * The container object.
   *
   * @var \Symfony\Component\DependencyInjection\ContainerInterface
   */
  protected $container;

  /**
   * The tempstore object.
   *
   * @var \Drupal\user\SharedTempStore
   */
  protected $tempStore;

  /**
   * The currentOrder object.
   *
   * @var \Drupal\commerce_pos\CurrentOrder
   */
  protected $currentOrder;

  /**
   * Constructs a new POS object.
   *
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   *   The service container.
   * @param \Drupal\user\PrivateTempStoreFactory $temp_store_factory
   *   The tempstore factory.
   * @param \Drupal\commerce_pos\CurrentOrder $current_order
   *   The current order service.
   */
  public function __construct(ContainerInterface $container, PrivateTempStoreFactory $temp_store_factory, CurrentOrder $current_order) {
    $this->container = $container;
    $this->tempStore = $temp_store_factory->get('commerce_pos');
    $this->currentOrder = $current_order;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container,
      $container->get('user.private_tempstore'),
      $container->get('commerce_pos.current_order')
    );
  }

  /**
   * Builds the POS form.
   *
   * @return array
   *   A renderable array containing the POS form.
   */
  public function content() {
    $register = \Drupal::service('commerce_pos.current_register')->get();

    if (empty($register) || !$register->isOpen()) {
      return \Drupal::formBuilder()->getForm('\Drupal\commerce_pos\Form\RegisterSelectForm');
    }

    $store_id = $register->getStoreId();

    $order = $this->currentOrder->get();

    if (!$order) {
      $order = Order::create([
        'type' => 'pos',
        'store_id' => $store_id,
        'uid' => User::getAnonymousUser()->id(),
        'field_cashier' => \Drupal::currentUser()->id(),
        'field_register' => $register->id(),
      ]);

      $order->save();

      $this->currentOrder->set($order);
    }

    $form_object = POSForm::create($this->container);
    $form_object->setEntity($order);

    $form_object
      ->setModuleHandler(\Drupal::moduleHandler())
      ->setEntityTypeManager(\Drupal::entityTypeManager())
      ->setOperation('pos')
      ->setEntityManager(\Drupal::entityManager());

    $form_state = (new FormState())->setFormState([]);

    return \Drupal::formBuilder()->buildForm($form_object, $form_state);
  }

}
