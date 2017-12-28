<?php

namespace Drupal\commerce_pos\Form;

use Drupal\commerce_store\Entity\Store;
use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * Class RegisterForm.
 */
class RegisterForm extends ContentEntityForm {

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    // Skip building the form if there are no available stores.
    $stores = Store::loadMultiple();
    if (empty($stores)) {
      $url = URL::fromRoute('entity.commerce_store.add_page');
      drupal_set_message($this->t('Registers can\'t be created until a store has been added. <a href=":url">Add a new store.</a>', [':url' => $url->toString()]), 'error');

      $form_state->set('no_stores', TRUE);

      return [];
    }

    return parent::form($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  protected function actions(array $form, FormStateInterface $form_state) {
    // Hide the action buttons if we have no stores.
    if ($form_state->get('no_stores')) {
      return [];
    }

    return parent::actions($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $status = parent::save($form, $form_state);
    drupal_set_message($this->t('Successfully saved register @name.', ['@name' => $this->entity->label()]));
    $form_state->setRedirectUrl($this->entity->toUrl('collection'));
    return $status;
  }

}
