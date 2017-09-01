<?php

namespace Drupal\commerce_pos\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\user\Form\UserLoginForm;
use Drupal\commerce_pos\Entity\Cashiers;

/**
 * Implements the cashier login form.
 */
class CashierForm extends UserLoginForm {

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);

    // TODO Replace with proper cashier ID field from user type.
    $form['cashier_id'] = [
      '#type' => 'textfield',
      '#title' => t('Cashier ID'),
      '#description' => t('ID of the cashier logging in'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
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

  /**
   * {@inheritdoc}
   */
  private function storeUser($username, $id) {
    Cashiers::storeUser($username, $id);
  }

}
