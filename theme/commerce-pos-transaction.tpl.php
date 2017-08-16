<?php

/**
 * @file
 * Default template for the POS "Sale" form.
 *
 * Available variables:
 *   - $form: The form API array.
 */
?>

<?php print render($form['header']); ?>
<?php print render($form['transaction_id']); ?>
<?php print render($form['transaction_type']); ?>

<?php if(isset($form['products_not_configured'])): ?>
  <?php print render($form['products_not_configured']); ?>
<?php endif; ?>

<div class="commerce-pos-sale-container clearfix">
  <div class="commerce-pos-col-products">
    <div class="commerce-pos-product-search-container">
      <?php if(isset($form['product_search']['onboarding_text'])): ?>
        <?php print render($form['product_search']['onboarding_text']); ?>
      <?php endif; ?>
      <?php if(isset($form['search_type'])): ?>
        <?php print render($form['search_type']); ?>
      <?php endif; ?>
      <?php print render($form['product_search']); ?>
      <?php if(isset($form['order_search'])): ?>
        <?php print render($form['order_search']); ?>
      <?php endif; ?>
    </div>

    <?php if(!empty($form['line_items'])): ?>
        <h2><?php print t('Sale Items'); ?></h2>
        <div class="commerce-pos-transaction-line-items">
          <?php print render($form['line_items']); ?>
        </div>
    <?php endif;?>

    <?php if(!empty($form['return_line_items'])): ?>
        <h2><?php print t('Return Items'); ?></h2>
        <div class="commerce-pos-transaction-line-items">
        <?php print render($form['return_line_items']); ?>
        </div>
    <?php endif;?>

    <div class="commerce-pos-transaction-messages">
      <?php print render($form['message']); ?>
    </div>

    <?php hide($form['transaction_options']); ?>
    <?php hide($form['parked_transactions']); ?>
    <?php print drupal_render_children($form); ?>
  </div>

  <div class="commerce-pos-col-transaction-info">
    <?php print render($form['transaction_options']); ?>
  </div>
</div>

<?php print render($form['parked_transactions']); ?>
