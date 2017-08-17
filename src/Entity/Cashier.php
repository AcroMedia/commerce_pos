<?php

namespace Drupal\commerce_pos\Entity;

//use Drupal\user\Entity;
//use Drupal\user\User;
use Drupal\user\Entity\User;


class Cashier {
  public $user;
  public $id;

  function __construct($username, $id) {
    $this->id = $id;
    $this->username = $username;
    $this->user = User::load($username);
  }

  function login() {
    //TODO : some error checks and whatnot
    user_login_finalize($this->user);
  }

  function logout() {
    //TODO : some error checks and whatnot
    user_user_logout($this->user);
  }

}