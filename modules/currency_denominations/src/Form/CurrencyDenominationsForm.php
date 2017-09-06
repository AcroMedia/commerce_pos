<?php

namespace Drupal\commerce_pos_currency_denominations\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;

/**
 * Class CurrencyDenominationsForm.
 */
class CurrencyDenominationsForm extends EntityForm {

  /**
   * The currency denominations storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $storage;

  /**
   * Creates a new CurrencyDenominationsForm object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    $this->storage = $entity_type_manager->getStorage('commerce_pos_currency_denoms');
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    /** @var \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager */
    $entity_type_manager = $container->get('entity_type.manager');

    return new static($entity_type_manager);
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);
    /** @var \Drupal\commerce_price\Entity\CurrencyInterface $currency_denominations */
    $currency_denominations = $this->entity;
    $form['currencyCode'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Currency Code'),
      '#maxlength' => 255,
      '#default_value' => $currency_denominations->getCurrencyCode(),
      '#required' => TRUE,
      '#description' => $this->t('Currency code in three Uppercase letters, example: USD.'),
    ];

    // Gather the number of denoms in the form already.
    $denoms = $form_state->get('denoms');
    // We have to ensure that there is at least one name field.
    if ($denoms === NULL) {
      // While editing.
      $denom = $currency_denominations->getDenominations();
      if (count($denom) > 1) {
        $form_state->set('denoms', (count($denom) - 1));
        $denoms = count($denom) - 1;
      }
      else {
        $form_state->set('denoms', 1);
        $denoms = 1;
      }
    }

    $form['#tree'] = TRUE;
    $form['denominations'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Add Denominations.'),
      '#prefix' => '<div id="denoms-fieldset-wrapper">',
      '#suffix' => '</div>',
      '#description' => $this->t('A denomination type is USD, for example. Denominations are 1USD'),
    ];

    for ($i = 0; $i < $denoms; $i++) {
      $form['denominations'][$i] = [
        '#type' => 'fieldset',
      ];
      $default_label = [];
      $default_amount = [];
      if (!empty($denom)) {
        $default_label = $denom[$i]['label'];
        $default_amount = $denom[$i]['amount'];
      }
      $form['denominations'][$i]['label'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Name'),
        '#maxlength' => 255,
        '#default_value' => $default_label,
        '#required' => TRUE,
        '#description' => $this->t('For example Denominations is 1USD, Denomination Name also 1USD'),
      ];
      $form['denominations'][$i]['amount'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Amount'),
        '#maxlength' => 255,
        '#default_value' => $default_amount,
        '#required' => TRUE,
        '#description' => $this->t('For example Denominations is 1USD, Amount is 1.'),
      ];
    }
    $form['denominations']['actions'] = [
      '#type' => 'actions',
    ];
    $form['denominations']['actions']['add_denom'] = [
      '#type' => 'submit',
      '#value' => t('Add one more'),
      '#submit' => ['::addOne'],
      '#ajax' => [
        'callback' => '::addmoreCallback',
        'wrapper' => 'denoms-fieldset-wrapper',
      ],
    ];
    // If there is more than one denomination, add the remove button.
    if ($denoms > 1) {
      $form['denominations']['actions']['remove_denom'] = [
        '#type' => 'submit',
        '#value' => t('Remove one'),
        '#submit' => ['::removeCallback'],
        '#ajax' => [
          'callback' => '::addmoreCallback',
          'wrapper' => 'denoms-fieldset-wrapper',
        ],
      ];
    }

    return $form;
  }

  /**
   * Callback for both ajax-enabled buttons.
   *
   * Selects and returns the fieldset with the names in it.
   */
  public function addmoreCallback(array &$form, FormStateInterface $form_state) {
    $form_state->get('denoms');
    return $form['denominations'];
  }

  /**
   * Submit handler for the "add-one-more" button.
   *
   * Increments the max counter and causes a rebuild.
   */
  public function addOne(array &$form, FormStateInterface $form_state) {
    $name_field = $form_state->get('denoms');
    $add_button = $name_field + 1;
    $form_state->set('denoms', $add_button);
    $form_state->setRebuild();
  }

  /**
   * Submit handler for the "remove one" button.
   *
   * Decrements the max counter and causes a form rebuild.
   */
  public function removeCallback(array &$form, FormStateInterface $form_state) {
    $name_field = $form_state->get('denoms');
    if ($name_field > 1) {
      $remove_button = $name_field - 1;
      $form_state->set('denoms', $remove_button);
    }
    $form_state->setRebuild();
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $form_state->setValue('currencyCode', strtoupper($form_state->getValue('currencyCode')));
    if (!preg_match("/^[A-Z]{3}$/", $form_state->getValue('currencyCode'))) {
      $form_state->setErrorByName('currencyCode', $this->t('The currency code must consist of three uppercase letters.'));
    }
  }


  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $currency_denominations = $this->entity;
    $currency_denominations->save();
    drupal_set_message($this->t('Saved the %label currency denominations.', [
      '%label' => $currency_denominations->label(),
    ]));
    $form_state->setRedirect('entity.commerce_pos_currency_denoms.collection');
  }

}
