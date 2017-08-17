<?php

namespace Drupal\commerce_pos\Entity;

use Drupal\user\User;
use Drupal\commerce_pos\Entity\Cashier;


class Cashiers {

  private static $users;

  static public function storeUser($username, $id) {
    $_SESSION['users'][$username] = array(
      'username' => $username,
      'id' => $id,
    );
  }

  static public function logInUser($username, $id) {
    $cashier = new Cashier($username, $id);
    self::$users[$id] = $cashier;
    $cashier->login();
    self::storeUser($username, $id);
  }

  static public function loadUsers() {
    if(!empty($_SESSION['users'])) {
      foreach($_SESSION['users'] as $quick_user) {
        self::$users[$quick_user['id']] = new Cashier($quick_user['username'], $quick_user['id']);
      }
    }

    return self::$users;
  }

  static public function getUsers() {
    self::loadUsers();
    return self::$users;
  }

}