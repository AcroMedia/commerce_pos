<?php


class POSPane_Commands extends POS_Pane {
  protected $config = array(
    'show_keypad' => FALSE,
  );

  function build(CommercePOS $pos, POS_Interface $interface, $js = FALSE) {
    $buttons = array();
    $numbers = array();
    foreach ($interface->getButtons() as $button) {
      if ($button->access($pos, NULL)) {
        $buttons[] = $button->render($pos);
      }
    }
    if ($this->config['show_keypad']) {
      foreach (range(9, 0) as $i) {
        $numbers[] = l($i, 'admin/commerce/pos', array(
          'attributes' => array(
            'data-pos-submit' => 'false',
            'data-pos-input' => '%s' . $i,
            'class' => array('pos-button'),
          )
        ));
      }
      $numbers[] = l('*', 'admin/commerce/pos', array(
        'attributes' => array(
          'data-pos-submit' => 'false',
          'data-pos-input' => '%s*',
          'class' => array('pos-button'),
        )
      ));
    }

    return array(
      '#theme' => 'pos_buttons',
      '#buttons' => $buttons,
      '#numbers' => $numbers,
    );
  }
}