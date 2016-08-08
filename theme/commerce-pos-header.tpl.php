<?php

/**
 * @file
 * Default template file for the Commerce POS header.
 *
 * $account: The employee account currently signed in.
 * $links: The links to be output in the header.
 */
?>

<div id="commerce-pos-header" class="clearfix">
  <div class="commerce-pos-header-nav-cont clearfix">
    <div class="commerce-pos-header-links">
      <ul class="clearfix">
        <?php foreach ($links as $link) { ?>
          <li><?php print $link; ?></li>
        <?php } ?>
      </ul>
    </div>
    <?php if (isset($cashier_form)): ?>
      <div id="commerce-pos-header-cashier-form" class="commerce-pos-header-cashier-form">
        <?php print render($cashier_form); ?>
      </div>
    <?php endif; ?>
  </div>
</div>
