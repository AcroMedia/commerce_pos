<?php

class POSPane_Alerts extends POS_Pane {


  function build(CommercePOS $pos, POS_Interface $interface, $js = FALSE) {
    return $js ? array('#theme' => 'status_messages') : array();
  }
}