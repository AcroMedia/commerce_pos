<?php

namespace Drupal\commerce_pos\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Configure example settings for this site.
 */
class CommercePOSSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'commerce_pos_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'commerce_pos.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('commerce_pos.settings');

    $form['payment_settings']['default_payment_gateway'] = [
      '#type' => 'select',
      '#title' => $this->t('Default Payment Gateway'),
      '#description' => t('Select the default payment method.'),
      '#empty_option' => t('- None -'),
      '#default_value' => $config->get('default_payment_gateway'),
      '#options' => $this->getPaymentGatewayOptions(),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    \Drupal::configFactory()->getEditable('commerce_pos.settings')
      // Set the submitted configuration setting.
      ->set('default_payment_gateway', $form_state->getValue('default_payment_gateway'))

      /* Need to verify if form values and settings are correct and reflect the nature of how settings will be handled before any save functionality is done. */
      ->save();

    // Validation of course needed as well.
    parent::submitForm($form, $form_state);
  }

  /**
   * Get the available payment options.
   */
  protected function getPaymentGatewayOptions() {
    // TODO: This should be dynamic.
    return [
      'pos_cash' => t('Cash'),
      'pos_credit' => t('Credit'),
      'pos_debit' => t('Debit'),
      'pos_gift_card' => t('Gift Card'),
    ];
  }

}
