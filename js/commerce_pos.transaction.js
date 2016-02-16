(function ($) {
  Drupal.behaviors.commercePosSale = {
    attach: function (context, settings) {
      setupProductAutocomplete(context, settings);

      $('.thevault-pos-hidden-element', context).each(function() {
        var element = $(this);
        var elementLabel = element.siblings('label');

        element.click(function() {
          element.show();
          elementLabel.hide();
          element.focus();
        });
      });

      $('body', context).each(function () {
        $(this).once('commerce-pos-keybindings')
      });

      if (settings.commercePosSale.focusProductInput) {
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

      $('.commerce-pos-remove-payment', context).click(function(event){
        event.preventDefault();

        $('.commerce-pos-remove-payment-input').val($(this).data('transaction-id'));
        $('.commerce-pos-remove-payment').trigger('remove_payment');
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
            addProductSku(ui.item.element.find('.btn-add').attr('data-product-sku'));
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

              addProductSku($(this).attr('data-product-sku'));
              element.data('ui-autocomplete').close();

          });

          return product;
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

} (jQuery));
