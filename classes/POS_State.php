<?php
/*
 * @file
 *  Represents the current status of the POS system, including order and
 *  messaging.
 */
class POS_State {
  protected $order;
  protected $print = FALSE;

  /**
   * Helper to retrieve the POS_State from a user's session.
   *
   * @param bool $create
   *   If the POS_State hasn't been created yet, whether to create it.
   *
   * @return POS_State|bool
   */
  static function get($create = TRUE) {
    if (isset($_SESSION['pos_state'])) {
      return $_SESSION['pos_state'];
    }
    elseif ($create) {
      return $_SESSION['pos_state'] = new self;
    }
    return FALSE;
  }

  /**
   * Check if the state has an order on it currently.
   *
   * @return bool
   */
  public function hasOrder() {
    return (bool) $this->order;
  }

  /**
   * Retrieve the order currently active.
   *
   * @return stdClass|NULL
   */
  public function getOrder() {
    return $this->order ? $this->order : commerce_order_new(0, 'pos_in_progress');
  }

  /**
   * Reset the order value.
   *
   * @param $order
   */
  public function setOrder($order) {
    $this->order = $order;
  }

  /**
   * Clear the current order and any alerts.
   */
  public function reset() {
    $this->order = NULL;
    $this->print = FALSE;
  }

  /**
   * Get the cashier for the current order.
   *
   * @todo: Do we need this?  It could be really handy, but maybe not necessary.
   *
   * @return stdClass
   */
  public function getCashier() {
    global $user;
    return $user;
  }

  /**
   * Set a render array to be printed on the next interface refresh.
   *
   * @param array $render
   */
  public function setPrintRender(array $render) {
    $this->print = $render;
  }

  /**
   * Get and clear the current printing render array.
   *
   * @return array|bool
   */
  public function getPrintRender() {
    if ($this->print) {
      $print = $this->print;
      $this->print = FALSE;
      return $print;
    }
    return FALSE;
  }
}