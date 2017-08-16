<?php

namespace Drupal\commerce_pos\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class RegisterForm.
 */
class RegisterForm extends EntityForm {

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    $register = $this->entity;
    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#maxlength' => 255,
      '#default_value' => $register->label(),
      '#required' => TRUE,
    ];

    $form['id'] = [
      '#type' => 'machine_name',
      '#default_value' => $register->id(),
      '#machine_name' => [
        'exists' => '\Drupal\commerce_pos\Entity\Register::load',
      ],
      '#disabled' => !$register->isNew(),
    ];

    /* You will need additional form elements for your custom properties. */

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $register = $this->entity;
    $status = $register->save();

    switch ($status) {
      case SAVED_NEW:
        drupal_set_message($this->t('Created the %label Register.', [
          '%label' => $register->label(),
        ]));
        break;

      default:
        drupal_set_message($this->t('Saved the %label Register.', [
          '%label' => $register->label(),
        ]));
    }
    $form_state->setRedirectUrl($register->toUrl('collection'));
  }

}
