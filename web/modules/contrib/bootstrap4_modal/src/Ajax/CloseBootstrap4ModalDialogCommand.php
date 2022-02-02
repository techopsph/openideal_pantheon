<?php

namespace Drupal\bootstrap4_modal\Ajax;

use Drupal\Core\Ajax\CloseModalDialogCommand;

/**
 * Defines an AJAX command that closes the currently visible modal dialog.
 *
 * @ingroup ajax
 */
class CloseBootstrap4ModalDialogCommand extends CloseModalDialogCommand {

  /**
   * Constructs a CloseModalDialogCommand object.
   *
   * @param string $selector
   *   A CSS selector string of the dialog to close.
   * @param bool $persist
   *   (optional) Whether to persist the dialog in the DOM or not.
   */
  public function __construct($selector = NULL, $persist = FALSE) {
    $this->selector = $selector ?? '#drupal-bootstrap4-modal';
    $this->persist = $persist;
  }

  /**
   * {@inheritdoc}
   */
  public function render() {
    return [
      'command' => 'closeBootstrap4Dialog',
      'selector' => $this->selector,
      'persist' => $this->persist,
    ];
  }

}
