<?php

namespace Drupal\commerce_pos_reports\Ajax;

use Drupal\Core\Ajax\CommandInterface;

/**
 * AJAX command for printing the End Of Day Report.
 */
class PrintEodReport implements CommandInterface {

  /**
   * The selected date.
   *
   * @var string
   */
  protected $date;

  /**
   * The selected register.
   *
   * @var string
   */
  protected $register;

  /**
   * {@inheritdoc}
   */
  public function __construct($date, $register) {
    $this->date = $date;
    $this->register = $register;
  }

  /**
   * {@inheritdoc}
   */
  public function render() {
    $content = [
      '#theme' => 'commerce_pos_reports_receipt',
      '#date' => $this->date,
      '#register' => $this->register,
    ];
    /** @var \Drupal\Core\Render\RendererInterface $renderer */
    $renderer = \Drupal::service('renderer');
    return [
      'command' => 'printWindow',
      'content' => $renderer->render($content),
    ];
  }

}
