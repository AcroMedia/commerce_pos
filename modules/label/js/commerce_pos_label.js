(function ($) {
  Drupal.commercePosLabel = Drupal.commercePosLabel || {};

  Drupal.commercePosLabel.autocompleteCallback = function(sku) {
    $('.commerce-pos-product-autocomplete').val(sku);
    $('.commerce-pos-label-btn-add').mousedown();
  };

  if (Drupal.ajax) {
    Drupal.ajax.prototype.commands.printLabels = function (ajax, response, status) {
      $(response.content).print({
        globalStyles: false,
        mediaPrint: true,
        stylesheet: response.cssUrl
      });
    };
  }
}(jQuery));

