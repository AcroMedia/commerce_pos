<?php

namespace Drupal\commerce_pos\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class RegisterForm.
 */
class RegisterForm extends ContentEntityForm {

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    $name_array = $form_state->getValue('name');
    $name = $name_array['0']['value'];
    drupal_set_message($this->t('Successfully saved register @name.', ['@name' => $name]));
  }

}
