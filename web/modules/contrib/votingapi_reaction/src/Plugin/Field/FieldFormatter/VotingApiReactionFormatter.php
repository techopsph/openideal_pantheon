<?php

namespace Drupal\votingapi_reaction\Plugin\Field\FieldFormatter;

use Drupal\Core\Entity\EntityFormBuilder;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\votingapi\Entity\Vote;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Plugin implementation of the 'votingapi_reaction_default' formatter.
 *
 * @FieldFormatter(
 *   id = "votingapi_reaction_default",
 *   module = "votingapi_reaction",
 *   label = @Translation("Default"),
 *   field_types = {
 *     "votingapi_reaction"
 *   },
 *   quickedit = {
 *     "editor" = "disabled"
 *   }
 * )
 */
class VotingApiReactionFormatter extends FormatterBase implements ContainerFactoryPluginInterface {

  /**
   * Form builder service.
   *
   * @var \Drupal\Core\Entity\EntityFormBuilder
   */
  protected $formBuilder;

  /**
   * Constructs an VotingApiReactionFormatter object.
   *
   * @param string $plugin_id
   *   The plugin_id for the formatter.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Field\FieldDefinitionInterface $field_definition
   *   The definition of the field to which the formatter is associated.
   * @param array $settings
   *   The formatter settings.
   * @param string $label
   *   The formatter label display setting.
   * @param string $view_mode
   *   The view mode.
   * @param array $third_party_settings
   *   Any third party settings settings.
   * @param \Drupal\Core\Entity\EntityFormBuilder $form_builder
   *   Form builder service.
   */
  public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, $label, $view_mode, array $third_party_settings, EntityFormBuilder $form_builder) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $label, $view_mode, $third_party_settings);
    $this->formBuilder = $form_builder;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $plugin_id,
      $plugin_definition,
      $configuration['field_definition'],
      $configuration['settings'],
      $configuration['label'],
      $configuration['view_mode'],
      $configuration['third_party_settings'],
      $container->get('entity.form_builder')
    );
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'show_summary' => TRUE,
      'show_icon' => TRUE,
      'show_label' => TRUE,
      'show_count' => TRUE,
      'sort_reactions' => 'desc',
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    return [
      'show_summary' => [
        '#title' => $this->t('Show reactions summary'),
        '#type' => 'checkbox',
        '#default_value' => $this->getSetting('show_summary'),
      ],
      'show_icon' => [
        '#title' => $this->t('Show reaction icon'),
        '#type' => 'checkbox',
        '#default_value' => $this->getSetting('show_icon'),
      ],
      'show_label' => [
        '#title' => $this->t('Show reaction label'),
        '#type' => 'checkbox',
        '#default_value' => $this->getSetting('show_label'),
      ],
      'show_count' => [
        '#title' => $this->t('Show reaction count'),
        '#type' => 'checkbox',
        '#default_value' => $this->getSetting('show_count'),
      ],
      'sort_reactions' => [
        '#title' => $this->t('Sort reactions'),
        '#type' => 'select',
        '#options' => [
          'none' => $this->t('No sorting'),
          'asc' => $this->t('Asc'),
          'desc' => $this->t('Desc'),
        ],
        '#default_value' => $this->getSetting('sort_reactions'),
      ],
    ] + parent::settingsForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    return [
      $this->t('Reactions summary: @status', ['@status' => $this->getSetting('show_summary') ? $this->t('yes') : $this->t('no')]),
      $this->t('Reaction icon: @status', ['@status' => $this->getSetting('show_icon') ? $this->t('yes') : $this->t('no')]),
      $this->t('Reaction label: @status', ['@status' => $this->getSetting('show_label') ? $this->t('yes') : $this->t('no')]),
      $this->t('Reaction count: @status', ['@status' => $this->getSetting('show_count') ? $this->t('yes') : $this->t('no')]),
      $this->t('Sort reactions: @status', ['@status' => $this->getSetting('sort_reactions')]),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];

    if (is_null($items->status)) {
      $default_value = $items->getFieldDefinition()->getDefaultValue($items->getEntity());
      $items->status = $default_value[0]['status'];
    }

    $extras = [
      'field_items' => $items,
      'formatter_settings' => $this->getSettings(),
    ];

    $entity = Vote::create([
      'type' => 'vote',
      'entity_id' => $items->getEntity()->id(),
      'entity_type' => $items->getEntity()->getEntityTypeId(),
      'value_type' => 'option',
      'value' => 1,
      'field_name' => $items->getName(),
    ]);

    $form = $this->formBuilder->getForm($entity, 'votingapi_reaction', $extras);

    if ($form['#access']) {
      $elements[] = ['form' => $form];
    }

    return $elements;
  }

}
