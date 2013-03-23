<?php

abstract class POS_Command_Modal extends POS_Command {

  public function getModalButton() {
    ctools_include('ajax');
    ctools_include('modal');
    ctools_modal_add_js();
    return ctools_modal_text_button($this->name, $this->getModalUrl(), '', 'pos-button');
  }

  function getModalUrl() {
    return 'admin/commerce/pos/nojs/' . $this->id;
  }

  abstract function modalPage($js, POS_State $state);
}