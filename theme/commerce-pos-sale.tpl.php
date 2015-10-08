<?php

/**
 * @file
 * Default template for the POS "Sale" form.
 *
 * Available variables:
 *   - $form: The form API array.
 */

dpm($form, '$form');
?>

<?php print render($form['header']); ?>
<?php print render($form['transaction_id']); ?>

<div class="commerce-pos-sale-container clearfix">
  <div class="commerce-pos-col-products">
    <div class="commerce-pos-product-search-container">
      <?php print render($form['product_search']); ?>
    </div>

    <div class="commerce-pos-transaction-line-items">
      <?php print render($form['line_items']); ?>
    </div>

    <div class="commerce-pos-transaction-messages">
      <?php print render($form['message']); ?>
    </div>

    <?php hide($form['transaction_options']); ?>
    <?php print drupal_render_children($form); ?>
  </div>

  <div class="commerce-pos-col-transaction-info">
    <?php print render($form['transaction_options']); ?>
  </div>
</div>


