<?php

namespace Drupal\votingapi_widgets\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;

use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\votingapi_widgets\Plugin\VotingApiWidgetManager;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Plugin implementation of the 'voting_api_widget' widget.
 *
 * @FieldWidget(
 *   id = "voting_api_widget",
 *   label = @Translation("Voting api widget"),
 *   field_types = {
 *     "voting_api_field"
 *   }
 * )
 */
class VotingApiWidget extends WidgetBase implements ContainerFactoryPluginInterface {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return ['show_initial_vote' => 0];
  }

  /**
   * @var VotingApiWidgetManager $votingapiWidgetProcessor
   */
  protected $votingapiWidgetProcessor;

  /**
   * @var AccountInterface $account
   */
  protected $account;

  /**
   * {@inheritdoc}
   */
  public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, array $third_party_settings, VotingApiWidgetManager $widget_manager, AccountInterface $account) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $third_party_settings);
    $this->account = $account;
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
      $configuration['third_party_settings'],
      $container->get('plugin.manager.voting_api_widget.processor'),
      $container->get('current_user')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $form['show_initial_vote'] = [
      '#type' => 'select',
      '#options' => [0 => $this->t('Show not initial voting'), 1 => $this->t('Show initial voting')],
      '#default_value' => $this->getSetting('show_initial_vote'),
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $entity = $items->getEntity();
    $field_label = $this->fieldDefinition->getLabel();
    $element['status'] = array(
      '#type' => 'radios',
      '#title' => $this->t($field_label),
      '#default_value' => isset($items->getValue('status')[0]['status']) ? $items->getValue('status')[0]['status'] : 1,
      '#options' => array(
        1 => $this->t('Open'),
        0 => $this->t('Closed'),
      ),
    );
    $entity_type = $this->fieldDefinition->getTargetEntityTypeId();
    $bundle = $this->fieldDefinition->getTargetBundle();
    $field_name = $this->fieldDefinition->getName();
    $permission = 'edit voting status on ' . $entity_type . ':' . $bundle . ':' . $field_name;
    $element['status']['#access'] = $this->account->hasPermission($permission);

    $plugin = $this->fieldDefinition->getSetting('vote_plugin');
    /**
     * @var VotingApiWidgetBase $plugin
     */
    $plugin = $this->votingapiWidgetProcessor->createInstance($plugin);

    $permission = 'vote on ' . $entity_type . ':' . $bundle . ':' . $field_name;
    $options = [
      '' => $this->t('None'),
    ];

    $vote_type = 'vote';
    $vote = $plugin->getEntityForVoting($entity_type, $bundle, $entity->id(), $vote_type, $field_name);
    $options += $plugin->getValues();
    $element['value'] = [
      '#type' => 'select',
      '#title' => $this->t('Your vote'),
      '#options' => $options,
      '#default_value' => $vote->getValue(),
      '#access' => ($this->getSetting('show_initial_vote') && $this->account->hasPermission($permission)) ? TRUE : FALSE,
    ];

    $plugin->getInitialVotingElement($element);

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = [];
    $summary[] = $this->t(
      'Show initial vote: @show_initial_vote',
      ['@show_initial_vote' => $this->getSetting('show_initial_vote') ? $this->t('yes') : $this->t('no')]
    );

    return $summary;
  }

}
