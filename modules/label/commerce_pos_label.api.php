<?php

/**
 * @file
 * API documentation for Commerce POS labels.
 */

/**
 * Allows modules to specify their own label formats.
 *
 * Modules should return an associative array of all formats:
 *
 * @return array
 *   An array whose keys are format machine names and whose values identify
 *   properties for the format:
 *
 *   - title: The human-readable name of the format.
 *   - css: (optional) An absolute URL to a CSS to instead when printing labels
 *     in this format. If not provided, the default CSS file will be used.
 *   - barcode: (optional) An associative array of information needed to
 *     generate the label's barcode. This can be omitted if the format doesn't
 *     need a barcode. Elements:
 *     - type: The type of barcode format to generate. This should be one of the
 *       constants defined in the BarcodeGenerator class.
 *     - widthFactor: The width of a single bar element in pixels
 *     - totalHeight: The height of the barcode in pixels.
 *     - color: An array of RGB values for the color of the barcode.
 *   - dimensions: An associative array of dimension information for label.
 *     Elements:
 *     - width: The width of the label in inches.
 *     - height: The height of the label in inches.
 */
function hook_commerce_pos_label_format_info() {
  $formats = array();

  return $formats;
}

/**
 * Allows modules to modify the label format information.
 *
 * @param array $formats
 *   An array of label formats.
 */
function hook_commerce_pos_label_format_info_alter(array &$formats) {
  // No example.
}
