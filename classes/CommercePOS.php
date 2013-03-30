<?php

class CommercePOS {
  protected static $instance;
  protected $commands = array();
  protected $state;

  /**
   * Singleton constructor for this class.
   *
   * @return CommercePOS
   *  The pos object, configured with command handlers.
   */
  public static function instance() {
    if(!self::$instance) {
      ctools_include('plugins');
      $plugins = ctools_get_plugins('commerce_pos', 'commands');
      uasort($plugins, 'ctools_plugin_sort');
      foreach ($plugins as $id => $plugin) {
        if ($handler_class = ctools_plugin_get_class($plugin, 'handler')) {
          $handler = new $handler_class($plugin['title'], $id, $plugin['input_pattern'], $plugin['handler_options']);
          if ($handler instanceof POS_Command) {
            $commands[$id] = $handler;
          }
        }
      }

      self::$instance = new self(POS_State::get(), $commands);
    }

    return self::$instance;
  }

  public function __construct( POS_State $state, array $commands) {
    $this->setCommands($commands);
    $this->state = $state;
  }

  /**
   * @param POS_Command[] $commands
   */
  public function setCommands(array $commands) {
    $this->commands = array();
    foreach($commands as $command) {
      $this->commands[$command->getId()] = $command;
    }
  }

  /**
   * @return POS_Command[]
   */
  public function getCommands() {
    return $this->commands;
  }

  /**
   * @param $id
   *
   * @return POS_Command|bool
   */
  public function getCommand($id) {
    return isset($this->commands[$id]) ? $this->commands[$id] : FALSE;
  }

  /**
   * @param string $input
   *
   * @return POS_Command|bool
   */
  public function determineActiveCommand($input) {
    foreach ($this->commands as $command) {
      if ($command->matchesInput($input)) {
        return $command;
      }
    }
    return FALSE;
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
    if($command = $this->determineActiveCommand($input)) {
      $input = $command->parseInput($input);
      if($command->access($this, $input)) {
        return $command->execute($this, $input);
      }
      throw new InvalidArgumentException('Access Denied');
    }
    throw new InvalidArgumentException('Invalid input.');
  }

  public function getState() {
    return $this->state;
  }
}