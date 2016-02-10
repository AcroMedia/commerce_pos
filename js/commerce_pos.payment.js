(function ($) {
  Drupal.behaviors.commercePosSalePay = {
    attach: function (context, settings) {
      $('.commerce-pos-keypad-key', context).each(function () {
        // Bind click events to the keypad keys so that pressing them populates
        // the amount field with the respective value.
        var self = $(this);
        self.once('commerce-pos-key', function () {
          self.data('commerce-pos-keybind-amount', $('.commerce-pos-keypad-amount input', context));
          self.click(function () {
            var self = $(this);
            var inputs = self.data('commerce-pos-keybind-amount');
            var key = self.attr('data-keybind');

            self.addClass('pressed');
            window.setTimeout(function() {
              self.removeClass('pressed');
            }, 500);

            inputs.each(function () {
              var self = $(this);
              var currentValue = self.val();

              if (key != "") {
                self.val(currentValue + key);
              }
              else {
                var valLength = currentValue.length;

                if (valLength > 0) {
                  // An empty key represents a backspace press instead.
                  self.val(currentValue.substr(0, valLength - 1));
                }
              }
            });
          });
        });
      });

      //Have the Enter key trigger the 'Add' button when focused on text input
      $('.commerce-pos-keypad-amount input', context).keypress(function(event) {
        if (event.keyCode == 13) {
          $('.commerce-pos-keypad-actions .form-submit').mousedown();
        }

        //$('.commerce-pos-keypad-actions .form-submit', context).mousedown(function(){
        //  $('.commerce-pos-keypad-amount input').val('50');
        //});
      });

      $('.commerce-pos-summary-toggle', context).each(function () {
        var self = $(this);

        $(this).once('commerce-pos-summary-toggle', function () {
          self.click(function (e) {
            $('.commerce-pos-product-summary').toggleClass('element-invisible');
            e.preventDefault();
          });
        });
      });

      $('.commerce-pos-remove-payment', context).click(function(event){
        event.preventDefault();

        var transaction_id = $(this).data('transaction-id');

        $('.commerce-pos-remove-payment-input').val(transaction_id);
        $('.commerce-pos-remove-payment').trigger('remove_payment');
      });
    }
  };

} (jQuery));