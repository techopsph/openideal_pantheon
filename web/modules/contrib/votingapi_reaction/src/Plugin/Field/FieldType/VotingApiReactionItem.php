<?php

namespace Drupal\votingapi_reaction\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldItemBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\TypedData\DataDefinition;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldStorageDefinitionInterface;

/**
 * Plugin implementation of the 'votingapi_reaction' field type.
 *
 * @FieldType(
 *   id = "votingapi_reaction",
 *   label = @Translation("Reaction"),
 *   description = @Translation("Allows user to react to an entity"),
 *   default_widget = "votingapi_reaction_default",
 *   default_formatter = "votingapi_reaction_default",
 *   cardinality = 1,
 * )
 */
class VotingApiReactionItem extends FieldItemBase implements VotingApiReactionItemInterface {

  /**
   * {@inheritdoc}
   */
  public static function defaultFieldSettings() {
    return [
      'anonymous_detection' => [
        VotingApiReactionItemInterface::BY_COOKIES,
        VotingApiReactionItemInterface::BY_IP,
      ],
      'anonymous_rollover' => VotingApiReactionItemInterface::VOTINGAPI_ROLLOVER,
      'reactions' => [
        'reaction_angry',
        'reaction_laughing',
        'reaction_like',
        'reaction_love',
        'reaction_sad',
        'reaction_surprised',
      ],
    ] + parent::defaultFieldSettings();
  }

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {
    return [
      'status' => DataDefinition::create('integer')
        ->setLabel(t('Reaction status'))
        ->setRequired(TRUE),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition) {
    return [
      'columns' => [
        'status' => [
          'description' => 'Whether reactions are allowed on this entity: 0 = no, 1 = closed (read only), 2 = open (read/write).',
          'type' => 'int',
          'default' => 0,
        ],
      ],
      'indexes' => [],
      'foreign keys' => [],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function fieldSettingsForm(array $form, FormStateInterface $form_state) {
    $element = [
      '#attached' => ['library' => ['votingapi_reaction/settings_styles']],
    ];

    $element['anonymous_detection'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Anonymous detection method'),
      '#description' => $this->t("By which method anonymous users must be detected. Warning: Detecting users by cookies is not a reliable way, as cookies could be easily manipulated by users."),
      '#options' => [
        VotingApiReactionItemInterface::BY_COOKIES => $this->t('By cookies'),
        VotingApiReactionItemInterface::BY_IP => $this->t('By IP'),
      ],
      '#default_value' => $this->getSetting('anonymous_detection'),
      '#required' => TRUE,
    ];

    $options = [
      300,
      900,
      1800,
      3600,
      10800,
      21600,
      32400,
      43200,
      86400,
      172800,
      345600,
      604800,
    ];
    foreach ($options as $key => $option) {
      unset($options[$key]);
      $options[$option] = \Drupal::service('date.formatter')
        ->formatInterval($option);
    }
    $options[VotingApiReactionItemInterface::NEVER_ROLLOVER] = $this->t('Never');
    $options[VotingApiReactionItemInterface::VOTINGAPI_ROLLOVER] = $this->t('Voting API Default');

    $element['anonymous_rollover'] = [
      '#type' => 'select',
      '#title' => $this->t('Anonymous vote rollover'),
      '#description' => $this->t("The amount of time that must pass before two anonymous votes from the same computer are considered unique. Setting this to 'never' will eliminate most double-voting, but will make it impossible for multiple anonymous on the same computer (like internet cafe customers) from casting votes."),
      '#options' => $options,
      '#default_value' => $this->getSetting('anonymous_rollover'),
    ];

    $reactionManager = \Drupal::service('votingapi_reaction.manager');
    $element['reactions'] = [
      '#title' => $this->t('Reactions'),
      '#type' => 'checkboxes',
      '#required' => TRUE,
      '#options' => $reactionManager->getReactions([
        'show_icon' => TRUE,
        'show_label' => TRUE,
        'show_count' => FALSE,
        'sort_reactions' => 'none',
        'reactions' => array_keys($reactionManager->allReactions()),
      ], []),
      '#default_value' => $this->getSetting('reactions'),
      '#attributes' => ['class' => ['votingapi-reaction-settings']],
    ];

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public static function mainPropertyName() {
    return 'status';
  }

  /**
   * {@inheritdoc}
   */
  public function isEmpty() {
    // @TODO check if Voting API tables have records for this field
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public static function generateSampleValue(FieldDefinitionInterface $field_definition) {
    $statuses = [
      VotingApiReactionItemInterface::HIDDEN,
      VotingApiReactionItemInterface::CLOSED,
      VotingApiReactionItemInterface::OPEN,
    ];
    return [
      'status' => $statuses[mt_rand(0, count($statuses) - 1)],
    ];
  }

}
