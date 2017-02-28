<?php

/**
 * @file
 * Default template for the POS "Pay" form.
 *
 * Available variables:
 *  $form
 */
?>

<?php print render($form['header']); ?>

<?php if (isset($form['no_transactions'])) { ?>
  <div class="no-transaction">
    <?php print render($form['no_transactions']); ?>
  </div>
<?php } ?>

<div class="commerce-pos-pay-container clearfix">
  <div class="commerce-pos-pay-col-payments">
    <?php print render($form['summary']); ?>
    <?php print render($form['summary_table']); ?>
    <?php print render($form['edit_order']); ?>
    <div class="edit-payment-options-wrapper">
      <?php print render($form['payment_options']); ?>
    </div>
    <?php print render($form['keypad']); ?>
  </div>
  <div class="commerce-pos-col-transaction-info">
    <?php print render($form['balance']); ?>
  </div>
</div>

<?php print render($form['parked_transactions']); ?>
<?php print drupal_render_children($form); ?>
