<?php

/**
 * @file
 * Default template file for the Commerce POS header.
 *
 * $account: The employee account currently signed in.
 */
?>

<div id="commerce-pos-header" class="clearfix">
  <div class="commerce-pos-header-employee-info">
    <ul class="clearfix">
      <li class="username">
        <?php print t('<em>@username</em> signed in', array('@username' => $account->name)); ?>
      </li>
      <li class="logout">
        <?php print l(t('sign out'), 'user/logout', array('query' => array('destination' => url('pos')))); ?>
      </li>
    </ul>
  </div>

  <div class="commerce-pos-header-nav-cont clearfix">
    <div id="pos-logo">
      <a href="<?php print base_path(); ?>pos"><img src="<?php print base_path() . drupal_get_path('theme', 'thevault'); ?>/gfx/logo_the_vault_pro_scooters.png" alt="" /></a>
    </div>

    <div class="commerce-pos-header-links">
      <ul class="clearfix">
        <li><?php print l(t('Sale'), 'pos/sale'); ?></li>
        <li><?php print l(t('Return'), 'pos/return'); ?></li>
        <?php if (module_exists('commerce_pos_report')): ?>
          <li><?php print l(t('Reports'), 'pos/end-of-day'); ?></li>
        <?php endif; ?>
      </ul>
    </div>
  </div>

</div>
