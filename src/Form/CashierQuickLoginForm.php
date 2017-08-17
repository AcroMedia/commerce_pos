<?php

namespace Drupal\commerce_pos\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\user\Form;
use Drupal\Core\Entity\ContentEntityForm;
use Drupal\commerce_pos\Entity\Cashiers;


/**
 * Implements an example form.
 */
class CashierQuickLoginForm extends FormBase {

  public function buildForm(array $form, FormStateInterface $form_state)
  {
    $users = Cashiers::getUsers();

    if(!empty($users)) {
      foreach($users as $cashier) {
        if(!empty($cashier->username)) {
          //TODO : don't display current logged in user
          $form[$cashier->username]['id_check'] = array(
            '#type' => 'textfield',
            '#description' => t('The cashier ID for the user you want to use'),
            '#title' => $cashier->username,
          );
          $form[$cashier->username]['submit'] = array(
            '#type' => 'submit',
            '#value' => t('Switch to @username', array('@username' => $cashier->username)),
          );
        }
      }

    }
    else {
      drupal_set_message(t('NO USERS'), 'error');
    }


    return $form;
  }

  public function submitForm(array &$form, FormStateInterface $form_state)
  {
    // TODO: Implement submitForm() method.

  }

  public function getFormId()
  {
    return 'cashier-quick-login';
  }

}