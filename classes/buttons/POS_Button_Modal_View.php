<?php


class POS_Button_Modal_View extends POS_Button_Modal {

  public function access($input, POS_State $state) {
    if($view = views_get_view($this->config['view'])) {
      return $view->access(array($this->config['display']));
    }
    return FALSE;
  }

  public function modalPage($js, POS_State $state) {
    $output = commerce_embed_view($this->config['view'], $this->config['display'], array(), $_GET['q']);
    if ($js) {
      return array(
        ctools_modal_command_display(drupal_get_title(), $output)
      );
    }
    return $output;
  }

}