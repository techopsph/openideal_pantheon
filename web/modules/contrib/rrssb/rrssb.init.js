/**
 * @file
 * The RRSSB Drupal Behavior to configure settings.
 */

(function ($) {
  'use strict';

  Drupal.behaviors.rrssb = {
    attach: function (context, settings) {
      for (var buttonSet in settings.rrssb) {
        $('.rrssb-bs-' + buttonSet).each(function(){
          $(this).rrssbConfig(settings.rrssb[buttonSet]);
        });
      }
    }
  };
})(jQuery);
