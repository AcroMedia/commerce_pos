(function ($, Drupal, drupalSettings) {
  var inputBox = null;

  Drupal.behaviors.commercePosKeypadKeypad = {
    attach: function (context, settings) {
      if (drupalSettings.commerce_pos_keypad.commerce_pos_keypad.commercePosKeypadKeypad && drupalSettings.commerce_pos_keypad.commerce_pos_keypad.commercePosKeypadKeypad.inputBox) {
        var counter = 0;
        // TODO this only supports 1 instance of inputBox properly, if we want to do different types on the same page it won't work right.
        $('.commerce-pos-keypad-keypad').once('commerce-pos-keypad-keypad').each(function () {
          var uniqueClass = 'commerce-pos-keypad-instance-' + counter;
          $(this).addClass(uniqueClass);
          $(this).select();
          inputBox = new KeypadInputBox("." + uniqueClass);
          counter++;
        });
      }

      // TODO this should actually be properly a unique setting per instance
      if (drupalSettings.commerce_pos_keypad.commerce_pos_keypad.commercePosKeypadIcon) {
        $('.commerce-pos-keypad-keypad').each(function () {
          var _this = $(this);
          _this.once('commerce-pos-keypad-keypad-processed').each(function () {
            inputBox.hide();
            _this.after('<div class="commerce-pos-keypad-keypad-icon">&#9000;</div>');

            _this.siblings('.commerce-pos-keypad-keypad-icon').click(function () {
              inputBox.setTextInput(_this);
              inputBox.toggle();
            });
          });
        });
      }
    }
  };

  var KeypadInputBox = function (uniqueIdentifier) {
    this.output = '';
    this.submitEvents = [];
    this.uniqueIdentifier = uniqueIdentifier;
    this.construct();
  };

  KeypadInputBox.prototype.setTextInput = function (element) {
    this.textInput = element;
  };

  KeypadInputBox.prototype.toggle = function toggle () {
    $(this.uniqueIdentifier).parent().find('.commerce-pos-keypad-keys').toggle();
  };

  KeypadInputBox.prototype.show = function show () {
    $(this.uniqueIdentifier).parent().find('.commerce-pos-keypad-keys').show();
  };

  KeypadInputBox.prototype.hide = function hide () {
    $(this.uniqueIdentifier).parent().find('.commerce-pos-keypad-keys').hide();
  };

  KeypadInputBox.prototype.submit = function submit () {
    if (this.submitEvents) {
      this.textInput.val(this.output);

      this.submitEvents.forEach(function (eventInfo) {
        var e = jQuery.Event(eventInfo.name, eventInfo.properties);
        $(eventInfo.selector).trigger(e);
      });

      this.hide();
    }
  };

  KeypadInputBox.prototype.construct = function construct () {
    $(this.uniqueIdentifier).after(drupalSettings.commerce_pos_keypad.commerce_pos_keypad.commercePosKeypadKeypad.inputBox);
    this.inputBox = $(this.uniqueIdentifier).parent();
    // The element to update with the current value of what is entered by the
    // keypad.
    this.valueElement = this.inputBox.find('input');
    var self = this;

    $('.commerce-pos-keypad-close').on('click', function (e) {
      self.hide();
      e.preventDefault();
      return false;
    });

    if (drupalSettings.commerce_pos_keypad.commerce_pos_keypad.commercePosKeypadKeypad.events) {
      drupalSettings.commerce_pos_keypad.commerce_pos_keypad.commercePosKeypadKeypad.events.forEach(function (item) {
        self.submitEvents.push({selector: item.selector, name: item.name, properties: item.properties});
      });
    }

    this.inputBox.find('.commerce-pos-keypad .commerce-pos-keypad-key').each(function (index) {
      $(this).on('click', function () {
        var _this = $(this);

        if (_this.data('keybind') !== undefined) {
          self.output += _this.data('keybind');
        }

        if (_this.data('key-action')) {
          switch (_this.data('key-action')) {
            case 'clear':
              self.output = "";
              break;
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

  KeypadInputBox.prototype.updateValue = function () {
    this.valueElement.val(this.output);
  };

  // CASH INPUT BOX.
  Drupal.behaviors.commercePosKeypadCashInput = {
    attach: function (context, settings) {
      if (drupalSettings.commerce_pos_keypad.commerce_pos_keypad.commercePosKeypadKeypad.commercePosKeypadCashInput && drupalSettings.commerce_pos_keypad.commerce_pos_keypad.commercePosKeypadKeypad.commercePosKeypadCashInput.inputBox) {
        $('body').once('commerce-pos-keypad-cash-input').each(function() {
          inputBox = new CashInputBox();
        });
      }

      $('.commerce-pos-keypad-cash-input', context).once('commerce-pos-keypad-cash-input-processed').each(function() {
        var _this = $(this);
        _this.after('<div class="commerce-pos-keypad-cash-input-icon"></div>');

        _this.siblings('.commerce-pos-keypad-cash-input-icon').click(function () {
          inputBox.setTextInput(_this);
          inputBox.show();
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
      this.textInput.trigger('change');
    }
    this.hide();
  };

  CashInputBox.prototype.construct = function() {
    $('body').prepend(drupalSettings.commerce_pos_keypad.commerce_pos_keypad.commercePosKeypadKeypad.commercePosKeypadCashInput.inputBox);

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

    this.inputBox.once('bindEvents').each(function() {
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

}(jQuery, Drupal, drupalSettings));
