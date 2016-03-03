(function ($) {
  Drupal.ajax.prototype.commands.printReceipt = function (ajax, response, status) {
    $(response.content).print({
      globalStyles: false,
      stylesheet: Drupal.settings.commercePosReceipt.cssUrl
    });
  };

  Drupal.behaviors.commercePosReceipt = {
    attach: function (context, settings) {
      if (Drupal.settings.commercePosReceipt.printInfo) {
        printReceiptRequest(Drupal.settings.commercePosReceipt.printInfo);
        Drupal.settings.commercePosReceipt.printInfo = false;
      }
    }
  };

  function printReceiptRequest(printInfo) {
    var element = $('<a id="commerce-pos-print-receipt' + printInfo.transactionId + '"></a>');
    var element_settings = {};
    element_settings.progress = { 'type': 'throbber' };
    element_settings.url = printInfo.printUrl;
    element_settings.event = 'click';
    var base = element.attr('id');
    Drupal.ajax[base] = new Drupal.ajax(base, element.get(0), element_settings);

    element.trigger('click');
  }

} (jQuery));
