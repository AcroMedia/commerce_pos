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
    $form['#attached']['library'][] = 'commerce_pos_keypad/keypad';
    $form['#attached']['library'][] = 'commerce_pos/form';

    $form['keypad'] = [
      '#type' => 'container',
      '#id' => 'commerce-pos-sale-keypad-wrapper',
      '#tree' => TRUE,
      '#theme' => 'commerce_pos_keypad',
    ];
    $form['keypad']['amount'] = [
      '#type' => 'textfield',
      '#title' => t('Enter amount'),
      '#required' => TRUE,
      '#default_value' => 100,
      '#attributes' => [
        'autofocus' => 'autofocus',
        'autocomplete' => 'off',
        'class' => [
          'commerce-pos-payment-keypad-amount',
        ],
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
