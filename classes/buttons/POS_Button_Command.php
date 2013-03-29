<?php


class POS_Button_Command implements POS_Button {
  protected $command;

  public function __construct(POS_Command $command) {
    $this->command = $command;
  }

  public function getId() {
    return $this->command->getId();
  }
  public function getName() {
    return $this->command->getName();
  }

  public function render($text = NULL, $input = NULL, $options = array()) {
    if($pattern = $this->command->constructInputFromPattern($input)) {
      static $token = NULL;
      if(!$token) {
        $token = drupal_get_token('pos_command');
      }
      $text = !empty($text) ? $text : $this->getName();

      return theme('link__pos_button', array(
        'text' => $text,
        'path' => 'admin/commerce/pos',
        'options' => drupal_array_merge_deep(array(
          'attributes' => array(
            'class' => array('pos-button', 'pos-button-' . $this->getId()),
            'data-pos-input' => $pattern,
            'data-pos-submit' => 'true',
          ),
          'query' => array(
            'command' => $pattern,
            'token' => $token,
          ),
          'html' => FALSE,
        ), $options)
      ));
    }
    return FALSE;
  }

  public function access($input, POS_State $state) {
    return $this->command->access($input, $state);
  }
}