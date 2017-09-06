<?php

namespace Drupal\commerce_pos\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\user\Entity\User;

/**
 * Cashier Quick Login Form.
 */
class CashierQuickLoginForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $cashiers = \Drupal::service('commerce_pos.cashier');
    $users = $cashiers->getCashiers();
    $current_uid = \Drupal::currentUser()->id();

    if (!empty($users)) {
      // Escaping the current user name from the list.
      unset($users[$current_uid]);
      foreach ($users as $user) {
        $name = User::load($user)->getAccountName();
        $form[$name]['id_check'] = [
          '#type' => 'textfield',
          '#description' => t('The cashier ID for the user you want to use'),
          '#title' => $name,
        ];
        $form['user_login_id'] = [
          '#type' => 'hidden',
          '#value' => $user,
        ];
        $form[$name]['submit'] = [
          '#type' => 'submit',
          '#value' => t('Switch to @username', array('@username' => $name)),
        ];

      }
    }
    else {
      drupal_set_message(t('NO USERS'), 'error');
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'cashier-quick-login';
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    // Cashier Id to be crossed checked.
    $id_check = $form_state->getValue('id_check');
    // User ID to be logged in.
    $user_id = $form_state->getValue('user_login_id');
    // Get the cashier ID associted with the particular Cashier User.
    $cashier_id = User::load($user_id)->get('field_cashier_id')->getValue()[0]['value'];
    if ($id_check != $cashier_id) {
      $form_state->setErrorByName('id_check', $this->t("The Cashier ID is not matching."));
    }

  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $user_id = $form_state->getValue('user_login_id');
    // Load the user to be logged in.
    $user = User::load($user_id);
    user_login_finalize($user);
  }

}
