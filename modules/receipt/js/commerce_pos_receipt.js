(function ($) {
  Drupal.ajax.prototype.commands.printReceipt = function (ajax, response, status) {
    $(response.content).print({
      globalStyles: false,
      stylesheet: Drupal.settings.commercePosReceipt.cssUrl
    });
  }
} (jQuery));
