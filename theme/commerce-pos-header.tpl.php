<?php

/**
 * @file
 * Default template file for the Commerce POS header.
 *
 * $account: The employee account currently signed in.
 */
?>

<div id="commerce-pos-header" class="clearfix">
  <div class="commerce-pos-header-nav-cont clearfix">
    <div class="commerce-pos-header-links">
      <ul class="clearfix">
        <li><?php print l(t('Sale'), 'admin/commerce/pos/sale'); ?></li>
        <li><?php print l(t('Return'), 'admin/commerce/pos/return'); ?></li>
        <?php if (module_exists('commerce_pos_report')): ?>
          <li><?php print l(t('Reports'), 'admin/commerce/pos/end-of-day'); ?></li>
        <?php endif; ?>
      </ul>
    </div>
    <?php if (isset($cashier_form)): ?>
      <div id="commerce-pos-header-cashier-form" class="commerce-pos-header-cashier-form">
        <?php print render($cashier_form); ?>
      </div>
    <?php endif; ?>
  </div>

</div>
