<?php
/**
 * @file
 *  This class represents a button that pops up a view in a modal window.
 */

class POS_Button_Modal_View extends POS_Button_Modal {

  public function access(CommercePOS $pos, $input) {
    if($view = views_get_view($this->config['view'])) {
      return $view->access(array($this->config['display']));
    }
    return FALSE;
  }

  public function modalPage(CommercePOS $pos, $js) {
    $output = commerce_embed_view($this->config['view'], $this->config['display'], array(), $_GET['q']);
    if ($js) {
      return array(
        ctools_modal_command_display(drupal_get_title(), $output)
      );
    }
    return $output;
  }

}