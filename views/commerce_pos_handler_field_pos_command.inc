<?php

class commerce_pos_handler_field_pos_command extends views_handler_field {
  protected $button;


  function init($view, $options) {
    $registry = POS_Button_Registry::create(POS_Command_Registry::create());
    if($button = $registry->getButton($this->definition['command'])) {
      $this->button = $button;
    }
    return parent::init($view, $options);
  }

  function option_definition() {
    $options = parent::option_definition();
    $options['text'] = array('default' => '', 'translatable' => TRUE);

    return $options;
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
    if($this->button) {
      $value = $this->get_value($values);
      drupal_add_library('system', 'jquery.bbq');
      drupal_add_js(drupal_get_path('module', 'commerce_pos') . '/theme/pos-interface.js');
      drupal_add_css(drupal_get_path('module', 'commerce_pos') . '/theme/pos-interface.css');
      return $this->button->render($this->options['text'], $value);
    }
  }

}