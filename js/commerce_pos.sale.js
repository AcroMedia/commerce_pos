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
        });
      });

      $('body', context).each(function () {
        $(this).once('commerce-pos-keybindings')
      });

      if (settings.commercePosSale.focusProductInput) {
        $('.commerce-pos-product-search').focus();
      }
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
          source: settings.commercePosSale.productAutoCompleteUrl
        });

        // Override the default UI autocomplete render function.
        element.data('ui-autocomplete')._renderItem = function (ul, item) {
          var innerHtml = $('<div><span>' + item.title + '</span></div>');
          var addButton = $('<a href="#" data-product-sku="' + item.sku + '">Add</a>');
          addButton.click(function (e) {

            addProductSku($(this).attr('data-product-sku'));
            e.preventDefault();
          });

          innerHtml.find('> span').append(addButton);

          return $("<li></li>")
            .data("item.autocomplete", item)
            .append(innerHtml)
            .appendTo(ul);
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
