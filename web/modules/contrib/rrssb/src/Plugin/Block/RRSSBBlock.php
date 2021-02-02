<?php

namespace Drupal\rrssb\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a 'RRSSB form' block.
 *
 * @Block(
 *   id = "rrssb_block",
 *   admin_label = @Translation("Ridiculously Responsive Social Share Buttons"),
 *   category = @Translation("RRSSB")
 * )
 */
class RRSSBBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    $config = $this->getConfiguration();
    return rrssb_get_buttons($config['button_set'], \Drupal::routeMatch()->getParameter('node'), 'url.path');
  }

  /**
   * {@inheritdoc}
   */
  public function label() {
    return $this->t('Share this content');
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $form = parent::blockForm($form, $form_state);
    $config = $this->getConfiguration();

    $form['button_set'] = [
      '#type' => 'select',
      '#title' => $this->t('Button set'),
      '#options' => rrssb_button_set_names(),
      '#description' => $this->t('Select RRSSB button set to display.'),
      '#default_value' => isset($config['button_set']) ? $config['button_set'] : '',
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    parent::blockSubmit($form, $form_state);
    $values = $form_state->getValues();
    $this->configuration['button_set'] = $values['button_set'];
  }

}
