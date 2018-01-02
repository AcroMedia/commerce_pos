<?php

namespace Drupal\commerce_pos_label;

use Picqer\Barcode\BarcodeGenerator as ExternalGenerator;
use Picqer\Barcode\BarcodeGeneratorPNG;
use Picqer\Barcode\Exceptions\BarcodeException;

/**
 * Class BarcodeGenerator.
 *
 * A service for generating barcodes.
 *
 * @package Drupal\commerce_pos_label
 */
class BarcodeGenerator implements BarcodeGeneratorInterface {

  /**
   * Determine the type of a UPC.
   *
   * @param string $upc
   *   A UPC.
   *
   * @return string|false
   *   A UPC type or false if none could be determined from the UPC length.
   */
  public function type($upc) {
    $map = [
      8 => ExternalGenerator::TYPE_EAN_8,
      12 => ExternalGenerator::TYPE_UPC_A,
      13 => ExternalGenerator::TYPE_EAN_13,
    ];

    $length = strlen($upc);

    return (isset($map[$length])) ? $map[$length] : FALSE;
  }

  /**
   * Render a UPC into a PNG barcode.
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
  public function generate($upc, $widthFactor = 2, $totalHeight = 40) {
    $type = $this->type($upc);
    if ($type) {
      $generator = new BarcodeGeneratorPNG();
      try {
        return $generator->getBarcode($upc, $type, $widthFactor, $totalHeight);
      }
      catch (BarcodeException $e) {
        return FALSE;
      }
    }

    return FALSE;
  }

}
