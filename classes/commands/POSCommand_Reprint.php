<?php

class POSCommand_Reprint extends POS_Command {

  function access($input, POS_State $state) {
    return TRUE;
  }

  function execute($input, POS_State $state) {
    $state->setPrintRender(array(
      '#theme' => 'pos_receipt',
      '#state' => $state,
      // @todo: this is pretty sloppy. Is there a better way?
      '#registry' => POS_Command_Registry::create(),
    ));
  }
}