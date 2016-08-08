(function ($) {
  Drupal.behaviors.commercePosReport = {
    attach: function (context, settings) {
      $('.commerce-pos-report-declared-input', context).once('commerce-pos-bind-keydown', function () {
        var element = $(this);
        var paymentMethodId = element.data('payment-method-id');
        element.data('balance-area',  $('.commerce-pos-report-balance').filter('[data-payment-method-id=' + paymentMethodId + ']'));

        element.keyup(function (e) {
          Drupal.CommercePosReport.calculateDeclared($(this));
        });

        // Calculate the declared amount once upon page load.
        Drupal.CommercePosReport.calculateDeclared($(this));
      });
    }
  };

  Drupal.ajax.prototype.commands.printWindow = function (ajax, response, status) {
    $(response.content).print({
      globalStyles: false,
      stylesheet: Drupal.settings.commercePosReport.cssUrl
    });
  };

  Drupal.CommercePosReport = {};

  Drupal.CommercePosReport.calculateDeclared = function (element) {
    var value = element.val();

    if (!isNaN(value)) {
      var balance = (value * 100) - element.data('expected-amount');
      var balanceArea = element.data('balance-area');

      balanceArea.html(
        Drupal.CommercePosReport.currencyFormat(balance, element.data('currency-code'))
      );

      if (balance < 0) {
        balanceArea.addClass('commerce-pos-report-negative');
      }
      else {
        balanceArea.removeClass('commerce-pos-report-negative');
      }
    }
  };

  Drupal.CommercePosReport.currencyFormat = function (amount, currencyCode, convert) {
    var currency = Drupal.settings.commercePosReport.currencies[currencyCode];

    if (typeof convert === 'undefined') {
      convert = true;
    }

    if (convert) {
      amount = amount / currency['divisor'];
    }

    var price = Drupal.CommercePosReport.currencyRound(Math.abs(amount), currency);

    var replacements = {
      '@code_before': currency['code_placement'] == 'before' ? currency['code'] : '',
      '@symbol_before': currency['symbol_placement'] == 'before' ? currency['symbol'] : '',
      '@price': price,
      '@symbol_after': currency['symbol_placement'] == 'after' ? currency['symbol'] : '',
      '@code_after': currency['code_placement'] == 'after' ? currency['code'] : '',
      '@negative_before': amount < 0 ? '(' : '',
      '@negative_after': amount < 0 ? ')' : '',
      '@symbol_spacer': currency['symbol_spacer'],
      '@code_spacer': currency['code_spacer'],
      '@code_spacer2': currency['code_spacer']
    };

    return Drupal.t('@code_before@code_spacer@negative_before@symbol_before@price@negative_after@symbol_spacer@symbol_after@code_spacer2@code_after', replacements);
  };

  Drupal.CommercePosReport.currencyRound = function (amount, currency, convert) {
    if (typeof convert === 'undefined') {
      convert = false;
    }

    if (typeof currency == 'string') {
      currency = Drupal.settings.commercePosReport.currencies[currency];
    }

    if (convert) {
      amount = amount / currency['divisor'];
    }

    if (!currency['rounding_step']) {
      return Drupal.CommercePosReport.round(amount, currency['decimals']).toFixed(currency['decimals']);
    }

    var modifier = 1 / currency['rounding_step'];

    return (Drupal.CommercePosReport.round(amount * modifier) / modifier).toFixed(currency['decimals']);
  };

  Drupal.CommercePosReport.round = function (value, precision, mode) {
    var m, f, isHalf, sgn; // helper variables
    // making sure precision is integer
    precision |= 0;
    m = Math.pow(10, precision);
    value *= m;
    // sign of the number
    sgn = (value > 0) | -(value < 0);
    isHalf = value % 1 === 0.5 * sgn;
    f = Math.floor(value);

    mode = mode || 'PHP_ROUND_HALF_UP';

    if (isHalf) {
      switch (mode) {
        case 'PHP_ROUND_HALF_DOWN':
          // rounds .5 toward zero
          value = f + (sgn < 0);
          break;
        case 'PHP_ROUND_HALF_EVEN':
          // rouds .5 towards the next even integer
          value = f + (f % 2 * sgn);
          break;
        case 'PHP_ROUND_HALF_ODD':
          // rounds .5 towards the next odd integer
          value = f + !(f % 2);
          break;
        default:
          // rounds .5 away from zero
          value = f + (sgn > 0);
      }
    }

    return (isHalf ? value : Math.round(value)) / m;
  }

} (jQuery));
