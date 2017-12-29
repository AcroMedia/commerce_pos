<?php

namespace Drupal\commerce_pos_label\Form;

use Drupal\commerce_pos\UPC;
use Drupal\commerce_pos_label\Ajax\PrintLabelsCommand;
use Drupal\commerce_price\NumberFormatterFactoryInterface;
use Drupal\commerce_price\RounderInterface;
use Drupal\commerce_product\Entity\ProductVariationInterface;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\RendererInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class PrintLabelsForm.
 *
 * Builds a list of products to print labels for.
 */
class PrintLabelsForm extends FormBase {

  /**
   * Storage for commerce_product_variation entities.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $productVariationStorage;

  /**
   * Storage for currency entities.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $currencyStorage;

  /**
   * The rendering service.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * A rounding services for prices.
   *
   * @var \Drupal\commerce_price\RounderInterface
   */
  protected $rounder;

  /**
   * A number formatter for currencies.
   *
   * @var \CommerceGuys\Intl\Formatter\NumberFormatterInterface
   */
  protected $formatter;

  /**
   * A service for looking up product UPCs.
   *
   * @var \Drupal\commerce_pos\UPC
   */
  protected $upc;

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'commerce_pos_label_print_form';
  }

  /**
   * PrintLabelsForm constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager.
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The rendering service.
   * @param \Drupal\commerce_price\RounderInterface $rounder
   *   A rounding services for prices.
   * @param \Drupal\commerce_price\NumberFormatterFactoryInterface $formatterFactory
   *   A factory for creating price formatters.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   *   If unable to get the storage for product variations.
   */
  public function __construct(EntityTypeManagerInterface $entityTypeManager, RendererInterface $renderer, RounderInterface $rounder, NumberFormatterFactoryInterface $formatterFactory, UPC $upc) {
    $this->productVariationStorage = $entityTypeManager->getStorage('commerce_product_variation');
    $this->currencyStorage = $entityTypeManager->getStorage('commerce_currency');
    $this->renderer = $renderer;
    $this->rounder = $rounder;
    $this->formatter = $formatterFactory->createInstance();
    $this->upc = $upc;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('renderer'),
      $container->get('commerce_price.rounder'),
      $container->get('commerce_price.number_formatter_factory'),
      $container->get('commerce_pos.upc')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, ProductVariationInterface $commerce_product_variation = NULL) {
    $product_variation = $commerce_product_variation;

    $form['#attached']['library'][] = 'commerce_pos_label/label';
    $form['#attached']['library'][] = 'commerce_pos/jQuery.print';

    $labels_to_create = $this->getLabelList($form_state);
    if ($product_variation && !$labels_to_create) {
      $labels_to_create[$product_variation->id()] = $this->buildInfoArray($product_variation);
      $form_state->setValue('label_list', $labels_to_create);
    }

    $format_options = array_map(function ($format) {
      return $format['title'];
    }, commerce_pos_label_get_label_formats());

    // We need at least 1 label format to proceed.
    if (empty($format_options)) {
      drupal_set_message($this->t('There are no available label formats. Please enable at least one POS label format module.'), 'error');
      return $form;
    }

    $form_wrapper_id = 'commerce-pos-label-form-container';

    $form['#attributes'] = [
      'id' => $form_wrapper_id,
      'class' => ['commerce-pos-form-container'],
    ];

    $form['label_format'] = [
      '#type' => 'select',
      '#title' => $this->t('Label format'),
      '#options' => $format_options,
      '#required' => TRUE,
      '#default_value' => key($format_options),
    ];

    $form['product_search'] = [
      '#type' => 'entity_autocomplete',
      '#target_type' => 'commerce_product_variation',
      '#title' => t('Product Search'),
      '#title_display' => 'invisible',
      '#size' => 60,
      '#description' => $this->t('Search by product title.'),
      '#attributes' => [
        'class' => [
          'commerce-pos-product-autocomplete',
          'commerce-pos-product-search',
        ],
        'placeholder' => $this->t('Product Search'),
      ],
    ];

    $form['product_search_add'] = [
      '#type' => 'submit',
      '#value' => $this->t('Add'),
      '#validate' => ['::productAddValidate'],
      '#submit' => ['::productAddSubmit'],
      '#attributes' => [
        'class' => [
          'commerce-pos-label-btn-add',
          'commerce-pos-btn',
          'fixed-width',
          'btn-success',
        ],
      ],
    ];

    $form['label_options'] = [
      '#type' => 'container',
    ];

    $form['label_options']['label_list'] = [
      '#type' => 'table',
      '#header' => [
        $this->t('Quantity'),
        $this->t('Title'),
        $this->t('Description'),
        $this->t('Price'),
        $this->t('Remove'),
      ],
      '#tree' => TRUE,
    ];

    foreach ($labels_to_create as $product_variation_id => $label_info) {

      $form['label_options']['label_list'][$product_variation_id]['quantity'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Quantity'),
        '#value' => $label_info['quantity'],
        '#required' => TRUE,
        '#size' => 5,
      ];

      $form['label_options']['label_list'][$product_variation_id]['title'] = [
        '#title' => $this->t('Title'),
        '#type' => 'textfield',
        '#required' => TRUE,
        '#value' => $label_info['title'],
      ];

      $form['label_options']['label_list'][$product_variation_id]['description'] = [
        '#title' => $this->t('Description'),
        '#type' => 'textfield',
        '#value' => $label_info['description'],
      ];

      $form['label_options']['label_list'][$product_variation_id]['price'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Price'),
        '#value' => $label_info['price'],
        '#required' => TRUE,
        '#size' => 5,
      ];

      $form['label_options']['label_list'][$product_variation_id]['remove'] = [
        '#type' => 'submit',
        '#value' => $this->t('Remove'),
        '#name' => 'remove-' . $product_variation_id,
        '#validate' => ['::productRemoveValidate'],
        '#submit' => ['::productRemoveSubmit'],
        '#product_id' => $product_variation_id,
        '#attributes' => [
          'class' => [
            'commerce-pos-btn',
            'fixed-width',
            'btn-danger',
          ],
        ],
      ];
    }

    if ($labels_to_create) {
      $form['label_options']['print_labels'] = [
        '#type' => 'button',
        '#value' => $this->t('Print'),
        '#ajax' => [
          'callback' => '::printLabels',
        ],
        '#attributes' => [
          'class' => [
            'commerce-pos-btn',
            'fixed-width',
          ],
        ],
      ];
    }

    return $form;
  }

  /**
   * Generate barcode from product variation title.
   */
  public function printLabels($form, FormStateInterface $form_state) {
    $labels = [
      '#theme' => 'commerce_pos_labels',
      '#labels' => [],
    ];
    $label_values = $this->getLabelList($form_state);
    $label_format = $form_state->getValue('label_format');

    // Convert form values into a render array to theme the labels.
    foreach ($label_values as $product_id => $label) {
      unset($label['remove']);
      foreach ($label as $key => $value) {
        $label['#' . $key] = $value;
        unset($label[$key]);
      }

      $label['#product_id'] = $product_id;
      $label['#theme'] = 'commerce_pos_label';
      $label['#format'] = $label_format;
      $label['#barcode'] = $this->upc->get($product_id);

      for ($i = 0; $i < $label['#quantity']; $i++) {
        $labels['#labels'][] = $label;
      }
    }

    $labels = $this->renderer->render($labels);

    $response = new AjaxResponse();
    $response->addCommand(new PrintLabelsCommand($labels, $label_format));

    return $response;
  }

  /**
   * Validation for adding a product.
   */
  public function productAddValidate(array &$form, FormStateInterface $form_state) {
    $product_id = $form_state->getValue('product_search');

    if (empty($product_id) || !$this->productVariationStorage->load($product_id)) {
      $form_state->setError($form['product_search'], $this->t('Invalid product.'));
    }
  }

  /**
   * Submit handler for adding a product.
   */
  public function productAddSubmit(array &$form, FormStateInterface $form_state) {
    $product_id = $form_state->getValue('product_search');

    /** @var \Drupal\commerce_product\Entity\ProductVariationInterface $product */
    $product = $this->productVariationStorage->load($product_id);
    $labels = $this->getLabelList($form_state);
    if (empty($labels[$product_id])) {
      $labels[$product_id] = $this->buildInfoArray($product);
      $form_state->setValue('label_list', $labels);
    }
    $form_state->setRebuild(TRUE);
  }

  /**
   * Validation for adding a product.
   */
  public function productRemoveValidate(array &$form, FormStateInterface $form_state) {
    $trigger = $form_state->getTriggeringElement();
    if (!empty($trigger['#product_id'])) {
      $labels = $this->getLabelList($form_state);
      if (!isset($labels[$trigger['#product_id']])) {
        $form_state->setError($form['label_list'], $this->t('Can not remove a product that is was added.'));
      }
    }
  }

  /**
   * Submit handler for removing a product.
   */
  public function productRemoveSubmit(array &$form, FormStateInterface $form_state) {
    $trigger = $form_state->getTriggeringElement();
    if (!empty($trigger['#product_id'])) {
      $labels = $this->getLabelList($form_state);
      unset($labels[$trigger['#product_id']]);
      $form_state->setValue('label_list', $labels);
    }
    $form_state->setRebuild(TRUE);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $form_state->setRebuild(TRUE);
  }

  /**
   * Build an array of product info used for printing labels.
   *
   * @param \Drupal\commerce_product\Entity\ProductVariationInterface $product_variation
   *   The product variation to create the array for.
   *
   * @return array
   *   The info array.
   */
  protected function buildInfoArray(ProductVariationInterface $product_variation) {
    $price = $product_variation->getPrice();
    /** @var \Drupal\commerce_price\Entity\Currency $currency */
    $currency = $this->currencyStorage->load($price->getCurrencyCode());

    return [
      'title' => $product_variation->getSku(),
      'quantity' => 1,
      'price' => $this->formatter->formatCurrency($this->rounder->round($price)->getNumber(), $currency),
      'description' => $product_variation->getTitle(),
    ];
  }

  /**
   * Get the label list value.
   *
   * Ensures that when label_list is empty we get an empty array instead of an
   * empty string.
   *
   * @return array
   *   An array of form elements for the label list.
   */
  protected function getLabelList(FormStateInterface $form_state) {
    return $form_state->getValue('label_list') ?: [];
  }

}
