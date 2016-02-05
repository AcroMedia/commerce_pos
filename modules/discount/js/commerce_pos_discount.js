(function ($) {
  Drupal.behaviors.commercePosDiscount = {
    attach: function (context, settings) {
      if (settings.commercePosDiscount && settings.commercePosDiscount.focusInput) {
        console.log(settings);
        $('#commerce-pos-discount-wrapper-' + settings.commercePosDiscount.lineItemId + ' .form-wrapper .form-text').focus();
      }
    }
  };

}(jQuery));