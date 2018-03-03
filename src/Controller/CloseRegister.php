<?php

namespace Drupal\commerce_pos\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Url;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * This exists primarily as a placeholder for close functionality.
 *
 * If you don't have reports install. It is assumed most users will use the EOD
 * report.
 *
 * @package Drupal\commerce_pos\Controller
 */
class CloseRegister extends ControllerBase {

  /**
   * Builds a passthrough page that will redirect based on what is available.
   *
   * If the reports module is enabled, the user will be directed there to close
   * their till and fill out the EOD report. If the report module is not
   * installed, the register will be closed and the usr redirected back to the
   * main POS page.
   */
  public function content() {
    if ($this->moduleHandler()->moduleExists('commerce_pos_reports')) {
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

    return new RedirectResponse($redirect_url->toString());
  }

}
