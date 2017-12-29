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
  Drupal.AjaxCommands.prototype.printLabels = function (ajax, response, status) {
    $(response.content).print({
      globalStyles: false,
      stylesheet: response.cssUrl
    });
  };

}(jQuery, Drupal, drupalSettings));
