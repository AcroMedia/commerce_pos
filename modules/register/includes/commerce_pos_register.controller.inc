<?php

/**
 * @file
 * Controller file for the CommercePOSRegister Entity.
 */

/**
 * Controller for the CommercePOSRegister Entity.
 */
class CommercePosRegisterEntityController extends EntityAPIController implements EntityAPIControllerInterface {

  /**
   * Create a default register.
   */
  public function create(array $values = array()) {
    $values += array(
      'register_id' => NULL,
      'name' => '',
      'is_new' => TRUE,
      'created' => '',
      'changed' => '',
    );

    return parent::create($values);
  }

  /**
   * Saves a register.
   */
  public function save($register, DatabaseTransaction $transaction = NULL) {
    $register->changed = REQUEST_TIME;

    if (empty($register->{$this->idKey}) || !empty($register->is_new)) {
      // Set the creation timestamp if not set, for new entities.
      if (empty($register->created)) {
        $register->created = REQUEST_TIME;
      }
    }
    else {
      // Otherwise if the product is not new but comes from an entity_create()
      // or similar function call that initializes the created timestamp and uid
      // value to empty strings, unset them to prevent destroying existing data
      // in those properties on update.
      if ($register->created === '') {
        unset($register->created);
      }
    }

    return parent::save($register, $transaction);
  }

}
