(function ($) {
    Drupal.behaviors.commercePosMessages = {
        attach: function (context, settings) {
            if (settings.commercePosMessages && settings.commercePosMessages.focusInput) {
                $('#commerce-pos-messages-add-note-wrapper textarea').focus();
            }
        }
    };

} (jQuery));