<?php

namespace Drupal\votingapi_widgets\Plugin;

use Drupal\Component\Plugin\PluginBase;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\field\Entity\FieldConfig;

use Drupal\votingapi\VoteResultFunctionManager;
use Drupal\Core\Entity\EntityFormBuilderInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Session\AccountInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Drupal\Core\Config\ConfigFactoryInterface;

/**
 * Base class for Voting api widget plugins.
 */
abstract class VotingApiWidgetBase extends PluginBase implements VotingApiWidgetInterface, ContainerFactoryPluginInterface {

  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;
  /**
   * @var VoteResultFunctionManager $votingapiResult
   */
  protected $votingapiResult;

  /**
   * @var \Drupal\Core\Entity\EntityFormBuilderInterface
   */
  protected $entityFormBuilder;

  /**
   * @var AccountInterface $account
   */
  protected $account;

  /**
   * The request stack.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * The config factory service.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Constructs a new class instance.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   Entity type manager service.
   * @param \Drupal\votingapi\VoteResultFunctionManager $vote_result
   *   Vote result function service.
   * @param \Drupal\Core\Entity\EntityFormBuilderInterface $form_builder
   *   The form builder service.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The account service.
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   The request stack.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory service.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entity_type_manager, VoteResultFunctionManager $vote_result, EntityFormBuilderInterface $form_builder, AccountInterface $account, RequestStack $request_stack, ConfigFactoryInterface $config_factory) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->entityTypeManager = $entity_type_manager;
    $this->votingapiResult = $vote_result;
    $this->entityFormBuilder = $form_builder;
    $this->account = $account;
    $this->requestStack = $request_stack;
    $this->configFactory = $config_factory;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager'),
      $container->get('plugin.manager.votingapi.resultfunction'),
      $container->get('entity.form_builder'),
      $container->get('current_user'),
      $container->get('request_stack'),
      $container->get('config.factory')
    );
  }

  /**
   * Return label.
   */
  public function getLabel() {
    return $this->label;
  }

  /**
   * Return minimal value.
   */
  public function getValues() {
    return $this->getPluginDefinition()['values'];
  }

  /**
   * Gets the widget form as configured for given parameters.
   *
   * @return \Drupal\Core\Form\FormInterface
   *   configured vote form
   */
  public function getForm($entity_type, $entity_bundle, $entity_id, $vote_type, $field_name, $settings) {
    $vote = $this->getEntityForVoting($entity_type, $entity_bundle, $entity_id, $vote_type, $field_name);
    /*
     * @TODO: remove custom entity_form_builder once
     *   https://www.drupal.org/node/766146 is fixed.
     */

    return $this->entityFormBuilder->getForm($vote, 'votingapi_' . $this->getPluginId(), [
      'options' => $this->getPluginDefinition()['values'],
      'settings' => $settings,
      'plugin' => $this,
      // @TODO: following keys can be removed once #766146 is fixed.
      'entity_type' => $entity_type,
      'entity_bundle' => $entity_bundle,
      'entity_id' => $entity_id,
      'vote_type' => $vote_type,
      'field_name' => $field_name,
    ]);
  }

  /**
   * Get initial element.
   */
  abstract public function getInitialVotingElement(array &$form);

  /**
   * Checks whether currentUser is allowed to vote.
   *
   * @return bool
   *   True if user is allowed to vote
   */
  public function canVote($vote, $account = FALSE) {
    if (!$account) {
      $account = $this->account;
    }
    $entity = $this->entityTypeManager
      ->getStorage($vote->getVotedEntityType())
      ->load($vote->getVotedEntityId());

    if (!$entity) {
      return FALSE;
    }

    $perm = 'vote on ' . $vote->getVotedEntityType() . ':' . $entity->bundle() . ':' . $vote->field_name->value;
    if (!$vote->isNew()) {
      $perm = 'edit own vote on ' . $vote->getVotedEntityType() . ':' . $entity->bundle() . ':' . $vote->field_name->value;
    }
    return $account->hasPermission($perm);
  }

