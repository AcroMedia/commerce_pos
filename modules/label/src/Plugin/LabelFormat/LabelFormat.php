<?php

namespace Drupal\commerce_pos_label\Plugin\LabelFormat;

use Drupal\Core\Plugin\PluginBase;

/**
 * Provides the label format class.
 */
class LabelFormat extends PluginBase implements LabelFormatInterface {

  /**
   * {@inheritdoc}
   */
  public function getId() {
    return $this->pluginDefinition['id'];
  }

  /**
   * {@inheritdoc}
   */
  public function getTitle() {
    return $this->pluginDefinition['title'];
  }

  /**
   * {@inheritdoc}
   */
  public function getCss() {
    return $this->pluginDefinition['css'];
  }

  /**
   * {@inheritdoc}
   */
  public function getDimensions() {
    return $this->pluginDefinition['dimensions'];
  }

}
