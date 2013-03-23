<?php

class POS_Controller {
  protected $state;
  protected $registry;

  /**
   * Create a new POS_Controller.
   *
   * @param POS_State $state
   * @param POS_Command_Registry $registry
   */
  public function __construct(POS_State $state, POS_Command_Registry $registry) {
    $this->state = $state;
    $this->registry = $registry;
  }

  /**
   * For a given input, attempt to execute the command that matches.
   *
   * @param $input
   *
   * @return bool
   * @throws InvalidArgumentException
   * @throws RuntimeException
   */
  public function executeCommand($input) {
    if ($command = $this->registry->determineActiveCommand($input)) {
      $command_input = $command->deconstructInputFromPattern($input);
      if ($command->access($command_input, $this->state)) {
        $command->execute($command_input, $this->state);
        return TRUE;
      }
      else {
        // This should not normally happen through the UI.  We should
        // always check access before displaying a button to do something.
        throw new InvalidArgumentException('Access denied.');
      }
    }
    else {
      throw new InvalidArgumentException('Invalid input.');
    }
  }

  /**
   * Get the current state this controller is operating on.
   *
   * @return POS_State
   */
  public function getState() {
    return $this->state;
  }
}