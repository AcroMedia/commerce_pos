<?php

/**
 * @file
 * Default template for the POS "Pay" form.
 *
 * Available variables:
 *  $form
 */

dpm($form, '$form');
?>


<div class="commerce-pos-pay-container clearfix">
  <div class="commerce-pos-pay-col-payments">
    <?php print render($form['summary']); ?>
    <?php print render($form['edit_order']); ?>
    <?php print render($form['payment_options']); ?>
    <?php print render($form['keypad']); ?>
  </div>
  <div class="commerce-pos-col-transaction-info">
    <?php print drupal_render_children($form); ?>
  </div>
</div>
