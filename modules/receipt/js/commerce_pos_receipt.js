(function ($) {
  Drupal.ajax.prototype.commands.printReceipt = function (ajax, response, status) {
    $(response.content).print({
      globalStyles: false,
      stylesheet: Drupal.settings.commercePosReceipt.cssUrl
    });
  };

  Drupal.behaviors.commercePosReceipt = {
    attach: function (context, settings) {
      if (settings.commercePosReceipt.printInfo) {
        printReceiptRequest(settings.commercePosReceipt.printInfo);
      }
    }
  };

  function printReceiptRequest(printInfo) {
    var element = $('<a id="commerce-pos-print-receipt' + printInfo.transactionId + '"></a>');
    var element_settings = {};
    // Clicked links look better with the throbber than the progress bar.
    element_settings.progress = { 'type': 'throbber' };

    // For anchor tags, these will o to the target of the anchor rather
    // than the usual location.
    // @TODO: pull this from settings.
    element_settings.url = printInfo.printUrl;
    element_settings.event = 'click';

    var base = element.attr('id');
    Drupal.ajax[base] = new Drupal.ajax(base, element.get(0), element_settings);

    element.trigger('click');
  };

} (jQuery));
