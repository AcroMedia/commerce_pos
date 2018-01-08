(function ($, Drupal, drupalSettings) {

  /**
   * Ajax command to set the toolbar subtrees.
   *
   * @param {Drupal.Ajax} ajax
   *   {@link Drupal.Ajax} object created by {@link Drupal.ajax}.
   * @param {object} response
   *   JSON response from the Ajax request.
   * @param {number} [status]
   *   XMLHttpRequest status.
   */
  Drupal.AjaxCommands.prototype.completeOrder = function (ajax, response, status) {
    // Just click the button to complete the order.
    $('.commerce-pos-receipt-button-done').click();
  };

  /**
   * Ajax command to set the toolbar subtrees.
   *
   * @param {Drupal.Ajax} ajax
   *   {@link Drupal.Ajax} object created by {@link Drupal.ajax}.
   * @param {object} response
   *   JSON response from the Ajax request.
   * @param {number} [status]
   *   XMLHttpRequest status.
   */
  Drupal.AjaxCommands.prototype.printReceipt = function (ajax, response, status) {
    $(response.content).print({
      globalStyles: false,
      stylesheet: drupalSettings.commercePosReceipt.cssUrl,
      deferred: $.Deferred().done(function(){
        // Sloppy, should probaby be nice and scalable, but that might never be needed
        $('.commerce-pos-receipt-button-done').click();
      })
    });
  };

  /**
   * Catch the submit before it submits, so we can popup the print dialog first,
   * then we trigger the submit for real once we've finished the print.
   */
  var printed = false;
  Drupal.behaviors.catchSubmits = {
    attach: function (context, settings) {
      $('.commerce-pos-receipt-button', context).click( function (e) {
        // Normally you would use .once() here, but it applies at the end,
        // and because of the chaining nature of our ajax, it doesn't fire until it is too late
        // and we've clicked the button again and we're in a loop
        $(this).addClass('commerce-pos-receipt-button-done');
        $(this).removeClass('commerce-pos-receipt-button');
        $(this).off();

        e.preventDefault();

        // Don't fire this again, if we've already done it.
        if (printed) return;

        $.ajax(
          {
            url: "/admin/commerce/pos/" + drupalSettings.commercePosReceipt.orderId + '/ajax-receipt/' + $('input[name="actions[print_email_receipt]"]:checked').val(),
            success: function (data) {
              var ajaxObject = Drupal.ajax(
                {
                  url: "",
                  base: false,
                  element: false,
                  progress: false
                });

              // Then, simulate an AJAX response having arrived, and let the
              // Ajax system handle it.
              ajaxObject.success(data, "success");
            }
          });

          printed = true;
      });
    }
  }

}(jQuery, Drupal, drupalSettings));
