/**
 * @file entity_browser.bootstrap4_modal_selection.js
 *
 * Propagates selected entities from modal display.
 */

(function (drupalSettings) {

  'use strict';

  // We need to access parent window, get it's jquery and find correct modal
  // element to trigger event on.
  parent.jQuery(parent.document)
    .find(':input[data-uuid*=' + drupalSettings.entity_browser.bootstrap4_modal.uuid + ']')
    .trigger('entities-selected', [drupalSettings.entity_browser.bootstrap4_modal.uuid, drupalSettings.entity_browser.bootstrap4_modal.entities])
    .unbind('entities-selected').show();

  // This is a silly solution, but works fo now. We should close the modal
  // via ajax commands.
  parent.jQuery(parent.document).find('.entity-browser-modal-iframe').parents('.modal').eq(0).modal('hide');

}(drupalSettings));
