(function ($, Drupal, drupalSettings) {
  'use strict';

  Drupal.behaviors.CommercePosCashierLogin = {
    attach: function (context, settings) {

      $('.commerce-pos-login__pane__toggle').click(function (e) {
        $(this).parents('.commerce-pos-login__pane').addClass('is-active').siblings().removeClass('is-active');
        e.preventDefault();
      });

      $('.commerce-pos-login__users-list__user').click(function (e) {
        $('.commerce-pos-login__pane--login').addClass('is-active')
        $('.commerce-pos-login__pane--users').removeClass('is-active');

        $("input[name='name']").val($(this).html());

        e.preventDefault();
      });

    }
  };
}(jQuery, Drupal, drupalSettings));
