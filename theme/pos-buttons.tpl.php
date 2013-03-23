<div class="pos-buttons">
  <?php if(!empty($numbers)) : ?>
    <div class="numbers clearfix">
      <?php foreach ($numbers as $number) : ?>
        <?php print $number; ?>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
  <div class="buttons">
    <?php foreach ($buttons as $button) : ?>
      <?php print $button; ?>
    <?php endforeach; ?>
  </div>
</div>