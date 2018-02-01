<?php

namespace Drupal\commerce_pos_reports\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 * Class EndOfDay.
 */
class EndOfDay extends ControllerBase {

  /**
   * Builds the End of Day Report form.
   *
   * @return array
   *   A renderable array containing the End Of Day Report form.
   */
  public function content() {
    return \Drupal::formBuilder()->getForm('\Drupal\commerce_pos_reports\Form\EndOfDayForm');
  }

}
