(function ($) {

    Drupal.POS = Drupal.POS || {};

    Drupal.POS = function($button, $input, captureFocus) {
        this.$button = $button;
        this.$input = $input;
        this.inputFocused = false;

        var self = this;

        // Recapture focus after an ajax form submission.
        this.$input.focus(function() {
            self.inputFocused = true;
        }).blur(function() {
           setTimeout(function() {
                self.inputFocused = false;
           }, 100);
        });
        if(typeof Drupal.ajax[$button.attr('id')] != 'undefined') {
            Drupal.ajax[$button.attr('id')].beforeSubmit = function() {
                Drupal.settings.posCaptureFocus = self.inputFocused;
            };
        }


        if(captureFocus) {
            $input.focus();
        }
    }
    Drupal.POS.prototype = {
        submit: function() {
            this.$button.click();
        },
        input: function(value) {
            this.$input.val(value);
        },
        replacePattern: function(pattern) {
            this.$input.val(pattern.toString().replace('%s', this.$input.val()));
        }
    }
    Drupal.POS.captureFocus = false;

    Drupal.behaviors.POS = {
        attach: function (context, settings) {
            $('form.pos-input-form', context).once('ajax-pos', function() {
                var $button = $('input[type="submit"]', this);
                var $input = $('input[name="input"]', this);
                Drupal.POS.instance = new Drupal.POS($button, $input, settings.posCaptureFocus || false);
            });

            $('.pos-button', context).once('pos-button', function() {
                var $button = $(this);
                var pattern;
                if(Drupal.POS.instance) $button.addClass('overlay-exclude');

                $button.click(function(e) {
                    if(Drupal.POS.instance) {
                        if (pattern = $(this).data('pos-input')) {
                            Drupal.POS.instance.replacePattern(pattern);
                            if ($(this).data('pos-submit')) {
                                Drupal.POS.instance.submit();
                            }
                            if(typeof Drupal.CTools.Modal != 'undefined') {
                                Drupal.CTools.Modal.dismiss();
                            }
                        }
                        e.preventDefault();
                    }
                });

                if($button.hasClass('pos-button-add_product')) {
                    var original_input = $button.attr('data-pos-input');
                    var $qty = $('<input type="text" class="quantity" size="2" title = "' + Drupal.t('Enter a quantity') + '" value="1" />');
                    $button.append($qty).addClass('has-quantity');
                    $qty
                        .click(function(e) {
                            // Do not activate link:
                            e.preventDefault();
                            e.stopPropagation();
                        })
                        .change(function(e) {
                            var command = $qty.val() + '*' + original_input;
                            $button.querystring('href', {command: command});
                            $button.data('pos-input', command);
                        });
                }
            });

            $('.pos-print', context).once('jq-print', function () {
                $(this).jqprint();
            });
        },
        detach: function(context) {
            // Destroy the POS so we never end up with Drupal.POS.instance
            // if it isn't actually there.
            $('form.pos-input-form.ajax-pos-processed', context).each(function() {
               Drupal.POS.instance = null;
            });
        }
    }

    Drupal.ajax.prototype.commands.printReceipt = function (ajax, response, status) {
        $(response.content).jqprint();
    }
})(jQuery);