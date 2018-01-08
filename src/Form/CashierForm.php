<?php

namespace Drupal\commerce_pos\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\user\Form\UserLoginForm;

/**
 * Implements the cashier login form.
 */
class CashierForm extends UserLoginForm {

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    $form_state->setRedirect('commerce_pos.main');
  }

}
