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

/**
 * Callback for the POS location selection form.
 */
function commerce_pos_location_select($form, &$form_state) {
  $current_location = commerce_pos_location_get_current_location();

  $form['location'] = array(
    '#type' => 'select',
    '#title' => t('Please select your location:'),
    '#options' => commerce_pos_location_options(),
    '#default_value' => $current_location ? $current_location : NULL,
    '#required' => TRUE,
  );

  $form['actions'] = array(
    '#type' => 'actions',
  );

  $form['actions']['summit'] = array(
    '#type' => 'submit',
    '#value' => t('Save'),
  );

  return $form;
}

/**
 * Submit handler for the location selection form.
 */
function commerce_pos_location_select_submit($form, &$form_state) {
  commerce_pos_location_set_current_location($form_state['values']['location']);
  $form_state['redirect'] = 'pos';
}
