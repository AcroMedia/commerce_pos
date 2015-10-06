(function ($) {
  Drupal.ajax.prototype.commands.printReceipt = function (ajax, response, status) {
    $(response.content).print({
      mediaPrint: true
    });
  }
} (jQuery));
