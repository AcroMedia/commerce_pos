/**
 * @file
 *   Client-side functionality for commerce_pos_terminal.
 */

(function($) {
  var pinging = false;


  // Query the server
  Drupal.behaviors.commercePosTerminalPing = {
    attach: function(context, settings) {
      if (settings.commercePosTerminalPing) {
        var $container = $(settings.commercePosTerminalPing.containerId);
        var timeout = settings.commercePosTerminalPing.pingTimer;
        var url = settings.commercePosTerminalPing.pingUrl;
        var reloadButtonId = settings.commercePosTerminalPing.reloadButtonId;

        // Do not start more timers if one is already active.
        if (!pinging) {
          pinging = true;
          commercePosTerminalPing(timeout, url, reloadButtonId, $container);
        }
      }
    }
  };

  /**
   * Ping the server to see if there are pending transactions. If so, wait for
   * another timeout and try again. If not, trigger a reload of the form.
   *
   * @param timeout
   * @param url
   * @param reloadButtonId
   * @param $container
   *   A jQuery object for the element containing the overlay actions.
   */
  function commercePosTerminalPing(timeout, url, reloadButtonId, $container) {
    window.setTimeout(function () {
      $.get(url, null, function(response) {
        if (response.pending_transactions) {
          commercePosTerminalPing(timeout, url, reloadButtonId, $container);
        }
        else {
          pinging = false;
          $('#' + reloadButtonId).click();
        }
      });
    }, timeout)
  }
})(jQuery);
