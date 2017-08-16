<?php

namespace Drupal\commerce_pos\Entity;

use Drupal\user\User;


class Cashiers {

  static public function storeUser($username, $id) {
    $_SESSION['users'][$username] = array(
      'username' => $username,
      'id' => $id,
    );
  }

  static public function logInUser($username) {
    $user = User::load($username);
    user_login_finalize($user);
  }

  static public loadUsers

}