  /**
   * Returns a Vote entity.
   *
   * Checks whether a vote was already done and if this vote should be reused
   * instead of adding a new one.
   *
   * @return \Drupal\votingapi\Entity\Vote
   *  Vote entity
   */
  public function getEntityForVoting($entity_type, $entity_bundle, $entity_id, $vote_type, $field_name) {
    $storage = $this->entityTypeManager->getStorage('vote');
    $voteData = [
      'entity_type' => $entity_type,
      'entity_id'   => $entity_id,
      'type'      => $vote_type,
      'field_name'  => $field_name,
      'user_id' => $this->account->id(),
    ];
    $vote = $storage->create($voteData);
    $timestamp_offset = $this->getWindow('user_window', $entity_type, $entity_bundle, $field_name);

    if ($this->account->isAnonymous()) {
      $voteData['vote_source'] = $this->requestStack->getCurrentRequest()->getClientIp();
      $timestamp_offset = $this->getWindow('anonymous_window', $entity_type, $entity_bundle, $field_name);
    }

    $query = $this->entityTypeManager->getStorage('vote')->getQuery();
    foreach ($voteData as $key => $value) {
      $query->condition($key, $value);
    }

    // Check for rollover 'never' setting.
    if (!empty($timestamp_offset)) {
      $query->condition('timestamp', time() - $timestamp_offset, '>=');
    }

    $votes = $query->execute();
    if ($votes && count($votes) > 0) {
      $vote = $storage->load(array_shift($votes));
    }

    return $vote;
  }

  /**
   * Get results.
   */
  public function getResults($entity, $result_function = FALSE, $reset = FALSE) {
    if ($reset) {
      drupal_static_reset(__FUNCTION__);
    }
    $resultCache = &drupal_static(__FUNCTION__);
    if (!$resultCache) {
      $resultCache = $this->votingapiResult->getResults($entity->getVotedEntityType(), $entity->getVotedEntityId());
    }

    if ($result_function) {
      if (!$resultCache[$entity->getEntityTypeId()][$entity->getVotedEntityId()][$result_function]) {
        return [];
      }
      return $resultCache[$entity->getEntityTypeId()][$entity->getVotedEntityId()][$result_function];
    }

    if (!$result_function) {
      if (!isset($resultCache[$entity->getEntityTypeId()][$entity->getVotedEntityId()])) {
        return [];
      }
      return $resultCache[$entity->getEntityTypeId()][$entity->getVotedEntityId()];
    }
    return [];
  }

  /**
   * Get time window settings.
   */
  public function getWindow($window_type, $entity_type_id, $entity_bundle, $field_name) {
    $config = FieldConfig::loadByName($entity_type_id, $entity_bundle, $field_name);

    $window_field_setting = $config->getSetting($window_type);
    $use_site_default = FALSE;

    if ($window_field_setting === NULL || $window_field_setting === "-1") {
      $use_site_default = TRUE;
    }

    $window = $window_field_setting;
    if ($use_site_default) {
      /*
       * @var \Drupal\Core\Config\ImmutableConfig $voting_configuration
       */
      $voting_configuration = $this->configFactory->get('votingapi.settings');
      $window = $voting_configuration->get($window_type);
    }

    return $window;
  }

  /**
   * Generate summary.
   */
  public function getVoteSummary(ContentEntityInterface $vote) {
    $results = $this->getResults($vote);
    $field_name = $vote->field_name->value;
    $fieldResults = [];

    foreach ($results as $key => $result) {
      if (strrpos($key, $field_name) !== FALSE) {
        $key = explode(':', $key);
        $fieldResults[$key[0]] = ($result != 0) ? ceil($result * 10) / 10 : 0;
      }
    }

    return [
      '#theme' => 'votingapi_widgets_summary',
      '#vote' => $vote,
      '#results' => $fieldResults,
      '#field_name' => $vote->field_name->value,
    ];
  }

}
