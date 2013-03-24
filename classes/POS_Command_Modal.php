<?php

abstract class POS_Command_Modal extends POS_Command {

  public function getModalButton() {
    ctools_include('ajax');
    ctools_include('modal');
    ctools_modal_add_js();

    return theme('link__pos_button__modal', array(
      'text' => $this->name,
      'path' => $this->getModalUrl(),
      'options' => array(
        'attributes' => array(
          'class' => array('pos-button', 'ctools-use-modal'),
        ),
        'html' => FALSE,
      )
    ));
  }

  function getModalUrl() {
    return 'admin/commerce/pos/nojs/' . $this->id;
  }

  abstract function modalPage($js, POS_State $state);
}