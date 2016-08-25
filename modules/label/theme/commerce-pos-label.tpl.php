<?php

/**
 * @file
 * Default template POS labels.
 */
?>
<div class="<?php print $classes; ?>" <?php print $attributes; ?>>
  <div class="title">
    <?php print $title; ?>
  </div>
  <?php if ($description) { ?>
  <div class="description">
    <?php print $description; ?>
  </div>
  <?php } ?>
  <?php if ($barcode) { ?>
    <div class="barcode">
      <img src="data:image/png;base64, <?php print $barcode; ?>">
    </div>
  <?php } ?>
  <div class="price">
    <?php print $price; ?>
  </div>
</div>
