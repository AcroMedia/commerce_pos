<?php

/**
 * @file
 * commerce_pos_location.pages.inc
 */

/**
 * Callback for displaying a POS location entity.
 */
function commerce_pos_location_view_location($location) {
  return entity_view('commerce_pos_location', array($location));
}
