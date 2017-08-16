<?php

namespace Drupal\commerce_pos\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\user\Form;
use Drupal\commerce_pos\Entity\Cashiers;

/**
 * Implements an example form.
 */
class CashierForm extends Form\UserLoginForm {

  public function buildForm(array $form, FormStateInterface $form_state)
  {
    $form = parent::buildForm($form, $form_state);

    //TODO : replace with proper cashier ID field from user type
    $form['cashier_id'] = array(
      '#type' => 'textfield',
      '#title' => t('Cashier ID'),
      '#description' => t('ID of the cashier logging in'),
    );

    return $form;
  }

  public function submitForm(array &$form, FormStateInterface $form_state) {
//    $token = $this->generateToken();
    parent::submitForm($form, $form_state);
    $username = $form_state->getValue('name');
    $cashier_id = $form_state->getValue('cashier_id');

    if (!empty($username) && !empty($cashier_id)) {
      $this->storeUser($username, $cashier_id);
    }
    else {
      drupal_set_message(t('Failed to quick store user'), 'error');
    }

  }

  private function storeUser($username, $id) {
    Cashiers::storeUser($username, $id);
  }
}