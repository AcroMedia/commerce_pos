<?php

class POSCommand_Clear extends POS_Command {

  function execute(CommercePOS $pos, $input = '') {
    $pos->getState()->reset();
  }

  function access(CommercePOS $pos, $input = '') {
    return TRUE;
  }
}