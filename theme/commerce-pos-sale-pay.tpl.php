<?php

/**
 * @file
 * Template for the POS "Pay" form.
 *
 * Available variables:
 *  $form
 */

dpm($form, 'the form');
?>

<?php hide($form['keypad']) ?>

<?php print drupal_render_children($form); ?>


<div class="commerce-pos-keypad">
  <div class="commerce-pos-keypad-top">
    <div class="commerce-pos-keypad-title"></div>
    <div class="commerce-pos-keypad-close"></div>
    <?php print render($form['keypad']['amount']); ?>
  </div>
  <div class="commerce-pos-keypad-keys">
    <div class="commerce-pos-keypad-numbers">
      <div class="commerce-pos-keypad-key">1</div>
      <div class="commerce-pos-keypad-key">2</div>
      <div class="commerce-pos-keypad-key">3</div>
      <div class="commerce-pos-keypad-key">4</div>
      <div class="commerce-pos-keypad-key">5</div>
      <div class="commerce-pos-keypad-key">6</div>
      <div class="commerce-pos-keypad-key">7</div>
      <div class="commerce-pos-keypad-key">8</div>
      <div class="commerce-pos-keypad-key">9</div>
      <div class="commerce-pos-keypad-key">0</div>
      <div class="commerce-pos-keypad-key">.</div>
    </div>

    <div class="commerce-pos-keypad-actions">
      <div class="commerce-pos-keypad-key">&lt;</div>
      <?php print render($form['keypad']['add']); ?>
    </div>
  </div>
</div>


