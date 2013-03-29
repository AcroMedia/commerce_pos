<?php

class POSCommand_Reprint extends POS_Command {

  function access(POS $pos, $input = '') {
    return TRUE;
  }

  function execute(POS $pos, $input = '') {
    $pos->getState()->setPrintRender(array(
      '#theme' => 'pos_receipt',
      '#pos' => $pos,
    ));
  }
}