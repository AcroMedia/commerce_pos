(function ($) {
  Drupal.behaviors.commercePosDiscount = {
    attach: function (context, settings) {
      if (settings.commercePosDiscount && settings.commercePosDiscount.focusInput) {
        $('#commerce-pos-discount-wrapper-' + settings.commercePosDiscount.lineItemId + ' .form-wrapper .form-text').focus();
      }

      $('.commerce-pos-remove-order-discount', context).click(function(event){
        event.preventDefault();

        $('.commerce-pos-remove-order-discount').trigger('remove_order_discount');
      });

      $('.commerce-pos-remove-order-coupon', context).click(function(event){
        event.preventDefault();
        $("[name='remove_coupon_discount_name']").val($(this).data('discount'));
        console.log($(this).data('discount'));
        $('.commerce-pos-remove-coupon').trigger('remove_order_coupon');
      });
    }
  };

}(jQuery));
