<?php

/**
 * Callbacks specific to the POS login.
 */


/**
 * Form callback for the custom POS login.
 */
function commerce_pos_login($form, &$form_state) {
  // Handle users who are already logged in.
  if (user_is_logged_in()) {
    drupal_goto('pos');
  }

  // Just return the normal login form for now. Will probably mess with this
  // more later
  $form = user_login($form, $form_state);

  // Add module CSS
  $form['#attached']['css'][] = drupal_get_path('module', 'commerce_pos') . '/css/commerce_pos_style.css';

  return $form;
}

/**
 * Submit handler for the custom POS login.
 *
 * Taken from user_login_submit().
 */
function commerce_pos_login_submit($form, &$form_state) {
  global $user;

  // Even though we don't even use $user here, we still have to load it because
  // user_login_finalize() is expecting the $user global to be a fully-loaded
  // object.
  $user = user_load($form_state['uid']);
  $form_state['redirect'] = 'pos';

  user_login_finalize($form_state);
}
