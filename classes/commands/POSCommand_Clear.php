<?php

class POSCommand_Clear extends POS_Command {

  function execute($input, POS_State $state) {
    $state->reset();
  }

  function access($input, POS_State $state) {
    return TRUE;
  }
}