<?php

namespace Drupal\avatars_test\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * Avatar Kit test controller.
 */
class AvatarKitTestController extends ControllerBase {

  /**
   * Return an image for testing avatar generators.
   */
  public function image() {
    $headers = ['Content-Type' => 'image/png'];
    $file = drupal_get_path('core', '') . '/misc/druplicon.png';
    return new BinaryFileResponse($file, 200, $headers);
  }

}
