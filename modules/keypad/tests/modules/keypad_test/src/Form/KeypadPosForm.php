<?php

namespace Drupal\commerce_pos_keypad_test\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form for testing keypad themed containers.
 */
class KeypadPosForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'commerce_pos_keypad_test_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['keypad'] = [
      '#type' => 'container',
      '#id' => 'commerce-pos-sale-keypad-wrapper',
      '#tree' => TRUE,
    ];
    $form['keypad']['amount'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Enter amount'),
      '#required' => TRUE,
      '#default_value' => 100,
      '#commerce_pos_keypad' => TRUE,
      '#attributes' => [
        'autofocus' => 'autofocus',
        'autocomplete' => 'off',
      ],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

  }

}
