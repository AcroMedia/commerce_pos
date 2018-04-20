<?php

namespace Drupal\commerce_pos\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 * Provides route responses for the Cashier Login Page.
 */
class PosCashierLoginPage extends ControllerBase {

  /**
   * Returns a customer cashier login page.
   *
   * @return array
   *   A simple renderable array.
   */
  public function login() {
    if (isset($_COOKIE['commerce_pos_cashiers'])) {
      $cashiers = unserialize($_COOKIE['commerce_pos_cashiers']);

      usort($cashiers, function ($a, $b) {
        return $a['timestamp'] - $b['timestamp'];
      });

      $cashiers = array_slice($cashiers, 0, 10);
      setcookie('commerce_pos_cashiers', serialize($cashiers),
        time() + 31557600, '/');
    }
    else {
      $cashiers = NULL;
    }

    /* @var $register \Drupal\commerce_pos\Entity\Register */
    $register = \Drupal::service('commerce_pos.current_register')->get();
    if (isset($register)) {
      $store = $register->getStore();
    }
    else {
      /** @var \Drupal\commerce_store\StoreStorageInterface $store_storage */
      $store_storage = \Drupal::entityTypeManager()->getStorage('commerce_store');
      $store = $store_storage->loadDefault();
    }

    $status_messages = ['#type' => 'status_messages'];
    $messages = \Drupal::service('renderer')->renderRoot($status_messages);

    // Delete the messages now that we've grabbed them, so they don't keep showing.
    $messenger = \Drupal::messenger();
    $messenger->deleteAll();

    $page = [
      '#type' => 'page',
      '#theme' => 'commerce_pos_cashier_login_page',
      '#form' => \Drupal::formBuilder()->getForm('Drupal\commerce_pos\Form\CashierForm'),
      '#cashiers' => $cashiers,
      '#messages' => $messages,
      '#store_name' => $store->getName(),
    ];

    return $page;
  }

}
