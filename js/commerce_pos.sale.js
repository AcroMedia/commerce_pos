(function ($) {
  Drupal.behaviors.commercePosSale = {
    attach: function (context, settings) {
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

      $('body', context).each(function () {
        $(this).once('commerce-pos-keybindings')
      });

      // @TODO: not working on disabled buttons.
      $('.commerce-pos-btn-retrieve-transaction', context).each(function () {
        $(this).on('click mousedown', function(e) {
          if (settings.commercePosSale.hasActiveTransaction) {
            e.stopImmediatePropagation();
            e.preventDefault();
            alert(Drupal.t('Please park or void your current transaction before retrieving this transaction.'));
          }
        });
      });
    }
  };

  /**
   * Populates the product SKU field on the form and triggers its AJAX event.
   */
  var addProductSku = function (sku) {
    $('.commerce-pos-product-sku-input')
      .val(sku)
      .trigger('blur');
  }

} (jQuery));
