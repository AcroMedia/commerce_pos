<?php
/**
 * @file
 * The command registry is a pretty simple wrapper for an array of commands.
 * It queries each command to determine the active one for a given input.
 */

class POS_Command_Registry {
  protected $commands;
  protected static $instance;

  /**
   * Singleton constructor.
   *
   * @return POS_Command_Registry
   */
  public static function create() {
    if(!self::$instance) {
      $commands = array();
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
      self::$instance = new self($commands);
    }
    return self::$instance;
  }

  /**
   * Create a new POS_Command_Registry.
   *
   * @param array $commands
   */
  public function __construct(array $commands) {
    $this->setCommands($commands);
  }

  /**
   * Check if there is a command that wants to act on a given input.
   *
   * We return the first command that indicates it wants to act.
   *
   * @param string $input
   *  The textual input that has been entered.
   *
   * @return POS_Command|bool
   *  The command that should be active.
   */
  public function determineActiveCommand($input) {
    foreach ($this->commands as $command) {
      if ($command->shouldRun($input)) {
        return $command;
      }
    }
    return FALSE;
  }

  /**
   * Get all registered commands.
   *
   * @return POS_Command[]
   */
  public function getCommands() {
    return $this->commands;
  }

  /**
   * Set the registered commands.
   *
   * @param POS_Command[]
   */
  public function setCommands(array $commands) {
    // Avoid blindly copying over the commands to make sure they
    // are keyed by the ID.
    foreach ($commands as $command) {
      $this->commands[$command->getId()] = $command;
    }
  }

  /**
   * Check if a command exists in the registry.
   *
   * @param $id
   *  The canonical ID of the command.
   *
   * @return bool
   *  true|false depending on whether the command was found.
   */
  public function hasCommand($id) {
    foreach ($this->commands as $command_id => $command) {
      if ($command_id == $id) {
        return TRUE;
      }
    }
    return FALSE;
  }

  /**
   * Retrieve a named command from the registry.
   *
   * @param $id
   *  The canonical ID of the command.
   *
   * @return POS_Command|bool
   */
  public function getCommand($id) {
    foreach ($this->commands as $command_id => $command) {
      if ($command_id == $id) {
        return $command;
      }
    }
    return FALSE;
  }
}