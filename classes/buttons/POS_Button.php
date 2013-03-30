<?php

interface POS_Button {
  /**
   * Create a new instance of this class.
   *
   * @param $name
   *  The display name of the button.
   * @param $id
   *  The ID of the button (used for CSS class, and internally).
   * @param array $options
   *  Additional options available to the handler.
   */
  public function __construct($name, $id, array $options = array());

  /**
   * Get the ID of the command.
   *
   * @return string
   */
  public function getId();

  /**
   * Get the name of the command.
   *
   * @return string
   */
  public function getName();

  /**
   * Render this button.
   *
   * @param CommercePOS $pos
   *  The POS object.
   * @param null $text
   *  The link text (defaults to the command name if not specified).
   * @param null $input
   *  Assuming this is command based button, any input that needs to be passed to the
   *  POS form (ex: for the order command, $input is order id, and will be concatenated
   *  into the input pattern, like OR11).
   * @param array $options
   *  Additional options that will be passed to theme('link').
   *
   * @return string
   *  Returns a string of markup representing the button.
   */
  public function render(CommercePOS $pos, $text = NULL, $input = NULL, $options = array());

  /**
   * Check whether the button should be displayed in this context.
   *
   * @param CommercePOS $pos
   *  The POS object - contains the state, and all backend commands.
   * @param $input
   *  Input to check for.  (ex: for the order command, $input is order id, so we will
   *  check that the user has view access on that order id).
   *
   * @return bool
   */
  public function access(CommercePOS $pos, $input);
}