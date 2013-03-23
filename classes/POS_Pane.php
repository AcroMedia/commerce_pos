<?php

abstract class POS_Pane {
  protected $id;
  protected $name;
  protected $config = array();

  /**
   * Create a new POS_Pane.
   *
   * @param $id
   * @param $name
   * @param array $options
   */
  public function __construct($id, $name, array $options = array()) {
    $this->id = $id;
    $this->name = $name;
    $this->config = $options + $this->config;
  }

  /**
   * Build the render array representing this pane.
   *
   * @param POS_State $state
   * @param POS_Command_Registry $registry
   * @param bool $js
   *
   * @return mixed
   */
  public abstract function build(POS_State $state, POS_Command_Registry $registry, $js = FALSE);

  /**
   * Retrieve the ID of this pane.
   *
   * @return string
   */
  public function getID() {
    return $this->id;
  }

  /**
   * Retrieve the human readable name of this pane.
   *
   * @return mixed
   */
  public function getName() {
    return $this->name;
  }
}
