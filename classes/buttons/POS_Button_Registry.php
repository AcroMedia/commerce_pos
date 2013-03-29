<?php


class POS_Button_Registry {
  protected static $instance;
  protected $buttons;

  public static function create(POS_Command_Registry $registry) {
    if(!self::$instance) {
      $buttons = module_invoke_all('commerce_pos_buttons', $registry);
      drupal_alter('commerce_pos_buttons', $buttons, $registry);
      self::$instance = new self($buttons);
    }
    return self::$instance;
  }

  function __construct(array $buttons = array()) {
    $this->setButtons($buttons);
  }

  public function setButtons(array $buttons = array()) {
    $this->buttons = array();
    foreach($buttons as $button) {
      $this->buttons[$button->getId()] = $button;
    }
  }

  public function getButtons() {
    return $this->buttons;
  }

  public function getButton($id) {
    return isset($this->buttons[$id]) ? $this->buttons[$id] : FALSE;
  }
}