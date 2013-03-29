<?php


class POSPane_Input extends POS_Pane {

  function build(POS_State $state, POS_Button_Registry $registry, $js = FALSE) {
    return drupal_get_form('pos_input_form');
  }
}
