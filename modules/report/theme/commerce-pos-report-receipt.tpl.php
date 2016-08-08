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

  <h2><?php print t('Register: @register', array(
      '@register' => $register,
  )); ?></h2>

  <?php foreach ($rows as $row): ?>
    <h3 class="title"><?php print $row['title']; ?></h3>

    <table>
      <tr>
        <td class="label"><?php print t('Total Transactions:') ?></td>
        <td class="value"><?php print $row['total_transactions']; ?></td>
      </tr>
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
      <tr>
        <td colspan="2">&nbsp;</td>
      </tr>
      <tr>
        <td colspan="2" align="center">== <?php print t('Transaction Summary'); ?> ==</td>
      </tr>
      <tr>
        <td colspan="2">&nbsp;</td>
      </tr>
      <?php foreach ($row['transaction_summary'] as $transaction) { ?>
        <tr>
          <td class="label">
            <?php print t('Order ID:') ?>
          </td>
          <td class="value">
            <?php print $transaction['order_id']; ?>
          </td>
        </tr>
        <tr>
          <td class="label"><?php print t('Status:'); ?></td>
          <td class="value"><?php print $transaction['status']; ?></td>
        </tr>
        <tr>
          <td class="label">
            <?php print t('Time:') ?>
          </td>
          <td class="value">
            <?php print format_date($transaction['completed'], 'custom', 'g:i:s a'); ?>
          </td>
        </tr>
        <tr>
          <td class="label">
            <?php print t('Cashier:') ?>
          </td>
          <td class="value">
            <?php print $transaction['cashier']; ?>
          </td>
        </tr>
        <tr>
          <td class="label">
            <?php print t('Amount:') ?>
          </td>
          <td class="value">
            <?php print $transaction['amount'] ?>
          </td>
        </tr>
        <tr>
          <td colspan="2" align="center">=============================</td>
        </tr>
      <?php } ?>
    </table>

  <?php endforeach; ?>
</div>
