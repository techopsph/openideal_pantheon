<?php

namespace Drupal\moderation_state_buttons_widget;

use Drupal\content_moderation\ModerationInformationInterface;
use Drupal\content_moderation\StateTransitionValidationInterface;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Class ModerationStateButtonsWidgetInfo.
 */
class ModerationStateButtonsWidgetInfo implements ModerationStateButtonsWidgetInfoInterface {

  /**
   * Drupal\content_moderation\ModerationInformationInterface definition.
   *
   * @var \Drupal\content_moderation\ModerationInformationInterface
   */
  protected $moderationInformation;

  /**
   * Drupal\Core\Entity\EntityTypeManagerInterface definition.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Drupal\Core\Entity\EntityTypeBundleInfoInterface definition.
   *
   * @var \Drupal\Core\Entity\EntityTypeBundleInfoInterface
   */
  protected $bundleInfo;

  /**
   * Moderation state transition validation service.
   *
   * @var \Drupal\content_moderation\StateTransitionValidationInterface
   */
  protected $stateTransitionValidator;

  /**
   * Current user service.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * Constructs a new ModerationStateButtonsWidgetInfo object.
   *
   * @param \Drupal\content_moderation\ModerationInformationInterface $content_moderation_moderation_information
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   * @param \Drupal\Core\Entity\EntityTypeBundleInfoInterface $entity_type_bundle_info
   * @param \Drupal\content_moderation\StateTransitionValidationInterface $stateTransitionValidator
   */
  public function __construct(ModerationInformationInterface $content_moderation_moderation_information, EntityTypeManagerInterface $entity_type_manager, EntityTypeBundleInfoInterface $entity_type_bundle_info, StateTransitionValidationInterface $stateTransitionValidator, AccountInterface $currentUser) {
    $this->moderationInformation = $content_moderation_moderation_information;
    $this->entityTypeManager = $entity_type_manager;
    $this->bundleInfo = $entity_type_bundle_info;
    $this->stateTransitionValidator = $stateTransitionValidator;
    $this->currentUser = $currentUser;
  }

  /**
   * {@inheritdoc}
   */
  public function getAllBundlesThatCanBeModerated() {
    $result = [];

    foreach ($this->entityTypeManager->getDefinitions() as $entityType) {
      if (!$this->moderationInformation->canModerateEntitiesOfEntityType($entityType)) {
        continue;
      }

      $bundles = [];
      foreach ($this->bundleInfo->getBundleInfo($entityType->id()) as $bundleId => $bundle) {
        if (!$this->moderationInformation->shouldModerateEntitiesOfBundle($entityType, $bundleId)) {
          continue;
        }

        $bundles[$bundleId] = $bundle;
      }

      if (!empty($bundles)) {
        $result[$entityType->id()] = [
          'entity_type' => $entityType,
          'bundles' => $bundles,
        ];
      }
    }

    return $result;
  }

  /**
   * {@inheritdoc}
   */
  public function getStates(ContentEntityInterface $entity) {
    $workflow = $this->moderationInformation->getWorkflowForEntity($entity);
    $currentState = $workflow
      ->getTypePlugin()->getState($entity->moderation_state->value);
    $states = [];

    foreach ($workflow->getTypePlugin()->getStates() as $stateId => $state) {
      try {
        // Check if the transition exists and can be performed by the current
        // user.
        $transitionPossible = $this->stateTransitionValidator
          ->isTransitionValid(
            $workflow, $currentState, $state, $this->currentUser);
      }
      catch (\InvalidArgumentException $e) {
        // The transition doesn't exist.
        $transitionPossible = FALSE;
      }
      $states[$stateId] = [
        'state' => $state,
        'transition_possible' => $transitionPossible,
      ];
    }

    return $states;
  }

}
