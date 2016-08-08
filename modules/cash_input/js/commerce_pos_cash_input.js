(function ($) {
  var inputBox = null;

  Drupal.behaviors.commercePosCashInput = {
    attach: function (context, settings) {
      if (Drupal.settings.commercePosCashInput.inputBox) {
        $('body').once('commerce-pos-cash-input', function() {
          inputBox = new CashInputBox();
        });
      }

      $('.commerce-pos-cash-input', context).each(function() {
        var _this = $(this);
        _this.once('commerce-pos-cash-input-processed', function () {
          _this.after('<div class="commerce-pos-cash-input-icon"></div>');

          _this.siblings('.commerce-pos-cash-input-icon').click(function () {
            inputBox.setTextInput(_this);
            inputBox.show();
          });
        });
      });
    }
  };

  var CashInputBox = function() {
    this.currencyCode = '';
    this.inputValues = {};
    this.construct();
  };

  CashInputBox.prototype.setTextInput = function(textInput) {
    this.textInput = textInput;
  };

  CashInputBox.prototype.show = function() {
    var inputValues = this.textInput.data('inputValues');

    this.inputBox.find('.amount-input').each(function() {
      var amountInput = $(this);
      var value = 0;
      var cashType = amountInput.attr('data-cash-type');

      if (inputValues && typeof inputValues[cashType] !== 'undefined') {
        value = inputValues[cashType];
      }

      amountInput.val(value).trigger('keyup');
    });

    this.inputBox.show();
  };

  CashInputBox.prototype.hide = function() {
    this.inputBox.hide();
    this.textInput.data('inputValues', JSON.parse(JSON.stringify(this.inputValues)));
  };

  CashInputBox.prototype.formatAmount = function(amount, currencyCode) {
    var formattedAmount = amount;

    if (Drupal.CommercePosReport && Drupal.CommercePosReport.currencyFormat) {
      formattedAmount = Drupal.CommercePosReport.currencyFormat(formattedAmount, currencyCode);
    }

    return formattedAmount;
  };

  CashInputBox.prototype.calculateTotal = function(currencyCode) {
    var total = 0;

    this.inputBox.find('.amount-output').each(function() {
      var cents = $(this).data('cents');

      if (!isNaN(cents)) {
        total += cents;
      }
    });

    var formattedTotal = this.formatAmount(total, currencyCode);
    this.totalInput.data('total', total);
    this.totalInput.html(formattedTotal);
  };

  CashInputBox.prototype.addTotal = function() {
    if (this.textInput && !this.textInput.is(':disabled')) {
      var total = this.totalInput.data('total');

      if (Drupal.CommercePosReport && Drupal.CommercePosReport.currencyRound) {
        total = Drupal.CommercePosReport.currencyRound(total, this.currencyCode, true);
      }
      else {
        total = total / 100;
      }

      this.textInput.val(total);
    }
    this.hide();
  };

  CashInputBox.prototype.construct = function() {
    $('body').prepend(Drupal.settings.commercePosCashInput.inputBox);

    this.inputBox = $('#commerce-pos-cash-input-box');
    this.totalInput = this.inputBox.find('.input-total');

    var self = this;

    this.inputBox.find('.amount-input').each(function() {
      var _this = $(this);
      var outputBox = self.inputBox.find('.amount-output[data-cash-type="' + _this.attr('data-cash-type') + '"]');
      _this.data('outputBox', outputBox);

      if (self.currencyCode == '') {
        self.currencyCode = _this.attr('data-currency-code');
      }

      _this.keyup(function() {
        var _this = $(this);
        var amount = _this.attr('data-amount');
        var value = _this.val();

        if (!isNaN(value)) {
          var inputVal = _this.val();
          var cents = amount * inputVal;
          var outputBox = _this.data('outputBox');
          var currencyCode = _this.attr('data-currency-code');
          var formattedAmount = self.formatAmount(cents, currencyCode);

          outputBox
            .val(formattedAmount)
            .data('cents', cents);

          self.calculateTotal(currencyCode);
          self.inputValues[_this.attr('data-cash-type')] = inputVal;
        }
      });
    });

    this.inputBox.once('bindEvents', function() {
      $(this).find('.add-total').click(function(e) {
        self.addTotal();
        e.preventDefault();
        return false;
      });

      $(this).find('.cancel-total').click(function(e) {
        self.hide();
        e.preventDefault();
        return false;
      });
    });
  };

}(jQuery));
