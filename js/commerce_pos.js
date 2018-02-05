(function ($, Drupal, drupalSettings) {
    'use strict';

    // If we hit enter on the order item input, it will add the first result if possible.
    Drupal.behaviors.CommercePosOrderItemQuickAdd = {
        attach: function (context, settings) {
            $('input.form-autocomplete').keypress(function(event) {
                if (event.which == 13) {
                    var autocomplete_results = $('.commerce-pos-autocomplete-results');
                    if (typeof autocomplete_results[0] != 'undefined') {
                        autocomplete_results[0].click();
                    }
                }

            });
        }
    };
}(jQuery, Drupal, drupalSettings));