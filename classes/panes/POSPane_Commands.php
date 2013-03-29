<?php


class POSPane_Commands extends POS_Pane {
  protected $config = array(
    'show_keypad' => FALSE,
  );

  function build(POS $pos, POS_Interface $interface, $js = FALSE) {
    $buttons = array();
    $numbers = array();
    foreach ($interface->getButtons() as $button) {
      if ($button->access($pos, NULL)) {
        $buttons[] = $button->render($pos);
      }
    }
    if ($this->config['show_keypad']) {
      foreach (range(9, 0) as $i) {
        $numbers[] = '<span class="pos-button" data-pos-input="%s' . $i . '" data-pos-submit="false">' . $i . '</span>';
      }
      $numbers[] = '<span class="pos-button" data-pos-input="%s*" data-pos-submit="false">*</span>';
    }

    return array(
      '#theme' => 'pos_buttons',
      '#buttons' => $buttons,
      '#numbers' => $numbers,
    );
  }
}