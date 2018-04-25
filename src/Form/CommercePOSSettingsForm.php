<?php

namespace Drupal\commerce_pos\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\search_api\Entity\Server;

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
      '#description' => $this->t('Select the default payment method.'),
      '#empty_option' => $this->t('- None -'),
      '#default_value' => $config->get('default_payment_gateway'),
      '#options' => $this->getPaymentGatewayOptions(),
    ];

    $wrapper_id = 'product_search_settings';
    $form['product_search'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Product Search'),
      '#prefix' => '<div id="' . $wrapper_id . '">',
      '#suffix' => '</div>',
    ];

    $server_storage = \Drupal::entityTypeManager()->getStorage('search_api_server');
    /** @var \Drupal\search_api\ServerInterface[] $servers */
    $servers = $server_storage->loadMultiple();

    $server_options = [];
    foreach ($servers as $server) {
      $server_options[$server->id()] = $server->get('name');
    }

    $form['product_search']['server'] = [
      '#type' => 'select',
      '#title' => $this->t('Server'),
      '#options' => $server_options,
      '#empty_option' => $this->t('- Select -'),
      '#default_value' => $config->get('product_search_server'),
      '#ajax' => [
        'callback' => '::ajaxProductSearchRefresh',
        'wrapper' => $wrapper_id,
      ],
    ];

    if ($form_state->getValue('server')) {
      $server = $form_state->getValue('server');
    }
    elseif ($config->get('product_search_server')) {
      $server = $config->get('product_search_server');
    }

    if (isset($server)) {
      $server = Server::load($server);

      if (isset($server)) {
        $indexes = $server->getIndexes();

        $index_options = [];
        foreach ($indexes as $index) {
          $index_options[$index->id()] = $index->get('name');
        }

        $form['product_search']['index'] = [
          '#type' => 'select',
          '#title' => 'Search Index',
          '#options' => $index_options,
          '#default_value' => $config->get('product_search_index'),
          '#empty_option' => $this->t('- Select -'),
        ];
      }
    }

    $form['order_lookup'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Order Lookup'),
    ];

    $form['order_lookup']['order_lookup_limit'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Order Lookup Limit'),
      '#maxlength' => 2,
      '#size' => 2,
      '#required' => TRUE,
      '#description' => $this->t('Select the number of results to display for the POS order lookup.'),
      '#default_value' => $config->get('order_lookup_limit'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    $this->configFactory()->getEditable('commerce_pos.settings')
      // Set the submitted configuration setting.
      ->set('default_payment_gateway', $form_state->getValue('default_payment_gateway'))
      ->set('order_lookup_limit', $form_state->getValue('order_lookup_limit'))
      ->set('product_search_server', $form_state->getValue('server'))
      ->set('product_search_index', $form_state->getValue('index'))

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
      'pos_cash' => $this->t('Cash'),
      'pos_credit' => $this->t('Credit'),
      'pos_debit' => $this->t('Debit'),
      'pos_gift_card' => $this->t('Gift Card'),
    ];
  }

  /**
   * AJAX callback for the product search options.
   */
  public function ajaxProductSearchRefresh($form, &$form_state) {
    return $form['product_search'];
  }

}
