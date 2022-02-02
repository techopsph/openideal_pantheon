<?php

namespace Drupal\moderation_state_buttons_widget\Form;

use Drupal\Core\Entity\EntityStorageException;
use Drupal\Core\Entity\EntityTypeManager;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\moderation_state_buttons_widget\ModerationStateButtonsWidgetInfoInterface;

/**
 * Class ModerationStateSelectionForm.
 */
class ModerationStateSelectionForm extends FormBase {

  /**
   * Drupal\moderation_state_buttons_widget\ModerationStateButtonsWidgetInfoInterface definition.
   *
   * @var \Drupal\moderation_state_buttons_widget\ModerationStateButtonsWidgetInfoInterface
   */
  protected $moderationStateButtonsWidgetInfo;

  /**
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a new ModerationStateSelectionForm object.
   *
   * @param \Drupal\moderation_state_buttons_widget\ModerationStateButtonsWidgetInfoInterface $moderation_state_buttons_widget_info
   * @param \Drupal\Core\Entity\EntityTypeManager $entity_type_manager
   */
  public function __construct(
    ModerationStateButtonsWidgetInfoInterface $moderation_state_buttons_widget_info,
    EntityTypeManager $entity_type_manager
  ) {
    $this->moderationStateButtonsWidgetInfo = $moderation_state_buttons_widget_info;
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('moderation_state_buttons_widget.info'),
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'moderation_state_selection_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $buildInfo = $form_state->getBuildInfo();
    /** @var \Drupal\Core\Entity\ContentEntityInterface $entity */
    $entity = $buildInfo['args'][0];

    $states = $this->moderationStateButtonsWidgetInfo->getStates($entity);
    $options = [];
    foreach ($states as $stateId => $stateInfo) {
      /** @var \Drupal\content_moderation\ContentModerationState $stateObject */
      $stateObject = $stateInfo['state'];
      $options[$stateId] = $stateObject->label();
    }

    $form['new_state'] = [
      '#type' => 'radios',
      '#title' => new TranslatableMarkup('Moderation state'),
      '#options' => $options,
      '#default_value' => $entity->moderation_state->value,
      '#attributes' => [
        'class' => [
          'new-state-wrapper',
        ],
      ],
    ];

    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Submit'),
    ];

    // Add the states to the build info and set the #after_build callback. It
    // will disable the radio buttons corresponding to the states that the
    // entity can be moved to.
    // @see https://www.drupal.org/project/drupal/issues/342316
    $buildInfo['states'] = $states;
    $form_state->setBuildInfo($buildInfo);
    $form['#after_build'][] = [get_class($this), 'afterBuild'];

    // Add the default styles.
    $form['#attached']['library'][] =
      'moderation_state_buttons_widget/view_widget';

    return $form;
  }

  /**
   * The after build callback.
   *
   * @param array $form
   *   The form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return array
   *   The rendered form. This function may also perform a redirect and hence
   *   may not return at all depending upon the $form_state flags that were set.
   */
  public static function afterBuild(array $form, FormStateInterface $form_state) {
    $buildInfo = $form_state->getBuildInfo();
    foreach ($buildInfo['states'] as $stateId => $stateInfo) {
      if (!$stateInfo['transition_possible']) {
        $form['new_state'][$stateId]['#attributes']['disabled'] = 'disabled';
      }
    }

    $class = 'moderation-state-selection-form--new-state-wrapper';
    $form['new_state']['#prefix'] = '<div class="' . $class . '">';
    $form['new_state']['#suffix'] = '</div>';
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    // The radio buttons are disabled in #after_build so we can't rely on the
    // forms API validation.
    // @see https://www.drupal.org/project/drupal/issues/342316
    $buildInfo = $form_state->getBuildInfo();
    /** @var \Drupal\Core\Entity\ContentEntityInterface $entity */
    $entity = $buildInfo['args'][0];
    $states = $this->moderationStateButtonsWidgetInfo->getStates($entity);
    $previousState = $entity->moderation_state->value;
    $newState = $form_state->getValue('new_state');
    if ($previousState != $newState&& !$states[$newState]['transition_possible']) {
      $form_state->setError($form['new_state'],
        new TranslatableMarkup('The selected state is not valid.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $buildInfo = $form_state->getBuildInfo();
    /** @var \Drupal\Core\Entity\ContentEntityInterface $entity */
    $entity = $buildInfo['args'][0];
    /** @var \Drupal\Core\Entity\ContentEntityStorageInterface $storage */
    $storage = $this->entityTypeManager->getStorage($entity->getEntityTypeId());

    $newRevision = $storage->createRevision($entity);
    $newState = $form_state->getValue('new_state');

    $newRevision->moderation_state = $newState;
    try {
      $newRevision->save();
      $this->messenger()->addStatus(
        new TranslatableMarkup('Save successful.')
      );
    }
    catch (EntityStorageException $e) {
      $this->messenger()->addError(new TranslatableMarkup(
        'Save failed with message: %message',
        ['%message' => $e->getMessage()]
      ));
    }
  }

}
