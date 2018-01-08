<?php

namespace Drupal\commerce_pos_receipt\Ajax;

use Drupal\Core\Ajax\CommandInterface;

/**
 * AJAX command for completing the order payment process.
 */
class CompleteOrderCommand implements CommandInterface {

  /**
   * Return an array to be run through json_encode and sent to the client.
   */
  public function render() {
    return [
      'command' => 'completeOrder',
    ];
  }

}
