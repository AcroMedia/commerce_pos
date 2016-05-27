<?php

/**
 * Page and form callbacks for the "frontend" side of commerce_pos.
 *
 * In this scenario, frontend simply means the actual POS terminal. Whereas
 * admin pages for the POS are considered backend.
 */


/**
 * Page callback for the main POS page.
 *
 * This simply decides what the user has access too and redirects them
 * appropriately.
 */
function commerce_pos_redirect() {
  if (user_access('process commerce pos sales')) {
    drupal_goto('admin/commerce/pos/sale');
  }
  elseif (user_access('process commerce pos returns')) {
    drupal_goto('admin/commerce/pos/return');
  }
  else {
    // Not sure how they would have gotten to this, but just in case...
    drupal_access_denied();
    drupal_exit();
  }
}
