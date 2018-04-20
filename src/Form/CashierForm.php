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
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildform($form, $form_state);

    $form['name']['#attributes']['placeholder'] = $this->t('Username');
    $form['pass']['#attributes']['placeholder'] = $this->t('Password');
    $form['actions']['submit']['#value'] = $this->t('Login');

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    $form_state->setRedirect('commerce_pos.main');
  }

}
