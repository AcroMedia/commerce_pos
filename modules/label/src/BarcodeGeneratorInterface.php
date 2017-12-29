<?php

namespace Drupal\commerce_pos_label;

/**
 * Defines the interface for a barcode generator.
 *
 * @package Drupal\commerce_pos_label
 */
interface BarcodeGeneratorInterface {

  /**
   * Render a UPC into a PNG barcode.
   *
   * Override this method if you want to setup your own barcode generation
   *
   * @param string $upc
   *   A UPC.
   * @param int $widthFactor
   *   Width of a single bar element in pixels.
   * @param int $totalHeight
   *   Height of a single bar element in pixels.
   *
   * @return string
   *   The barcode as raw PNG data.
   */
  public function generate($upc, $widthFactor, $totalHeight);

}
