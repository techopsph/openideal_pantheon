<?php

namespace Drupal\ckeditor_mentions\Plugin\CKEditorPlugin;

use Drupal\ckeditor\CKEditorPluginBase;
use Drupal\editor\Entity\Editor;

/**
 * Defines the "mentions" plugin.
 *
 * @CKEditorPlugin(
 *   id = "ajax",
 *   label = @Translation("Ajax"),
 *   module = "ckeditor_mention"
 * )
 */
class Ajax extends CKEditorPluginBase {

  /**
   * {@inheritdoc}
   */
  public function getFile() {
    return 'libraries/ckeditor/plugins/ajax/plugin.js';
  }

  /**
   * {@inheritDoc}
   */
  public function getConfig(Editor $editor) {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function getDependencies(Editor $editor) {
    return ['xml'];
  }

  /**
   * {@inheritdoc}
   */
  public function getButtons() {
    return [];
  }

}
