<?php


class POSPane_Commands extends POS_Pane {
  protected $config = array(
    'show_keypad' => FALSE,
  );

  function build(POS_State $state, POS_Command_Registry $registry, $js = FALSE) {
    $buttons = array();
    $numbers = array();
    foreach ($registry->getCommands() as $command) {
      if ($command->access(NULL, $state)) {
        if($command instanceof POS_Command_Modal) {
          $buttons[] = $command->getModalButton();
        }
        else {
          $buttons[] = $command->getButton();
        }
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