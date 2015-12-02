<?php
/**
 * @file
 * Template for an end of day receipt.
 */
?>
<div class="pos-report-receipt">
  <h1><?php print t('End of Day @date', array(
      '@date' => $date,
  )); ?></h1>

  <h2><?php print t('Location: @location', array(
      '@location' => $location,
  )); ?></h2>

  <?php foreach ($rows as $row): ?>
    <h3 class="title"><?php print $row['title']; ?></h3>

    <table>
      <tr>
        <td class="label"><?php print t('Declared:'); ?></td>
        <td class="value"><?php print $row['declared']; ?></td>
      </tr>
      <tr>
        <td class="label"><?php print t('Expected:'); ?></td>
        <td class="value"><?php print $row['expected']; ?></td>
      </tr>
      <tr>
        <td class="label"><?php print t('Over/Short:'); ?></td>
        <td class="value"><?php print $row['over_short']; ?></td>
      </tr>
      <?php if (isset($row['cash_deposit'])) { ?>
        <tr>
          <td class="label"><?php print t('Cash Deposit:'); ?></td>
          <td class="value"><?php print $row['cash_deposit']; ?></td>
        </tr>
      <?php } ?>
    </table>

  <?php endforeach; ?>
</div>
