(function ($) {
  Drupal.behaviors.commercePosReportJournalRole = {
    attach: function (context, settings) {
      $('.commerce-pos-report-journal-role-order', context).click(function() {
        var self = $(this);
        self.unbind('click');
        self.closest('tr').toggleClass('commerce-pos-report-opened');

        self.click(function(e) {
          var self = $(this);
          var orderId = self.attr('data-order-id');
          self.closest('tr').toggleClass('commerce-pos-report-opened');
          $('td.commerce-pos-report-journal-role-order-info[data-order-id=' + orderId + ']').toggleClass('element-invisible');
          e.preventDefault();
          return false;
        });
      });

      $('.commerce-pos-report-journal-role-filter', context).each(function() {
        $(this).change(function() {
          $('.commerce-pos-report-journal-role-submit').click();
        });
      });
    }
  };
}(jQuery));
