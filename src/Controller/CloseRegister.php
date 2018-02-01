<?php

namespace Drupal\commerce_pos\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Url;
use Symfony\Component\HttpFoundation\RedirectResponse;

class CloseRegister extends ControllerBase {

  /**
   * Builds the Order Lookup form.
   *
   * @return array
   *   A renderable array containing the Order Lookup form.
   */
  public function content() {
    $module_handler = \Drupal::service('module_handler');

    if ($module_handler->moduleExists('commerce_pos_reports')) {
      $redirect_url = Url::fromRoute('commerce_pos_reports.end-of-day');
    }
    else {
      $register = \Drupal::service('commerce_pos.current_register')->get();
      $register->close();
      $register->save();

      drupal_set_message($this->t('Register @register has been closed.', [
        '@register' => $register->label(),
      ]));

      $redirect_url = Url::fromRoute('commerce_pos.main');
    }

    $response = new RedirectResponse($redirect_url->toString());
    $response->send();
  }

}
