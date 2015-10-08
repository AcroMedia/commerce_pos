(function ($) {

  Drupal.behaviors.commercePosUpcScan = {
    attach: function (context, settings) {
      $('.commerce-pos-product-search', context).each(function () {
        var pressed = false;
        var chars = [];
        var element = $(this);

        element.keypress(function(e) {
          if (e.which >= 48 && e.which <= 57) {
            chars.push(String.fromCharCode(e.which));
          }
          /*console.log(e.which + ":" + chars.join("|"));*/
          if (pressed == false) {
            setTimeout(function(){
              if (chars.length >= 10) {
                var barcode = chars.join("");
                /*console.log("Barcode Scanned: " + barcode);*/
                element.val('');
                $('.commerce-pos-upc-scan-upc-field')
                  .val(barcode)
                  .trigger('blur');

              }
              chars = [];
              pressed = false;
            },500);
          }
          pressed = true;
        });
      });
    }
  };
} (jQuery));
