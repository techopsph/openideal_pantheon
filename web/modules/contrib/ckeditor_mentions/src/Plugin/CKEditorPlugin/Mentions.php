<?php

namespace Drupal\ckeditor_mentions\Plugin\CKEditorPlugin;

use Drupal\ckeditor\CKEditorPluginBase;
use Drupal\ckeditor\CKEditorPluginConfigurableInterface;
use Drupal\ckeditor\CKEditorPluginContextualInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\editor\Entity\Editor;
use Drupal\ckeditor\CKEditorPluginCssInterface;

/**
 * Defines the "mentions" plugin.
 *
 * @CKEditorPlugin(
 *   id = "mentions",
 *   label = @Translation("Mentions"),
 *   module = "ckeditor_mentions"
 * )
 */
class Mentions extends CKEditorPluginBase implements CKEditorPluginConfigurableInterface, CKEditorPluginContextualInterface, CKEditorPluginCssInterface {

  /**
   * {@inheritdoc}
   */
  public function getDependencies(Editor $editor) {
    return ['autocomplete', 'textmatch', 'ajax', 'xml', 'textwatcher'];
  }

  /**
   * {@inheritdoc}
   */
  public function getButtons() {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function getConfig(Editor $editor) {
    $settings = $editor->getSettings();

    return [
      'mentions' => [
        [
          'throttle' => $settings['plugins']['mentions']['timeout'],
          'minChars' => $settings['plugins']['mentions']['charcount'],
          'feed' => '/ckeditor-mentions/ajax/{encodedQuery}',
          'itemTemplate' => '<li data-id="{id}">' . $settings['plugins']['mentions']['item_template'] . '</li>',
          'outputTemplate' => $settings['plugins']['mentions']['output_template'],
        ],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFile() {
    return 'libraries/ckeditor/plugins/mentions/plugin.js';
  }

  /**
   * {@inheritdoc}
   */
  public function isEnabled(Editor $editor) {
    $settings = $editor->getSettings();

    return (isset($settings['plugins']['mentions']['enable']) && (bool) $settings['plugins']['mentions']['enable']);
  }

  /**
   * {@inheritdoc}
   */
  public function getCssFiles(Editor $editor) {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state, Editor $editor) {
    $settings = $editor->getSettings();

    $form['enable'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable Mentions'),
      '#default_value' => !empty($settings['plugins']['mentions']['enable']) ? $settings['plugins']['mentions']['enable'] : FALSE,
    ];

    $form['charcount'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Character Count'),
      '#description' => $this->t('Enter minimum number of characters that must be typed to trigger mention match.'),
      '#default_value' => !empty($settings['plugins']['mentions']['charcount']) ? $settings['plugins']['mentions']['charcount'] : 0,
    ];

    $form['timeout'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Timeout (milliseconds)'),
      '#description' => $this->t('Enter time in milliseconds for mentions script to stop checking for matches.'),
      '#default_value' => !empty($settings['plugins']['mentions']['timeout']) ? $settings['plugins']['mentions']['timeout'] : 500,
    ];

    $form['item_template'] = [
      '#type' => 'textfield',
      '#title' => $this->t("The panel's item template used to render matches in the dropdown."),
      '#description' => $this->t('Placeholders you can put into template: user_page (entity.canonical page), account_name, realname, email, avatar (user picture url), id.
      The placeholder should wrapped by curly braces - {placeholder}. <br/>
      Example: &lt;a href="{user_page}"&gt;{first_name}&lt;/a&gt;'),
      '#default_value' => $settings['plugins']['mentions']['item_template'] ?? '<img class="photo" src="{avatar}" /><strong class="realname">{realname}</strong>',
    ];

    $form['output_template'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Template of markup to be inserted as the autocomplete item gets committed.'),
      '#description' => $this->t('Can be used same placeholders as in item template. Always put the "data-mention" attribute with id placeholder.'),
      '#default_value' => $settings['plugins']['mentions']['output_template'] ?? '<a data-mention="{id}" href="{user_page}">@{realname}</a><span>&nbsp;</span>',
    ];

    $form['charcount']['#element_validate'][] = [$this, 'isPositiveOrZeroNumber'];
    $form['timeout']['#element_validate'][] = [$this, 'isPositiveNumber'];

    return $form;
  }

  /**
   * Check if value is positive.
   *
   * @param array $element
   *   The Form element.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The FormState Object.
   */
  public function isPositiveNumber(array $element, FormStateInterface $form_state) {
    if (!is_numeric($element['#value']) || $element['#value'] < 1) {
      $form_state->setError($element, $this->t('Value must be a positive integer.'));
    }
  }

  /**
   * Check if value is positive or zero.
   *
   * @param array $element
   *   The Form element.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The FormState Object.
   */
  public function isPositiveOrZeroNumber(array $element, FormStateInterface $form_state) {
    if (!is_numeric($element['#value']) || $element['#value'] < 0) {
      $form_state->setError($element, $this->t('Value must be a positive integer or zero.'));
    }
  }

}
