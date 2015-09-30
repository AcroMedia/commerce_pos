<?php

/**
 * @file
 * Admin-related callbacks and functions for commerce_pos.
 */

/**
 * Callback for the POS settings form.
 */
function commerce_pos_settings($form, &$form_state) {

  $form['commerce_pos_default_country'] = array(
    '#type'
  );

  return system_settings_form($form);
}
