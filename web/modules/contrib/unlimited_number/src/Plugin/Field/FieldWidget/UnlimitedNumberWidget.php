<?php

namespace Drupal\unlimited_number\Plugin\Field\FieldWidget;

use Drupal\Core\Field\Plugin\Field\FieldWidget\NumberWidget;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\unlimited_number\Element\UnlimitedNumber;

/**
 * Plugin implementation of the 'unlimited_number' widget.
 *
 * @FieldWidget(
 *   id = "unlimited_number",
 *   label = @Translation("Unlimited or Number"),
 *   field_types = {
 *     "integer",
 *   }
 * )
 */
class UnlimitedNumberWidget extends NumberWidget {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'value_unlimited' => 0,
      'label_unlimited' => t('Unlimited'),
      'label_number' => t('Limited'),
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $element = parent::settingsForm($form, $form_state);

    $element['value_unlimited'] = [
      '#type' => 'number',
      '#title' => $this->t('Unlimited value'),
      '#default_value' => $this->getSetting('value_unlimited'),
      '#description' => $this->t('Internal number to use for unlimited.'),
    ];

    $element['label_unlimited'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Unlimited Label'),
      '#default_value' => $this->getSetting('label_unlimited'),
      '#description' => $this->t('Text that will be used for the unlimited radio.'),
    ];

    $element['label_number'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Number Label'),
      '#default_value' => $this->getSetting('label_number'),
      '#description' => $this->t('Text that will be used for the number radio.'),
    ];

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $field_settings = $this->getFieldSettings();
    $value_unlimited = $this->getUnlimitedValue();

    $value = isset($items[$delta]->value) ? $items[$delta]->value : NULL;
    if (isset($value)) {
      $default_value = $value == $value_unlimited ? UnlimitedNumber::UNLIMITED : $value;
    }
    else {
      $default_value = NULL;
    }

    $form_element['unlimited_number'] = $element + [
      '#type' => 'unlimited_number',
      '#default_value' => $default_value,
      '#min' => is_numeric($field_settings['min']) ? $field_settings['min'] : 1,
      '#max' => is_numeric($field_settings['max']) ? $field_settings['max'] : NULL,
      '#options' => [
        'unlimited' => $this->getSetting('label_unlimited'),
        'limited' => $this->getSetting('label_number'),
      ],
      '#parents' => [$items->getName(), $delta, 'unlimited_number'],
    ];

    return $form_element;
  }

  /**
   * {@inheritdoc}
   */
  public function massageFormValues(array $values, array $form, FormStateInterface $form_state) {
    $new_values = [];
    foreach ($values as $value) {
      $number = $value['unlimited_number'];
      if ($value['unlimited_number'] == UnlimitedNumber::UNLIMITED) {
        $number = $this->getUnlimitedValue();
      }
      $new_values[]['value'] = $number;
    }
    return $new_values;
  }

  /**
   * Get unlimited value from settings.
   *
   * @return integer
   */
  public function getUnlimitedValue() {
    $value_unlimited_raw = $this->getSetting('value_unlimited');
    return empty($value_unlimited_raw) ? 0 : $value_unlimited_raw;
  }

}
