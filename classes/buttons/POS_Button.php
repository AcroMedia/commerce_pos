<?php

interface POS_Button {
  //public function __construct($name, $id, array $options);
  public function render($text = NULL, $input = NULL, $options = array());
  public function access($input, POS_State $state);
  public function getId();
  public function getName();
}