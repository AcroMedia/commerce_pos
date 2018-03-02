<?php

namespace Drupal\commerce_pos_keypad_test\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form for testing keypad textfield elements.
 */
class KeypadTextElementForm extends FormBase {

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

    $form['cashier_id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Cashier ID'),
      '#description' => $this->t('ID of the cashier logging in'),
      '#commerce_pos_keypad' => [
        'type' => 'icon',
        'events' => [
          '.commerce-pos-cashier-login-form-log-in' => [
            'click' => [],
          ],
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
