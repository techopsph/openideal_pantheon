<?php

namespace Drupal\rrssb\Plugin\Block;

use Drupal\Core\Render\Element;

/**
 * Provides a 'RRSSB demo' block.
 *
 * Extend from RRSSBBlock to inherit the block form.
 *
 * @Block(
 *   id = "rrssb_demo_block",
 *   admin_label = @Translation("RRSSB Demo"),
 *   category = @Translation("RRSSB")
 * )
 */
class RRSSBDemoBlock extends RRSSBBlock {

  /**
   * {@inheritdoc}
   */
  public function build() {
    // Start with config values from the specified button set.
    $config = $this->getConfiguration();
    $button_set = \Drupal::entityTypeManager()->getStorage('rrssb_button_set')->load($config['button_set']);

    // Take the relevant parts from the configuration form.
    $form = \Drupal::service('entity.form_builder')->getForm($button_set, "edit");

    foreach (Element::children($form) as $key) {
      if ($key != 'appearance' && $key != 'actions') {
        unset($form[$key]);
      }
    }

    unset($form['#submit']);
    unset($form['actions']['delete']);
    unset($form['actions']['submit']['#submit']);
    $form['actions']['submit']['#value'] = $this->t('Update');
    $form['#attributes']['class'][] = 'rrssb-control';
    $form['#attached']['library'][] = 'rrssb/demo';
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function label() {
    return $this->t('RRSSB demo form');
  }

}
