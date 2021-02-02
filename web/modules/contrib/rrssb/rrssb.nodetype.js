(function ($, Drupal) {
  'use strict';

  Drupal.behaviors.rrssbSettingsSummary = {
    attach: function (context) {
      var $context = $(context);
      $context.find('#edit-rrssb').drupalSetSummary(function (context) {
        var vals = [];

        // Inclusion select field.
        vals.push($(context).find('#edit-button-set option:selected').text());

        return vals.join(', ');
      });
    }
  };

})(jQuery, Drupal);
