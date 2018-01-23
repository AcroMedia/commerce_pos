<?php

namespace Drupal\commerce_pos\Controller;

use Drupal\Core\Controller\ControllerBase;

class OrderLookup extends ControllerBase {

  /**
   * Builds the Order Lookup form.
   *
   * @return array
   *   A renderable array containing the Order Lookup form.
   */
  public function content() {
    return \Drupal::formBuilder()->getForm('\Drupal\commerce_pos\Form\OrderLookupForm');
  }

}
