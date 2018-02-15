<?php

namespace Drupal\commerce_pos\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\user\PrivateTempStoreFactory;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Component\Utility\Html;
use Drupal\Component\Utility\Tags;

/**
 * Class PosCustomerAutoComplete.
 *
 * @package Drupal\commerce_pos\Controller
 */
class PosCustomerAutoComplete extends ControllerBase {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityDisplayRepositoryInterface
   */
  protected $entityDisplayRepository;

  /**
   * The renderer.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * Constructs a new PosCustomerAutoComplete object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The rendering service.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, RendererInterface $renderer) {
    $this->entityTypeManager = $entity_type_manager;
    $this->renderer = $renderer;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('renderer')
    );
  }

  /**
   * Customer auto complete handler.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request object.
   * @param int $count
   *   The number of entities to list.
   *
   * @return object
   *   The Json object for auto complete suggestions.
   */
  public function customerAutoCompleteHandler(Request $request, $count) {
    $results = [];
    if ($input = $request->query->get('q')) {
      $suggestions = $this->searchQueryString($input, $count);

      // Loop through the entities and convert them into autocomplete output.
      foreach ($suggestions as $entity_id => $label) {
        $key = "$label ($entity_id)";
        // Strip things like starting/trailing white spaces, line breaks and
        // tags.
        $key = preg_replace('/\s\s+/', ' ', str_replace("\n", '', trim(Html::decodeEntities(strip_tags($key)))));
        // Names containing commas or quotes must be wrapped in quotes.
        $key = Tags::encode($key);
        $results[] = ['value' => $key, 'label' => $label];
      }
    }

    return new JsonResponse($results);
  }

  /**
   * Helper function for searching users.
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
    $query = \Drupal::database();
    $query = $query->select('users_field_data', 'u')
      ->fields('u', ['uid', 'name'])
      ->orderBy('uid', 'DESC')
      ->range(0, $count);
    $query->leftJoin('user__field_commerce_pos_phone_number', 'p', 'u.uid = p.entity_id');
    $query->leftJoin('commerce_order', 'o', 'u.uid = o.uid');
    $query->leftJoin('profile__address', 'a', 'o.billing_profile__target_id = a.entity_id');
    $query->condition('u.uid', 0, '!=');
    $query->condition($query->orConditionGroup()
      ->condition('u.name', '%' . $string . '%', 'LIKE')
      ->condition('u.mail', '%' . $string . '%', 'LIKE')
      ->condition('p.field_commerce_pos_phone_number_value', '%' . $string . '%', 'LIKE')
      ->condition('a.address_address_line1', '%' . $string . '%', 'LIKE')
      // Search for just a matching first name.
      ->condition('a.address_given_name', '%' . $string . '%', 'LIKE')
      // Search for just a matching last name.
      ->condition('a.address_family_name', '%' . $string . '%', 'LIKE')
      // Search for a matching full name.
      ->where("CONCAT(a.address_given_name, ' ', a.address_family_name) LIKE :q", [':q' => $string])
    );

    // Execute the query.
    $result = $query->execute()->fetchAllKeyed(0, 1);

    return $result;
  }

}
