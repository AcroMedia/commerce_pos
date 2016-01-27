(function ($) {
    Drupal.behaviors.commercePosDiscount = {
        attach: function (context, settings) {
            if (settings.commercePosDiscount && settings.commercePosDiscount.focusInput) {
                $('#commerce-pos-discount-wrapper .form-wrapper .form-text').focus();
            }
        }
    };

} (jQuery));