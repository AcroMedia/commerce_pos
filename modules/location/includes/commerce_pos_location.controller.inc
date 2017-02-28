<?php

/**
 * @file
 */

/**
 *
 */
class CommercePosLocationEntityController extends EntityAPIController implements EntityAPIControllerInterface {

  /**
   * Create a default location.
   */
  public function create(array $values = array()) {
    $values += array(
      'location_id' => NULL,
      'name' => '',
      'is_new' => TRUE,
      'created' => '',
      'changed' => '',
    );

    return parent::create($values);
  }

  /**
   * Saves a location.
   */
  public function save($location, DatabaseTransaction $transaction = NULL) {
    $location->changed = REQUEST_TIME;

    if (empty($location->{$this->idKey}) || !empty($location->is_new)) {
      // Set the creation timestamp if not set, for new entities.
      if (empty($location->created)) {
        $location->created = REQUEST_TIME;
      }
    }
    else {
      // Otherwise if the product is not new but comes from an entity_create()
      // or similar function call that initializes the created timestamp and uid
      // value to empty strings, unset them to prevent destroying existing data
      // in those properties on update.
      if ($location->created === '') {
        unset($location->created);
      }
    }

    return parent::save($location, $transaction);
  }

}
