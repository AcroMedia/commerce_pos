<?php

namespace Drupal\commerce_pos_label\Plugin\LabelFormat;

/**
 * Defines the interface for label formats.
 */
interface LabelFormatInterface {

  /**
   * Get the label format's ID.
   *
   * @return string
   *   The label format ID.
   */
  public function getId();

  /**
   * Get the label formats' title.
   *
   * @return string
   *   The label format title.
   */
  public function getTitle();

  /**
   * Get the path to the label format's css file.
   *
   * The path must be relative to the module that provides the format.
   *
   * @return string|false
   *   The label format css or FALSE if none.
   */
  public function getCss();

  /**
   * Get the label format's dimensions: width and height.
   *
   * @return array
   *   An array containing the width and height.
   */
  public function getDimensions();

}
