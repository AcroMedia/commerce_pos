<?php

/**
 * @file
 * Default template file for the Commerce POS header.
 *
 * $account: The employee account currently signed in.
 */
?>

<div id="commerce-pos-header">
  <div class="links">
    <ul>
      <li><?php print l(t('Sale'), 'pos/sale'); ?></li>
      <li><?php print l(t('Return'), 'pos/return'); ?></li>
    </ul>
  </div>

  <div class="employee-info">
    <div class="username">
      <?php print t('<em>@username</em> signed in', array('@username' => $account->name)); ?>
    </div>
    <div class="logout"><?php print l(t('sign out'), 'user/logout', array('query' => array('destination' => url('pos')))); ?></div>
  </div>
</div>
