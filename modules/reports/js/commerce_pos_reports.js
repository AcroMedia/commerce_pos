(function ($) {
  Drupal.behaviors.commercePosReport = {
    attach: function (context, settings) {
      $(context).find('input.commerce-pos-report-declared-input').once('addOnChange').each(function () {
        var _this = $(this);
        _this.on('change', function () {
          Drupal.CommercePosReport.calculateCashDeposit(_this);
          Drupal.CommercePosReport.calculateReportBalance(_this);
        });
      });
    }
  };

  Drupal.AjaxCommands.prototype.printWindow = function (ajax, response, status) {
    $(response.content).print({
      globalStyles: false,
      stylesheet: drupalSettings.commercePosReports.cssUrl
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

  Drupal.CommercePosReport.calculateCashDeposit = function (element) {
    var depositArea = element.closest('tr').find('.commerce-pos-report-deposit');
    var value = (element.val() * 100 - element.data('default-float') * 100) / 100;

    if (value < 0) {
      value = 0;
    }

    depositArea.val(value.toFixed(2));
  };

  Drupal.CommercePosReport.currencyFormat = function (amount, currencyCode, convert, numberOnly) {
    var currency_array = drupalSettings.commercePosReportCurrencies[currencyCode];
    var currency = currency_array['currency'];

    if (typeof convert === 'undefined') {
      convert = true;
    }

    if (convert) {
      amount = amount / currency_array['divisor'];
    }

    var price = Drupal.CommercePosReport.currencyRound(Math.abs(amount), currency_array);

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

  Drupal.CommercePosReport.currencyRound = function (amount, currency_array, convert) {
    if (typeof convert === 'undefined') {
      convert = false;
    }

    if (typeof currency_array == 'string') {
      currency_array = drupalSettings.commercePosReportCurrencies[currency_array];
    }

    if (convert) {
      amount = amount / currency_array['divisor'];
    }

    if (!currency_array['currency']['rounding_step']) {
      return Drupal.CommercePosReport.round(amount, currency_array['currency']['decimals']).toFixed(currency_array['currency']['decimals']);
    }

    var modifier = 1 / currency_array['currency']['rounding_step'];

    return (Drupal.CommercePosReport.round(amount * modifier) / modifier).toFixed(currency_array['currency']['decimals']);
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
