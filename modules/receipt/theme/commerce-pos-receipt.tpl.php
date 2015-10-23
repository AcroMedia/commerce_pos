<?php

/**
 * @file
 * Default template for a POS receipt.
 *
 * Available variables:
 *   - $receipt_header
 *   - $receipt_body
 *   - $receipt_footer
 */

?>
<div class="pos-receipt">
  <div class="receipt-header"><?php print render($receipt_header); ?></div>
  <div class="receipt-body"><?php print render($receipt_body); ?></div>
  <div class="receipt-footer"><?php print render($receipt_footer); ?></div>
</div>
