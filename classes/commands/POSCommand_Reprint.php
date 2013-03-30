<?php

class POSCommand_Reprint extends POS_Command {

  function access(CommercePOS $pos, $input = '') {
    return TRUE;
  }

  function execute(CommercePOS $pos, $input = '') {
    $pos->getState()->setPrintRender(array(
      '#theme' => 'pos_receipt',
      '#pos' => $pos,
    ));
  }
}