(function ($) {
  Drupal.behaviors.commercePosReport = {
    attach: function (context, settings) {

    }
  };

  Drupal.AjaxCommands.prototype.printWindow = function (ajax, response, status) {
    $(response.content).print({
      globalStyles: false,
      stylesheet: drupalSettings.commercePosReports.cssUrl
    });
  };

} (jQuery));
