<?php

namespace Drupal\commerce_pos_label\Ajax;

use Drupal\Core\Url;
use Drupal\Core\Ajax\CommandInterface;
use Drupal\Component\Render\MarkupInterface;

/**
 * AJAX command for retrieving data and printing labels.
 */
class PrintLabelsCommand implements CommandInterface {

  /**
   * Rendered labels to print.
   *
   * @var \Drupal\Component\Render\MarkupInterface
   */
  protected $labels;

  /**
   * The path to the CSS file for the label format.
   *
   * @var string
   */
  protected $cssUrl;

  /**
   * PrintLabelsCommand constructor.
   *
   * @param \Drupal\Component\Render\MarkupInterface $labels
   *   Rendered labels to print.
   * @param string $format
   *   The label format to print.
   */
  public function __construct(MarkupInterface $labels, $format) {
    $this->labels = $labels;

    $format = commerce_pos_label_format_load($format);
    $css = $format['css'];
    if ($css) {
      $provider_path = \Drupal::service('module_handler')->getModule($format['provider'])->getPath();
      $css = Url::fromUri('base:' . $provider_path . '/' . $css, ['absolute' => TRUE])->toString();
    }
    $this->cssUrl = $css;
  }

  /**
   * {@inheritdoc}
   */
  public function render() {

    return [
      'command' => 'printLabels',
      'content' => $this->labels,
      'cssUrl' => $this->cssUrl,
    ];
  }

}
