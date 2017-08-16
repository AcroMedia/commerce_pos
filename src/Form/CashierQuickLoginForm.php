<?php

namespace Drupal\commerce_pos\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\user\Form;
use Drupal\Core\Entity\ContentEntityForm;

/**
 * Implements an example form.
 */
class CashierRegisterForm extends ContentEntityForm {

  public function getFormId() {
    return 'cashier-register-form';
  }

  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);

    dpm($form, 'form');

    return $form;
  }

  public function submitForm(array &$form, FormStateInterface $form_state) {
//    parent::submitForm($form, $form_state);
  }

}