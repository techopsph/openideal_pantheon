<?php

namespace Drupal\votingapi_reaction\Plugin\Field\FieldWidget;

use Drupal\Component\Utility\Html;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\votingapi_reaction\Plugin\Field\FieldType\VotingApiReactionItemInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Session\AccountProxy;
use Drupal\Core\Field\FieldDefinitionInterface;

/**
 * Plugin implementation of the 'votingapi_reaction_default' widget.
 *
 * @FieldWidget(
 *   id = "votingapi_reaction_default",
 *   label = @Translation("Default"),
 *   field_types = {
 *     "votingapi_reaction"
 *   }
 * )
 */
class VotingApiReactionWidget extends WidgetBase implements ContainerFactoryPluginInterface {

  /**
   * Current user service.
   *
   * @var \Drupal\Core\Session\AccountProxy
   */
  protected $currentUser;

  /**
   * Class constructor.
   *
   * @param string $plugin_id
   *   The plugin_id for the widget.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Field\FieldDefinitionInterface $field_definition
   *   The definition of the field to which the widget is associated.
   * @param array $settings
   *   The widget settings.
   * @param array $third_party_settings
   *   Any third party settings.
   * @param \Drupal\Core\Session\AccountProxy $currentUser
   *   Current user service.
   */
  public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, array $third_party_settings, AccountProxy $currentUser) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $third_party_settings);
    $this->currentUser = $currentUser;
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
      $container->get('current_user')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $entity = $items->getEntity();

    $element['status'] = [
      '#type' => 'radios',
      '#title' => $this->t('Reactions'),
      '#title_display' => 'invisible',
      '#default_value' => !is_null($items->status) ? $items->status : VotingApiReactionItemInterface::OPEN,
      '#options' => [
        VotingApiReactionItemInterface::OPEN => $this->t('Open'),
        VotingApiReactionItemInterface::CLOSED => $this->t('Closed'),
        VotingApiReactionItemInterface::HIDDEN => $this->t('Hidden'),
      ],
      VotingApiReactionItemInterface::OPEN => [
        '#description' => $this->t('Users with proper permissions can react to this content.'),
      ],
      VotingApiReactionItemInterface::CLOSED => [
        '#description' => $this->t('Users cannot react to this content, but existing reactions will be displayed.'),
      ],
      VotingApiReactionItemInterface::HIDDEN => [
        '#description' => $this->t('Reactions are hidden from view.'),
      ],
      '#access' => $this->currentUser->hasPermission(
        'control reaction status on ' . $entity->getEntityTypeId() . ':' . $entity->bundle() . ':' . $items->getName()
      ),
    ];

    if (isset($form['advanced'])) {
      // Get default value from the field.
      $default_values = $this->fieldDefinition->getDefaultValue($entity);

      // Override widget title to be helpful for end users.
      $element['#title'] = $this->t('@title settings', [
        '@title' => $element['#title'],
      ]);

      $element += [
        '#type' => 'details',
        // Open the details when the selected value is different to the stored
        // default values for the field.
        '#open' => ($items->status != $default_values[0]['status']),
        '#group' => 'advanced',
        '#attributes' => [
          'class' => ['votingapi-reaction-' . Html::getClass($entity->getEntityTypeId()) . '-settings-form'],
        ],
      ];
    }

    return $element;
  }

}
