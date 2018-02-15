<?php

namespace Drupal\commerce_pos_keypad_test\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form for testing keypad textfield elements.
 */
class InputBoxElementForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'commerce_pos_keypad_input_box_test_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['amount'] = [
      '#type' => 'textfield',
      '#size' => 10,
      '#maxlength' => 10,
      '#attributes' => [
        'class' => ['commerce-pos-report-declared-input'],
        'data-currency-code' => 'USD',
        'data-amount' => 0,
        'data-payment-method-id' => 'pos_cash',
        'data-expected-amount' => 100,
      ],
      '#required' => TRUE,
      '#commerce_pos_keypad' => [
        'type' => 'cash input',
        'currency_code' => 'USD',
      ],
      '#field_prefix' => '$',
      '#field_suffix' => '',
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

  }

}
