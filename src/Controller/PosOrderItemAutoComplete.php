<?php

namespace Drupal\commerce_pos\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\TempStore\PrivateTempStoreFactory;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\commerce_product\Entity\ProductVariation;
use Drupal\search_api\Entity\Index;

/**
 * Class PosOrderItemAutoComplete.
 *
 * @package Drupal\commerce_pos\Controller
 */
class PosOrderItemAutoComplete extends ControllerBase {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityDisplayRepositoryInterface
   */
  protected $entityDisplayRepository;

  /**
   * The tempstore object.
   *
   * @var \Drupal\user\SharedTempStore
   */
  protected $tempStore;

  /**
   * The renderer.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * Constructs a new POS object.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, PrivateTempStoreFactory $temp_store_factory, RendererInterface $renderer) {
    $this->entityTypeManager = $entity_type_manager;
    $this->tempStore = $temp_store_factory->get('commerce_pos');
    $this->renderer = $renderer;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('user.private_tempstore'),
      $container->get('renderer')
    );
  }

  /**
   * Order item auto complete handler.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request object.
   * @param string $entity_type
   *   The entity type.
   * @param string $view_mode
   *   The view mode.
   * @param int $count
   *   The number of entities to list.
   *
   * @return object
   *   The Json object for auto complete suggestions.
   */
  public function orderItemAutoCompleteHandler(Request $request, $entity_type, $view_mode, $count) {

    $results = [];
    if ($input = $request->query->get('q')) {
      $suggestions = $this->searchQueryString($input, $count);
      $view_builder = $this->entityTypeManager->getViewBuilder($entity_type);
      foreach ($suggestions->getResultItems() as $item) {
        $data = explode(':', $item->getId());
        $data = explode('/', $data[1]);
        $product_variation = ProductVariation::load($data[1]);
        $product_render_array = $view_builder->view($product_variation, $view_mode, $product_variation->language()->getId());
        $results[] = [
          'value' => $product_variation->id(),
          'label' => '<div class="commerce-pos-autocomplete-results">' . $this->renderer->renderPlain($product_render_array) . '</div>',
        ];
      }
    }

    return new JsonResponse($results);
  }

  /**
   * Helper function for searching the product.
   *
   * @param string $string
   *   The Query string.
   * @param int $count
   *   The count of items to return.
   *
   * @return mixed
   *   The query search result.
   */
  public function searchQueryString($string, $count) {
    $register = \Drupal::service('commerce_pos.current_register')->get();
    if ($register) {
      $store_id = $register->getStoreId();

      $config = $this->config('commerce_pos.settings');

      $index = Index::load($config->get('product_search_index'));

      $query = $index->query();

      $parse_mode = \Drupal::service('plugin.manager.search_api.parse_mode')
        ->createInstance('direct');
      $parse_mode->setConjunction('OR');
      $query->setParseMode($parse_mode);

      // Set fulltext search keywords and fields.
      $query->keys($string);

      $query->addCondition('stores', $store_id);
      $query->range(0, $count);

      $results = $query->execute();

      return $results;
    }

    return NULL;
  }

}
