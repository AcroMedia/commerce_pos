<?php

class POSCommand_Clear extends POS_Command {

  function execute(POS $pos, $input = '') {
    $pos->getState()->reset();
  }

  function access(POS $pos, $input = '') {
    return TRUE;
  }
}