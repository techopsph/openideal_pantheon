/**
 * @file entity_browser.modal.js
 *
 * Defines the behavior of the entity browser's modal display.
 */

(function ($, Drupal, drupalSettings) {

  'use strict';

  Drupal.entityBrowserBS4Modal = {};

  Drupal.AjaxCommands.prototype.select_entities = function (ajax, response, status) {
    var uuid = drupalSettings.entity_browser.modal.uuid;

    $(':input[data-uuid="' + uuid + '"]').trigger('entities-selected', [uuid, response.entities])
      .removeClass('entity-browser-processed').unbind('entities-selected');
  };

  /**
   * Registers behaviours related to modal display.
   */
  Drupal.behaviors.entityBrowserBS4Modal = {
    attach: function (context) {
      _.each(drupalSettings.entity_browser.modal, function (instance) {
        _.each(instance.js_callbacks, function (callback) {
          // Get the callback.
          callback = callback.split('.');
          var fn = window;

          for (var j = 0; j < callback.length; j++) {
            fn = fn[callback[j]];
          }

          if (typeof fn === 'function') {
            $(':input[data-uuid="' + instance.uuid + '"]').not('.entity-browser-processed')
              .bind('entities-selected', fn).addClass('entity-browser-processed');
          }
        });
        if (instance.auto_open) {
          $('input[data-uuid="' + instance.uuid + '"]').click();
        }
      });
    }
  };

  /**
   * Registers behaviours related to modal open and windows resize for fluid modal.
   */
  Drupal.behaviors.bs4EntityBrowserModal = {
    attach: function (context) {
      var $window = $(window);
      var $document = $(document);

      // Be sure to run only once per window document.
      if ($document.once('fluid-modal').length === 0) {
        return;
      }

      // Recalculate dialog size on window resize.
      $window.resize(function (event) {
        Drupal.entityBrowserBS4Modal.fluidDialog();
      });

      // Catch dialog if opened within a viewport smaller than the dialog width
      // and recalculate size of all open dialogs.
      $('.bs4-modal').on('shown.bs.modal', function (event, ui) {
        Drupal.entityBrowserBS4Modal.fluidDialog();
      });

      // Disable scrolling of the whole browser window to not interfere with the
      // iframe scrollbar.
      $window.on({
        'dialog:aftercreate': function (event, dialog, $element, settings) {
          $('body').css({overflow: 'hidden'});
        },
        'dialog:beforeclose': function (event, dialog, $element) {
          $('body').css({overflow: 'inherit'});
        }
      });
    }
  };

  /**
   * Recalculates size of the modal.
   */
  Drupal.entityBrowserBS4Modal.fluidDialog = function () {

    var $visible = $('.bs4-modal:visible');
    // For each open dialog.
    $visible.each(function () {
      var $this = $(this);
      var dialog = $this.find('.modal-content').closest('.bs4-modal').data('settings');
      // If fluid option == true.
      var vHeight = $(window).height();
      if (dialog.options.fluid) {
        var contentHeight = .80 * vHeight;
        $this.find('iframe').css('height', contentHeight);
      }
    });
  };

}(jQuery, Drupal, drupalSettings));
