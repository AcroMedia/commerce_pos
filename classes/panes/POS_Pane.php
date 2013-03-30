<?php

abstract class POS_Pane {
  protected $id;
  protected $name;
  protected $config = array();

  /**
   * Create a new POS_Pane.
   *
   * @param $name
   * @param $id
   * @param array $options
   */
  public function __construct($name, $id, array $options = array()) {
    $this->id = $id;
    $this->name = $name;
    $this->config = $options + $this->config;
  }

  /**
   * Build the render array representing this pane.
   *
   * @param CommercePOS $pos
   * @param POS_Interface $interface
   * @param bool $js
   *
   * @return mixed
   */
  public abstract function build(CommercePOS $pos, POS_Interface $interface, $js = FALSE);

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
