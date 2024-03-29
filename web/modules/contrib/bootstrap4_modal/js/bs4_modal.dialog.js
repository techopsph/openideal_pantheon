/**
* DO NOT EDIT THIS FILE.
* See the following change record for more information,
* https://www.drupal.org/node/2815083
* @preserve
**/

(function ($, Drupal, drupalSettings) {
  drupalSettings.bs4_modal_dialog = {
    autoOpen: true,
    dialogClasses: '',
    dialogShowHeader: true,
    dialogShowHeaderTitle: true,
    buttonClass: 'btn',
    buttonPrimaryClass: 'btn-primary',
    close: function close(event) {
      Drupal.bs4_modal(event.target).close();
      Drupal.detachBehaviors(event.target, null, 'unload');
    }
  };

  Drupal.bs4_modal = function (element, options) {
    var undef = void 0;
    var $element = $(element);
    var dialog = {
      open: false,
      returnValue: undef
    };

    function openDialog(settings) {
      settings = $.extend({}, drupalSettings.bs4_modal_dialog, options, settings);

      $(window).trigger('dialog:beforecreate', [dialog, $element, settings]);

      if (settings.dialogClasses !== undefined) {
        $('.modal-dialog', $element).removeAttr('class').addClass('modal-dialog').addClass(settings.dialogClasses);
      }

      $($element)
        .attr('data-settings', JSON.stringify(settings));

      // The modal dialog header
      if (settingIsTrue(settings.dialogShowHeader)) {
        var modalHeader = '<div class="modal-header">';
        if (settingIsTrue(settings.dialogShowHeaderTitle)) {
          modalHeader += '<h5 class="modal-title">' + settings.title + '</h5>';
        }

        var version = 0;
        if ($.fn.modal !== undefined &&
            $.fn.modal.Constructor.VERSION) {
          version = parseInt($.fn.modal.Constructor.VERSION, 10);
        }
        if (version < 5) {
          modalHeader += '<button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>';
        } else {
          modalHeader += '<button type="button" class="close btn-close" data-bs-dismiss="modal" data-dismiss="modal" aria-label="Close"><span aria-hidden="true" class="visually-hidden">&times;</span></button>';
        }
        modalHeader += '</div>';
        $(modalHeader).prependTo($('.modal-dialog .modal-content', $element));
      }

      if (settingIsTrue(settings.drupalAutoButtons) && settings.buttons.length > 0) {
        updateButtons(settings.buttons);
      }

      if ($element.modal !== undefined) {
        $element.modal(settings);
        $element.modal('show');
      }
      // dialog.open = true;
      $(window).trigger('dialog:aftercreate', [dialog, $element, settings]);
    }

    function updateButtons(buttons) {
      var settings = $.extend({}, drupalSettings.bs4_modal_dialog, options);

      var modalFooter = $('<div class="modal-footer">');
      $.each(buttons, function () {
        var buttonObject = this;
        var classes = [ settings.buttonClass, settings.buttonPrimaryClass ];

        var button = $('<button type="button">');
        $(button)
          .addClass(buttonObject.class)
          .click(function (e) {
            buttonObject.click(e);
          })
          .html(buttonObject.text);

        if (!$(button).attr("class").match(/\bbtn-.*/)) {
          $(button)
            .addClass(classes.join(' '));
        }

        $(modalFooter).append(button);
      });
      if ($('.modal-dialog .modal-content .modal-footer', $element).length > 0) {
        $('.modal-dialog .modal-content .modal-footer', $element).remove();
      }
      $(modalFooter).appendTo($('.modal-dialog .modal-content', $element));
    }

    function closeDialog(value) {
      $(window).trigger('dialog:beforeclose', [dialog, $element]);
      if ($element.modal !== undefined) {
        $element.modal('hide');
      }
      dialog.returnValue = value;
      dialog.open = false;
      $(window).trigger('dialog:afterclose', [dialog, $element]);
    }

    function settingIsTrue(setting) {
      return (setting !== undefined && (setting === true || setting === 'true'))
    }

    dialog.updateButtons = function (buttons) {
      updateButtons(buttons);
    };

    dialog.show = function () {
      openDialog({ modal: false });
    };
    dialog.showModal = function () {
      openDialog({ modal: true });
    };
    dialog.close = closeDialog;

    $element.on('hide.bs.modal', function (e) {
      $(window).trigger('dialog:beforeclose', [dialog, $element]);
    });

    $element.on('hide.bs.modal', function (e) {
      $(window).trigger('dialog:afterclose', [dialog, $element]);
    });

    return dialog;
  };
})(jQuery, Drupal, drupalSettings);
