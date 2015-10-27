<?php

/**
 * @file
 * Default template file for the Commerce POS header.
 *
 * $account: The employee account currently signed in.
 */
?>

<div id="commerce-pos-header" class="clearfix">
  <div class="commerce-pos-header-links">
    <ul>
      <li><?php print l(t('Sale'), 'pos/sale'); ?></li>
      <li><?php print l(t('Return'), 'pos/return'); ?></li>
      <?php if (module_exists('commerce_pos_report')): ?>
        <li><?php print l(t('Reports'), 'pos/report'); ?></li>
      <?php endif; ?>
    </ul>
  </div>

  <div class="commerce-pos-header-employee-info">
    <span class="username">
      <?php print t('<em>@username</em> signed in', array('@username' => $account->name)); ?>
    </span>
    <span class="logout">
      <?php print l(t('sign out'), 'user/logout', array('query' => array('destination' => url('pos')))); ?>
    </span>
  </div>
</div>
