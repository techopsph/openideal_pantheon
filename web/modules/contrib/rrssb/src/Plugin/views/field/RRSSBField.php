<?php

namespace Drupal\rrssb\Plugin\views\field;

use Drupal\Core\Form\FormStateInterface;
use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\views\ResultRow;

/**
 * Field handler to show RRSSB Buttons.
 *
 * @ViewsField("rrssb_buttons")
 */
class RRSSBField extends FieldPluginBase {

  /**
   * {@inheritdoc}
   */
  public function query() {

  }

  /**
   * {@inheritdoc}
   */
  protected function defineOptions() {
    $options = parent::defineOptions();
    $options['button_set'] = ['default' => ''];

    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    $form['button_set'] = [
      '#type' => 'select',
      '#title' => $this->t('Button set'),
      '#options' => rrssb_button_set_names(),
      '#description' => $this->t('Select RRSSB button set to display.'),
      '#default_value' => $this->options['button_set'],
      '#required' => TRUE,
    ];

    parent::buildOptionsForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function render(ResultRow $values) {
    return rrssb_get_buttons($this->options['button_set'], $values->_entity);
  }

}
