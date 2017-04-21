(function ($) {
  Drupal.behaviors.commercePosSale = {
    attach: function (context, settings) {
      setupProductAutocomplete(context, settings);

      $('.commerce-pos-hidden-element', context).each(function() {
        var element = $(this);
        var elementLabel = element.siblings('label');

        element.click(function() {
          element.show();
          elementLabel.hide();
          element.focus();
        });
      });

      // Prevent the form from getting submitted if
      // the search box is in focus
      $('.commerce-pos-product-search').keydown(function(event) {

        if (event.keyCode == 13 && $('.commerce-pos-product-search').is(':focus')) {
          event.preventDefault();
        }
      });

      $('body', context).each(function () {
        $(this).once('commerce-pos-keybindings')
      });

      if (settings.commercePosSale && settings.commercePosSale.focusProductInput) {
        $('.commerce-pos-product-search').focus();
      }

      // Key Navigator Stuff
      $('commerce-pos-product-autocomplete .commerce-pos-product-display', context).keynavigator({
        activateOn: 'click',
        parentFocusOn: 'mouseover'
      });

      //When focused on quantity, price, or customer email input, pressing enter will unfocus and submit any changes
      $('.line-item-row-wrapper input, #commerce-pos-customer-input-wrapper input', context).keypress(function(event) {
        if (event.keyCode == 13) {
          $(this).blur();
        }
      });

      //Trigger the 'Pay' button to be clicked when f4 is pressed
      $('body', context).keydown(function(event){
        if (event.keyCode == 115) {
          $('.commerce-pos-btn-pay').click();
        }
      });

      $('.commerce-pos-void-payment', context).click(function(event){
        event.preventDefault();

        $('.commerce-pos-void-payment-input').val($(this).data('transaction-id'));
        $('.commerce-pos-void-payment').trigger('void_payment');
      });
    }
  };

  /**
   * Attaches some custom autocomplete functionality to the product search field.
   */
  function setupProductAutocomplete (context, settings) {
    $('.commerce-pos-product-autocomplete', context).each(function () {
      var element = $(this);

      element.once('commerce-pos-autocomplete', function () {
        element.autocomplete({
          source: settings.commercePosSale.productAutoCompleteUrl,
          focus: function(event, ui) {
            event.preventDefault(); // without this: keyboard movements reset the input to ''

            // clear all other rows as selected
            $('.commerce-pos-transaction-product-search-item').removeClass('selected');
            ui.item.element.addClass('selected');
          },
          select: function(event, ui){
            var sku = ui.item.element.find('.btn-add').attr('data-product-sku');

            if (Drupal.settings.commercePosSale.autoCompleteCallback) {
              Drupal[Drupal.settings.commercePosSale.autoCompleteNamespace][Drupal.settings.commercePosSale.autoCompleteCallback](sku);
            }
            else {
              addProductSku(sku);
            }
          },
          context: this
        });

        // Override the default UI autocomplete render function.
        element.data('ui-autocomplete')._renderItemData = function (ul, item) {
          var product = $("<li></li>");

          item.element = product;

          product.addClass('commerce-pos-transaction-product-search-item')
            .data("ui-autocomplete-item", item)
            .append(item.markup)
            .appendTo(ul)
            .find('.btn-add').click(function (e) {
              e.preventDefault();
              e.stopPropagation(); // prevent product from being added via bubbling

              var sku = $(this).attr('data-product-sku');

              if (Drupal.settings.commercePosSale.autoCompleteCallback) {
                Drupal[Drupal.settings.commercePosSale.autoCompleteNamespace][Drupal.settings.commercePosSale.autoCompleteCallback](sku);
              }
              else {
                addProductSku(sku);
              }

              element.data('ui-autocomplete').close();

          });

          return product;
        };
        // Overide renderMenu to sort the list of autocomplete results
        element.data('ui-autocomplete')._renderMenu = function (ul, items) {
          // Sort the items by title in an inline comparison function
          items.sort(function (a,b) {return a.title.localeCompare(b.title)});
          $.each(items, function(index, item){
            element.data('ui-autocomplete')._renderItemData(ul, item);
          });
        }
      });
    });
  }

  /**
   * Populates the product SKU field on the form and triggers its AJAX event.
   */
  function addProductSku (sku) {
    $('.commerce-pos-product-sku-input')
      .val(sku)
      .trigger('blur');
  }

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

      $('.commerce-pos-void-payment', context).click(function(event){
        event.preventDefault();

        var transaction_id = $(this).data('transaction-id');

        $('.commerce-pos-void-payment-input').val(transaction_id);
        $('.commerce-pos-void-payment').trigger('void_payment');
      });

      if (settings.commercePosPayment && settings.commercePosPayment.focusInput) {
        if($(settings.commercePosPayment.selector).length > 0) {
          $(settings.commercePosPayment.selector).focus();
        }
      }
    }
  };

  Drupal.behaviors.commercePosModal = {
    attach: function (context, settings) {
      $('#commerce-pos-form-modal-close').click(function() {
        $('#commerce-pos-form-modal-overlay').hide();
      });
    }
  }

} (jQuery));
