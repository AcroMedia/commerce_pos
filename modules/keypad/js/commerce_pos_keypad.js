(function ($) {
  var inputBox = null;

  Drupal.behaviors.commercePosKeypadKeypad = {
    attach: function (context, settings) {
      if (Drupal.settings.commercePosKeypadKeypad && Drupal.settings.commercePosKeypadKeypad.inputBox) {
        $('body').once('commerce-pos-keypad-keypad', function() {
          inputBox = new KeypadInputBox();
        });
      }

      $('.commerce-pos-keypad-keypad', context).each(function() {
        var _this = $(this);
        _this.once('commerce-pos-keypad-keypad-processed', function () {
          _this.after('<div class="commerce-pos-keypad-keypad-icon">&#9000;</div>');

          _this.siblings('.commerce-pos-keypad-keypad-icon').click(function () {
            inputBox.setTextInput(_this);
            inputBox.toggle();
          });
        });
      });
    }
  };

  var KeypadInputBox = function() {
    this.output = '';
    this.submitEvents = [];
    this.construct();
  };

  KeypadInputBox.prototype.setTextInput = function (element) {
    this.textInput = element;
  };

  KeypadInputBox.prototype.toggle = function toggle() {
    this.inputBox.toggle();
  };

  KeypadInputBox.prototype.show = function show() {
    this.inputBox.show();
  };

  KeypadInputBox.prototype.hide = function hide() {
    this.inputBox.hide();
  };

  KeypadInputBox.prototype.submit = function submit() {
    if (this.submitEvents) {
      this.textInput.val(this.output);

      this.submitEvents.forEach(function (eventInfo) {
        var e = jQuery.Event(eventInfo.name, eventInfo.properties);
        $(eventInfo.selector).trigger(e);
      });

      this.hide();
    }
  };

  KeypadInputBox.prototype.construct = function construct() {
    $('body').prepend(Drupal.settings.commercePosKeypadKeypad.inputBox);
    this.inputBox = $('#commerce-pos-keypad-keypad');
    // The element to update with the current value of what is entered by the
    // keypad.
    this.valueElement = this.inputBox.find('input');
    var self = this;

    $('.commerce-pos-keypad-close').on('click', function(e) {
      self.hide();
      e.preventDefault();
      return false;
    });

    if (Drupal.settings.commercePosKeypadKeypad.events) {
      Drupal.settings.commercePosKeypadKeypad.events.forEach(function (item) {
        self.submitEvents.push({selector: item.selector, name: item.name, properties: item.properties});
      });
    }

    this.inputBox.find('.commerce-pos-keypad-key').each(function() {
      var _this = $(this);
      _this.click(function() {
        var _this = $(this);

        if (_this.data('keybind')) {
          self.output += _this.data('keybind');
        }

        if (_this.data('key-action')) {
          switch (_this.data('key-action')) {
            case 'backspace':
              self.output = self.output.slice(0, -1);
              break;
            case 'submit':
              self.submit();
              break;
          }
        }

        self.updateValue();
      });
    });
  };

  KeypadInputBox.prototype.updateValue = function() {
    this.valueElement.val(this.output);
  };

  Drupal.behaviors.commercePosKeypadCashInput = {
    attach: function (context, settings) {
      if (Drupal.settings.commercePosKeypadCashInput && Drupal.settings.commercePosKeypadCashInput.inputBox) {
        $('body').once('commerce-pos-keypad-cash-input', function() {
          inputBox = new CashInputBox();
        });
      }

      $('.commerce-pos-keypad-cash-input', context).each(function() {
        var _this = $(this);
        _this.once('commerce-pos-keypad-cash-input-processed', function () {
          _this.after('<div class="commerce-pos-keypad-cash-input-icon"></div>');

          _this.siblings('.commerce-pos-keypad-cash-input-icon').click(function () {
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
    $('body').prepend(Drupal.settings.commercePosKeypadCashInput.inputBox);

    this.inputBox = $('#commerce-pos-keypad-cash-input-box');
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
