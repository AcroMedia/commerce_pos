<?php

namespace Drupal\commerce_pos_receipt\Ajax;

use Drupal\Core\Ajax\CommandInterface;

/**
 * AJAX command for retrieving data and printing a receipt.
 */
class PrintReceiptCommand implements CommandInterface {

  protected $receipt;

  /**
   * Constructs a new PrintReceiptCommand object.
   *
   * @param string $receipt
   *   ID of the wrapper.
   */
  public function __construct($receipt) {
    $this->receipt = $receipt;
  }

  /**
   * Return an array to be run through json_encode and sent to the client.
   */
  public function render() {
    return [
      'command' => 'printReceipt',
      'content' => $this->receipt,
    ];
  }

}
