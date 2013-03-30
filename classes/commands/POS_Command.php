<?php
/**
 * @file
 * POS_Command is the generic class from which all other commands derive.
 */

abstract class POS_Command {
  protected $input_pattern;
  protected $name;
  protected $id;
  protected $config = array();

  /**
   * Create a new POS_Command
   *
   * @param $name
   *  The human readable name of this command.
   * @param $id
   *  The unique ID (machine readable) of this command.
   * @param $input_pattern
   *  The input pattern to use for this command.
   * @param array $options
   *  Any additional options to provide the command.
   */
  function __construct($name, $id, $input_pattern, array $options = array()) {
    $this->name = $name;
    $this->id = $id;
    $this->input_pattern = $input_pattern;
    $this->config = $options + $this->config;
  }

  /**
   * Get the input pattern that was given for this command.
   *
   * @return mixed
   */
  public function getInputPattern() {
    return $this->input_pattern;
  }

  /**
   * Get the ID of this command.
   *
   * @return mixed
   */
  public function getId() {
    return $this->id;
  }

  /**
   * Get the human readable name of this command.
   *
   * @return mixed
   */
  public function getName() {
    return $this->name;
  }

  /**
   * For a given input, parse it according to the input pattern.
   *
   * This takes a string like "OR1" (load order 1)
   * and turns it into 1, which can then be passed to execute().
   *
   * @param string $input
   *
   * @return string|bool
   *  Returns bool FALSE if the input doesn't match the pattern.
   *  Returns an argument if input matches and one is present.
   *  Returns an empty string if input matches and no argument is present.
   */
  public function parseInput($input) {
    $pattern = str_replace('%s', '(?P<arg>\S+)', $this->input_pattern);
    if(preg_match('/^' . $pattern . '$/', $input, $matches)) {
      return isset($matches['arg']) ? $matches['arg'] : '';
    }
    return FALSE;
  }

  /**
   * Check whether this command wants to act on a given input.
   *
   * @param string $input
   *  The textual input to check.
   *
   * @return bool
   *  A boolean flag of whether the input pattern for this command matches the input.
   */
  public function matchesInput($input) {
    $pattern = str_replace('%s', '\S+', $this->input_pattern);
    return preg_match('/^' . $pattern . '$/', $input);
  }

  /**
   * Create an input for this command.
   *
   * @param string $argument
   *  An argument to pass to the command when this input is invoked.
   *  The nature, format, and presence of this argument depend on the command.
   *
   * @return bool|string
   */
  public function createInput($argument = NULL) {
    // If the command has no input pattern, or has one, but it requires input and there is none...
    if(!$this->input_pattern || (strpos($this->input_pattern, '%s') !== FALSE && !isset($argument))) {
      return FALSE;
    }
    return sprintf($this->input_pattern, $argument);
  }

  /**
   * Determine if this command is accessible for the given input/state.
   *
   * Returning false here will cause the command to be unusable, and the
   * button will be hidden as well.
   *
   * @param CommercePOS $pos
   *
   * @param string $input
   *  Any input that has been entered.  This may or may not actually be set,
   *  depending on the context it is called in.
   *
   * @return bool
   *  TRUE|FALSE
   */
  public abstract function access(CommercePOS $pos, $input = '');

  /**
   * Run this command for a given input.
   *
   * It is expected that all commands will make changes to the POS_State.
   *
   * @param CommercePOS $pos
   *
   * @param string $input
   *  The textual input.
   *
   * @return mixed
   */
  public abstract function execute(CommercePOS $pos, $input = '');
}

