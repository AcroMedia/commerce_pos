<?php

namespace Drupal\commerce_pos\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * Show a form to select the current register for this session.
 */
class RegisterSelectForm extends FormBase implements FormInterface {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'commerce_pos_register_select';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $registers = \Drupal::service('commerce_pos.registers')->getRegisters();

    if (empty($registers)) {
      // Return no registers error, link to setup registers.
    }

    $register_options = [];
    foreach ($registers as $id => $register) {
      $register_options[$id] = $register->getName();
    }

    $form['register'] = [
      '#type' => 'select',
      '#title' => $this->t('Select Register'),
      '#options' => $register_options,
    ];

    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Select Register'),
      '#button_type' => 'primary',
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    // Required by interface, currently no validation needed.
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $tempstore = \Drupal::service('user.private_tempstore')->get('commerce_pos');
    $tempstore->set('register', $form_state->getValue('register'));
  }

}
