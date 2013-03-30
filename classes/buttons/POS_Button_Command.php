<?php
/**
 * @file
 *  This class represents a button that directly invokes a backend command.
 */
class POS_Button_Command implements POS_Button {
  protected $name;
  protected $id;
  protected $command_id;

  public function __construct($name, $id, array $options = array()) {
    $this->command_id = $options['command_id'];
    $this->id = $id;
    $this->name = $name;
  }

  public function getId() {
    return $this->id;
  }
  public function getName() {
    return $this->name;
  }

  public function render(CommercePOS $pos, $text = NULL, $input = NULL, $options = array()) {
    if($command = $pos->getCommand($this->command_id)) {
      if($pattern = $command->createInput($input)) {
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
    }


    return FALSE;
  }

  public function access(CommercePOS $pos, $input) {
    if($command = $pos->getCommand($this->command_id)) {
      return $command->access($pos, $input);
    }
    return FALSE;
  }
}