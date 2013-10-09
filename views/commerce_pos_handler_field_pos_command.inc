<?php

class commerce_pos_handler_field_pos_command extends views_handler_field {
  protected $pos;
  protected $interface;


  function init(&$view, &$options) {
    $this->pos = CommercePOS::instance();
    $this->interface = POS_Interface::instance($this->pos);
    return parent::init($view, $options);
  }

  function option_definition() {
    $options = parent::option_definition();
    $options['text'] = array('default' => '', 'translatable' => TRUE);

    return $options;
  }

  function allow_advanced_render() {
    return FALSE;
  }

  function options_form(&$form, &$form_state) {
    $form['text'] = array(
      '#type' => 'textfield',
      '#title' => t('Text to display'),
      '#default_value' => $this->options['text'],
    );
    parent::options_form($form, $form_state);
  }

  function render($values) {
    if($button = $this->interface->getButton($this->definition['command'])) {
      $value = $this->get_value($values);
      if($button->access($this->pos, $value)) {
        drupal_add_library('system', 'jquery.bbq');
        drupal_add_js(drupal_get_path('module', 'commerce_pos') . '/theme/pos-interface.js');
        drupal_add_css(drupal_get_path('module', 'commerce_pos') . '/theme/pos-interface.css');
        return $button->render($this->pos, $this->options['text'], $value);
      }
    }
  }

}