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
            this.$input.val(pattern.replace('%s', this.$input.val()));
        }
    }
    Drupal.POS.captureFocus = false;

    Drupal.behaviors.POS = {
        attach: function (context, settings) {
            var $input,
                $button;
            $('form.pos-input-form', context).once('ajax-pos', function() {
                $button = $('input[type="submit"]', this);
                $input = $('input[name="input"]', this);
                Drupal.POS.instance = new Drupal.POS($button, $input, settings.posCaptureFocus || false);
            });
            if(Drupal.POS.instance) {
                $('.pos-button', context).once('pos-button').addClass('overlay-exclude').click(function (e) {
                    if(Drupal.POS.instance) {
                        if (pattern = $(this).data('pos-input')) {
                            Drupal.POS.instance.replacePattern(pattern);
                            if ($(this).data('pos-submit')) {
                                Drupal.POS.instance.submit();
                            }
                        }
                        e.preventDefault();
                    }
                    var pattern, input;
                    if (pattern = $(this).data('pos-input')) {
                        var $form = $('#pos-pane-input form');
                        input = $('input[name="input"]', $form);
                        input.val(pattern.replace('%s', input.val()));
                        if ($(this).data('pos-submit')) {
                            $('input[type="submit"]', $form).trigger('click');
                        }
                        e.preventDefault();
                    }
                });
            }

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