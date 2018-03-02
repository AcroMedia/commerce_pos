<?php

namespace Drupal\commerce_pos\Plugin\Field\FieldWidget;

use Drupal\Component\Utility\Html;
use Drupal\Core\Entity\EntityDisplayRepositoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Field\WidgetInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Validator\ConstraintViolationInterface;
use Drupal\user\Entity\User;
use Drupal\commerce_order\Entity\Order;
use Drupal\Core\Entity\Element\EntityAutocomplete;

/**
 * Plugin implementation of the 'pos_customer_widget' widget.
 *
 * @FieldWidget(
 *   id = "pos_customer_widget",
 *   label = @Translation("Pos customer widget"),
 *   field_types = {
 *     "entity_reference"
 *   },
 * )
 */
class PosCustomerWidget extends WidgetBase implements WidgetInterface, ContainerFactoryPluginInterface {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The entity display repository.
   *
   * @var \Drupal\Core\Entity\EntityDisplayRepositoryInterface
   */
  protected $entityDisplayRepository;

  /**
   * Constructs a EntityReferenceEntityFormatter instance.
   *
   * @param string $plugin_id
   *   The plugin_id for the formatter.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Field\FieldDefinitionInterface $field_definition
   *   The definition of the field to which the formatter is associated.
   * @param array $settings
   *   The formatter settings.
   * @param array $third_party_settings
   *   Any third party settings settings.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Entity\EntityDisplayRepositoryInterface $entity_display_repository
   *   The entity display repository.
   */
  public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, array $third_party_settings, EntityTypeManagerInterface $entity_type_manager, EntityDisplayRepositoryInterface $entity_display_repository) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $third_party_settings);

    $this->entityTypeManager = $entity_type_manager;
    $this->entityDisplayRepository = $entity_display_repository;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $plugin_id,
      $plugin_definition,
      $configuration['field_definition'],
      $configuration['settings'],
      $configuration['third_party_settings'],
      $container->get('entity_type.manager'),
      $container->get('entity_display.repository')
    );
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'size' => 60,
      'placeholder' => 'Search by username, name, or email address',
      'num_results' => 10,
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $elements = [];

    $elements['size'] = [
      '#type' => 'number',
      '#title' => $this->t('Size of textfield'),
      '#default_value' => $this->getSetting('size'),
      '#required' => TRUE,
      '#min' => 1,
    ];
    $elements['placeholder'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Placeholder'),
      '#default_value' => $this->getSetting('placeholder'),
      '#description' => $this->t('Text that will be shown inside the field until a value is entered. This hint is usually a sample value or a brief description of the expected format.'),
    ];
    $elements['num_results'] = [
      '#type' => 'number',
      '#title' => $this->t('Number of search results'),
      '#default_value' => $this->getSetting('num_results'),
      '#required' => TRUE,
      '#min' => 1,
      '#max' => 50,
    ];

    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = [];

    $summary[] = $this->t('Textfield size: @size', ['@size' => $this->getSetting('size')]);
    if (!empty($this->getSetting('placeholder'))) {
      $summary[] = $this->t('Placeholder: @placeholder', ['@placeholder' => $this->getSetting('placeholder')]);
    }
    $summary[] = $this->t('Number of results: @num_results', ['@num_results' => $this->getSetting('num_results')]);

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function extractFormValues(FieldItemListInterface $items, array $form, FormStateInterface $form_state) {

  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    /** @var \Drupal\commerce_order\Entity\Order $order */
    $order = $form_state->getFormObject()->getEntity();

    // Make a wrapper for the entire form.
    // @todo this feels off. There must be a better way.
    if (empty($form_state->wrapper_id)) {
      $wrapper_id = Html::getUniqueId(__CLASS__);
      $form['#prefix'] = '<div id="' . $wrapper_id . '">';
      $form['#suffix'] = '</div>';
    }
    else {
      $wrapper_id = $form_state->wrapper_id;
    }

    if ($form_state->getTriggeringElement()) {
      $this->processFormSubmission($form, $form_state);
    }

    $element['order_customer'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Customer'),
    ];

    // If the customer for the order is already set.
    $customer = $order->getCustomer();
    if ($customer->id() != 0) {
      $element['order_customer']['current_customer'] = [
        '#type' => 'textfield',
        '#default_value' => $customer->getAccountName(),
        '#disabled' => TRUE,
        '#size' => 30,
      ];

      $element['order_customer']['remove_current_user'] = [
        '#type' => 'button',
        '#value' => $this->t('Remove'),
        '#name' => 'remove-current-user',
        '#ajax' => [
          'callback' => [$this, 'ajaxRefresh'],
          'wrapper' => $wrapper_id,
        ],
        '#limit_validation_errors' => [],
      ];
    }
    // Else, if no customer has been set for the order.
    else {
      $selected_customer_type = $form_state->getValue([
        'uid',
        '0',
        'target_id',
        'order_customer',
        'customer_type',
      ], 'existing');

      $element['order_customer']['customer_type'] = [
        '#type' => 'radios',
        '#title' => $this->t('Order for'),
        '#title_display' => 'invisible',
        '#attributes' => [
          'class' => ['container-inline'],
        ],
        '#required' => TRUE,
        '#options' => [
          'existing' => $this->t('Existing customer'),
          'new' => $this->t('New customer'),
        ],
        '#default_value' => $selected_customer_type,
        '#ajax' => [
          'callback' => [$this, 'ajaxRefresh'],
          'wrapper' => $wrapper_id,
        ],
        '#limit_validation_errors' => [],
      ];

      // Add an existing customer.
      if ($selected_customer_type == 'existing') {
        $element['order_customer']['user'] = [
          '#type' => 'textfield',
          '#size' => $this->getSetting('size'),
          '#placeholder' => $this->getSetting('placeholder'),
          '#default_value' => NULL,
          '#autocomplete_route_name' => 'commerce_pos.pos_customer_widget_autocomplete',
          '#autocomplete_route_parameters' => [
            'count' => $this->getSetting('num_results'),
          ],
          '#ajax' => [
            'event' => 'autocompleteclose',
            'callback' => [$this, 'ajaxRefresh'],
            'wrapper' => $wrapper_id,
          ],
        ];
      }
      // Add new customer.
      else {
        $element['order_customer']['user'] = [
          '#type' => 'value',
          '#value' => 0,
        ];
        $element['order_customer']['email'] = [
          '#type' => 'email',
          '#title' => $this->t('Email'),
          '#size' => $this->getSetting('size'),
          '#required' => TRUE,
        ];
        $element['order_customer']['pos_phone_number'] = [
          '#type' => 'tel',
          '#title' => $this->t('Phone'),
          '#size' => $this->getSetting('size'),
        ];
      }

      $element['order_customer']['submit'] = [
        '#type' => 'button',
        '#value' => $this->t('Set Customer'),
        '#name' => 'set-order-customer',
        '#ajax' => [
          'callback' => [$this, 'ajaxRefresh'],
          'wrapper' => $wrapper_id,
        ],
        '#limit_validation_errors' => [['uid']],
      ];
    }

    return ['target_id' => $element];
  }

  /**
   * Submit handler for the POS order customer select form.
   *
   * @param array $form
   *   The parent form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public function processFormSubmission(array $form, FormStateInterface &$form_state) {
    /** @var \Drupal\commerce_order\Entity\Order $order */
    $order = $form_state->getFormObject()->getEntity();
    $triggering_element = $form_state->getTriggeringElement();
    $update_order = FALSE;

    // If the user clicked to remove the current user, do so and set the order
    // uid to anonymous.
    if (isset($triggering_element['#name'])) {
      if ($triggering_element['#name'] == 'remove-current-user') {
        $values['user'] = 0;
        $update_order = TRUE;
      }
      // Else, if they clicked to set the order customer.
      elseif ($triggering_element['#name'] == 'set-order-customer') {
        $values = $form_state->getValues();

        // If the user selected an existing user.
        if ($values['uid'][0]['target_id']['order_customer']['customer_type'] == 'existing') {
          // Extract the uid from the autocomplete.
          $values['user'] = EntityAutocomplete::extractEntityIdFromAutocompleteInput($values['uid'][0]['target_id']['order_customer']['user']);

          // Check if we have a valid user.
          if ($email = User::load($values['user'])->getEmail()) {
            $values['email'] = $email;
            $update_order = TRUE;
          }
        }
        // Else, if they created a new user.
        elseif ($values['uid'][0]['target_id']['order_customer']['customer_type'] == 'new') {
          if (!$user = user_load_by_mail($values['uid'][0]['target_id']['order_customer']['email'])) {
            $user = User::create([
              'name' => $values['uid'][0]['target_id']['order_customer']['email'],
              'mail' => $values['uid'][0]['target_id']['order_customer']['email'],
              'pass' => user_password(),
              'status' => TRUE,
              'field_commerce_pos_phone_number' => $values['uid'][0]['target_id']['order_customer']['pos_phone_number'],
            ]);
            $user->save();
            $update_order = TRUE;
          }
          $values['user'] = $user->id();
        }
      }
    }

    // Save the customer to the order and the values to the form_state.
    if ($update_order) {
      $form_state->setValues($values);
      $order->setCustomerId($values['user']);
      $order->save();
      $this->order = $order;
    }

    // Rebuild the form.
    $form_state->setRebuild(TRUE);
  }

  /**
   * {@inheritdoc}
   */
  public function errorElement(array $element, ConstraintViolationInterface $error, array $form, FormStateInterface $form_state) {
    return isset($element['target_id']) ? $element['target_id'] : FALSE;
  }

  /**
   * Ajax callback.
   */
  public static function ajaxRefresh(&$form, FormStateInterface $form_state) {
    // Anything on the form might have changed, including the order total based
    // on who the order user is.
    return $form;
  }

}
