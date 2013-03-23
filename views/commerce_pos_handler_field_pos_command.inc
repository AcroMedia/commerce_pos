<?php

class commerce_pos_handler_field_pos_command extends views_handler_field {
  protected $command;


  function init($view, $options) {
    $this->command = $this->definition['command'];
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
    $value = $this->get_value($values);
    return $this->command->getButton($this->options['text'], $value, array(
      'attributes' => array(
        'class' => array('ctools-close-modal')
      )
    ));
  }

}