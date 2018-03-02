<?php

namespace Drupal\commerce_pos_receipt\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Define a Configuration form for persisting header/footer.
 */
class ReceiptSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $header = $this->config('commerce_pos_receipt.settings')->get('header');
    $footer = $this->config('commerce_pos_receipt.settings')->get('footer');

    $form['commerce_pos_receipt_header'] = [
      '#type' => 'text_format',
      '#title' => $this->t('Header text'),
      '#description' => $this->t('This text will appear at the top of printed receipts.'),
      '#default_value' => $header,
      '#format' => NULL,
    ];

    $form['commerce_pos_receipt_footer'] = [
      '#type' => 'text_format',
      '#title' => $this->t('Footer text'),
      '#description' => $this->t('This text will appear at the bottom of printed receipts.'),
      '#default_value' => $footer,
      '#format' => NULL,
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    $values = $form_state->getValues();
    $this->config('commerce_pos_receipt.settings')
      ->set('header', $values['commerce_pos_receipt_header']['value'])
      ->set('footer', $values['commerce_pos_receipt_footer']['value'])
      ->set('header_format', $values['commerce_pos_receipt_header']['format'])
      ->set('footer_format', $values['commerce_pos_receipt_footer']['format'])
      ->save();
  }

  /**
   * Gets the configuration names that will be editable.
   *
   * @return array
   *   An array of configuration object names that are editable if called in
   *   conjunction with the trait's config() method.
   */
  protected function getEditableConfigNames() {
    return [
      'commerce_pos_receipt.settings',
    ];
  }

  /**
   * Returns a unique string identifying the form.
   *
   * @return string
   *   The unique string identifying the form.
   */
  public function getFormId() {
    return 'commerce_pos_receipt_settings';
  }

}
