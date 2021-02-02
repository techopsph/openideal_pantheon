+(function(window, $, undefined) {
  'use strict';

  $(document).ready(function() {
    $('.rrssb-control').submit(function () {
      var settings = {};
      $(':input', this).each(function() {
        var match = this.name.match(/appearance\[(.*)\]/);
        if (match) {
          if (this.type == 'checkbox') {
            settings[match[1]] = $(this).is(":checked");
          }
          else {
            settings[match[1]] = $(this).val();
          }
        }
      });
      // Reset padding as the library can't handle alignment changing without a page load.
      $('.rrssb').css('padding-left', '');
      $('.rrssb').css('padding-right', '');
      rrssbConfigAll(settings);
      return false;
    });
  });
})(window, jQuery);
