<?php

class CommercePosCashierEntityController extends EntityAPIController implements EntityAPIControllerInterface {

  public function create(array $values = array()) {
    $values += array(
      'cashier_id' => NULL,
      'name' => '',
      'is_new' => TRUE,
      'created' => '',
      'changed' => '',
      'code' => '',
    );

    return parent::create($values);
  }

  public function save($cashier, DatabaseTransaction $transaction = NULL) {
    $cashier->changed = REQUEST_TIME;

    if (empty($cashier->{$this->idKey}) || !empty($cashier->is_new)) {
      // Set the creation timestamp if not set, for new entities.
      if (empty($cashier->created)) {
        $cashier->created = REQUEST_TIME;
      }
    }
    else {
      // Otherwise if the entity is not new but comes from an entity_create()
      // or similar function call that initializes the created timestamp and id
      // value to empty strings, unset them to prevent destroying existing data
      // in those properties on update.
      if ($cashier->created === '') {
        unset($cashier->created);
      }
    }

    return parent::save($cashier, $transaction);
  }
}
