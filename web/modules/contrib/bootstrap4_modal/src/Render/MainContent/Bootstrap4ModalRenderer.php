<?php

namespace Drupal\bootstrap4_modal\Render\MainContent;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\bootstrap4_modal\Ajax\OpenBootstrap4ModalDialogCommand;
use Drupal\Core\Routing\RouteMatchInterface;
use Symfony\Component\HttpFoundation\Request;
use Drupal\Core\Render\MainContent\DialogRenderer;

/**
 * Default main content renderer for modal dialog requests.
 */
class Bootstrap4ModalRenderer extends DialogRenderer {

  /**
   * {@inheritdoc}
   */
  public function renderResponse(array $main_content, Request $request, RouteMatchInterface $route_match) {
    $response = new AjaxResponse();

    // First render the main content, because it might provide a title.
    $content = $this->renderer->renderRoot($main_content);

    // Attach the library necessary for using the OpenModalDialogCommand and set
    // the attachments for this Ajax response.
    $main_content['#attached']['library'][] = 'bootstrap4_modal/bs4_modal.dialog';
    $main_content['#attached']['library'][] = 'bootstrap4_modal/bs4_modal.dialog.ajax';
    $response->setAttachments($main_content['#attached']);

    // If the main content doesn't provide a title, use the title resolver.
    $title = isset($main_content['#title']) ? $main_content['#title'] : $this->titleResolver->getTitle($request, $route_match->getRouteObject());

    if (is_array($title)) {
      $title = $this->renderer->renderPlain($title);
    }

    // Determine the title: use the title provided by the main content if any,
    // otherwise get it from the routing information.
    $options = $request->request->get('dialogOptions', []);

    $response->addCommand(new OpenBootstrap4ModalDialogCommand($title, $content, $options));
    return $response;
  }

}
