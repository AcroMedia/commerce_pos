<?php

class POSCommand_ViewsReport extends POS_Command {
  var $config = array(
    'view_name' => NULL,
    'display_id' => NULL,
  );
  protected $_view = NULL;

  function access(CommercePOS $pos, $input = '') {
    if($view = $this->getView()) {
      return $view->access(array($this->config['display_id']), $pos->getState()->getCashier());
    }
    drupal_set_message(t('View required for @command not found.', array('@command' => $this->getName())), 'warning');
    return FALSE;
  }

  function execute(CommercePOS $pos, $input = '') {
    $pos->getState()->setPrintRender(array(
      '#markup' => $this->getView()->preview(),
    ));
  }

  protected function getView() {
    if(!isset($this->_view)) {
      if($this->_view = views_get_view($this->config['view_name'])) {
        $this->_view->set_display($this->config['display_id']);
      }
    }
    return  $this->_view;
  }
}