(function ($) {
  Drupal.behaviors.commercePosDiscount = {
    attach: function (context, settings) {
      if (settings.commercePosDiscount && settings.commercePosDiscount.focusInput) {
        console.log(settings);
        $('#commerce-pos-discount-wrapper-' + settings.commercePosDiscount.lineItemId + ' .form-wrapper .form-text').focus();
      }

      $('.commerce-pos-remove-order-discount', context).click(function(event){
        event.preventDefault();

        $('.commerce-pos-remove-order-discount').trigger('remove_order_discount');
      });
    }
  };

}(jQuery));