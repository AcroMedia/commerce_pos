<?php
/**
 * @file
 * Default template for a Commerce POS Cash Input box.
 *
 * Variables:
 * - $currency_code: The currency code in use.
 * - $inputs: The input elements available.
 *   - An array of arrays, each with a 'title' and 'amount' key.
 */

?>

<div id="commerce-pos-keypad-cash-input-box" style="display: none;" class="commerce-pos-keypad-cash-input-box-wrap">
  <div class="commerce-pos-keypad-cash-input-box-container">
    <div class="commerce-pos-keypad-cash-input-box-content">
      <div class="commerce-pos-keypad-cash-input-box-popup-block">
        <div class="title"><?php print t('Cash Count'); ?></div>

        <table>
          <?php foreach ($inputs as $key => $input) { ?>
            <tr>
              <td><?php print $input['title']; ?></td>
              <td>
                <input class="amount-input" size="5" type="text" data-currency-code="<?php print $currency_code; ?>" data-cash-type="<?php print $key; ?>" data-amount="<?php print $input['amount']; ?>">
              </td>
              <td>
                <input class="amount-output" size="5"  type="text" data-cash-type="<?php print $key; ?>" disabled>
              </td>
            </tr>
          <?php } ?>

          <tr class="row-total">
            <td><?php print t('Total'); ?></td>
            <td colspan="2"><span class="input-total"></span></td>
          </tr>

          <tr>
            <td>
              <?php print l(t('Cancel'), '', array('external' => TRUE, 'fragment' => 'cancel', 'attributes' => array('class' => array('cancel-total')))); ?>
            </td>
            <td colspan="2">
              <?php print l(t('Add'), '', array('external' => TRUE, 'fragment' => 'add', 'attributes' => array('class' => array('add-total')))); ?>
            </td>
          </tr>
        </table>
      </div>
    </div>
  </div>
</div>
