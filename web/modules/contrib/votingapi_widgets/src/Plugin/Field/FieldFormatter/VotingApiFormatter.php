<?php

namespace Drupal\votingapi_widgets\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\votingapi\VoteResultFunctionManager;
use Drupal\votingapi_widgets\Plugin\VotingApiWidgetManager;

/**
 * Plugin implementation of the 'voting_api_formatter' formatter.
 *
 * @FieldFormatter(
 *   id = "voting_api_formatter",
 *   label = @Translation("Voting api formatter"),
 *   field_types = {
 *     "voting_api_field"
 *   }
 * )
 */
class VotingApiFormatter extends FormatterBase implements ContainerFactoryPluginInterface {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'readonly'      => FALSE,
      'style'         => 'default',
      'show_results'  => FALSE,
      'values'        => [],
      'show_own_vote' => FALSE,
      // Implement default settings.
    ] + parent::defaultSettings();
  }

  /**
   * @var VoteResultFunctionManager $votingapiResult
   */
  protected $votingapiResult;

  /**
   * @var VotingApiWidgetManager $votingapiWidgetProcessor
   */
  protected $votingapiWidgetProcessor;

  /**
   * Constructs an VotingApiFormatter object.
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
   * @param \Drupal\votingapi\VoteResultFunctionManager $vote_result
   *   Vote result function.
   * @param \Drupal\votingapi_widgets\Plugin\VotingApiWidgetManager $widget_manager
   *   Voting Api Widget Manager.
   */
  public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, $label, $view_mode, array $third_party_settings, VoteResultFunctionManager $vote_result, VotingApiWidgetManager $widget_manager) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $label, $view_mode, $third_party_settings);
    $this->votingapiResult = $vote_result;
    $this->votingapiWidgetProcessor = $widget_manager;
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
      $container->get('plugin.manager.votingapi.resultfunction'),
      $container->get('plugin.manager.voting_api_widget.processor')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $plugins = $this->votingapiResult->getDefinitions();

    $options = [];
    $styles = [];

    $votePlugin = $this->votingapiWidgetProcessor->createInstance($this->getFieldSetting('vote_plugin'));
    $styles = $votePlugin->getStyles();

    foreach ($plugins as $plugin_id => $plugin) {
      $plugin = $this->votingapiResult->createInstance($plugin_id, $plugin);
      if ($plugin->getDerivativeId()) {
        $options[$plugin_id] = $plugin_id;
      }
    }

    return [
      // Implement settings form.
      'style'        => [
        '#title'         => $this->t('Styles'),
        '#type'          => 'select',
        '#options'       => $styles,
        '#default_value' => $this->getSetting('style'),
      ],
      'readonly'     => [
        '#title'         => $this->t('Readonly'),
        '#type'          => 'checkbox',
        '#default_value' => $this->getSetting('readonly'),
      ],
      'show_results' => [
        '#title'         => $this->t('Show results'),
        '#type'          => 'checkbox',
        '#default_value' => $this->getSetting('show_results'),
      ],
      'show_own_vote' => [
        '#title'         => $this->t('Show own vote'),
        '#description'   => $this->t('Show own cast vote instead of results. (Useful on add/ edit forms with rate widget).'),
        '#type'          => 'checkbox',
        '#return_value'  => 1,
        '#default_value' => $this->getSetting('show_own_vote'),
      ],
    ] + parent::settingsForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = [];
    $summary[] = $this->t('Styles: @styles', ['@styles' => $this->getSetting('style')]);
    $summary[] = $this->t('Readonly: @readonly', ['@readonly' => $this->getSetting('readonly') ? $this->t('yes') : $this->t('no')]);
    $summary[] = $this->t('Show results: @results', ['@results' => $this->getSetting('show_results') ? $this->t('yes') : $this->t('no')]);
    $summary[] = $this->t('Show own vote: @show_own_vote', ['@show_own_vote' => $this->getSetting('show_own_vote') ? $this->t('yes') : $this->t('no')]);

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];

    $entity = $items->getEntity();

    // Do not continue if the entity is being previewed.
    if (!empty($entity->in_preview)) {
      return $elements;
    }

    $field_settings = $this->getFieldSettings();
    $field_name = $this->fieldDefinition->getName();

    $vote_type = $field_settings['vote_type'];
    $vote_plugin = $field_settings['vote_plugin'];

    $show_own_vote = $this->getSetting('show_own_vote') ? TRUE : FALSE;

    $elements[] = [
      'vote_form' => [
        '#lazy_builder'       => [
          'voting_api.lazy_loader:buildForm',
          [
            $vote_plugin,
            $entity->getEntityTypeId(),
            $entity->bundle(),
            $entity->id(),
            $vote_type,
            $field_name,
            serialize($this->getSettings())
          ],
        ],
        '#create_placeholder' => TRUE,
      ],
      'results'   => [],
    ];

    return $elements;
  }

}
