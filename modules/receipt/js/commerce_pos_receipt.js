(function ($) {
  Drupal.ajax.prototype.commands.printReceipt = function (ajax, response, status) {
    $(response.content).print({
      globalStyles: false,
      mediaPrint: true,
      stylesheet: Drupal.settings.commercePosReceipt.cssUrl
    });
  }
} (jQuery));
