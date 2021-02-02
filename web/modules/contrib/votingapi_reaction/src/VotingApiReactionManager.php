<?php

namespace Drupal\votingapi_reaction;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Render\Renderer;
use Drupal\Core\Session\AccountProxy;
use Drupal\votingapi\Entity\Vote;
use Drupal\votingapi\Entity\VoteType;
use Drupal\votingapi\VoteResultFunctionManager;
use Drupal\votingapi_reaction\Plugin\Field\FieldType\VotingApiReactionItemInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Config\ConfigFactoryInterface;

/**
 * Manages reactions through Voting API entities.
 */
class VotingApiReactionManager implements ContainerInjectionInterface {

  /**
   * Vote storage.
   *
   * @var \Drupal\votingapi\VoteStorage
   */
  protected $voteStorage;

  /**
   * Vote type storage.
   *
   * @var \Drupal\Core\Entity\Sql\SqlContentEntityStorage
   */
  protected $voteTypeStorage;

  /**
   * File storage.
   *
   * @var \Drupal\Core\Entity\Sql\SqlContentEntityStorage
   */
  protected $fileStorage;

  /**
   * Current user service.
   *
   * @var \Drupal\Core\Session\AccountProxy
   */
  protected $currentUser;

  /**
   * Voting API results service.
   *
   * @var \Drupal\votingapi\VoteResultFunctionManager
   */
  protected $votingApiResults;

  /**
   * Renderer service.
   *
   * @var \Drupal\Core\Render\Renderer
   */
  protected $renderer;

  /**
   * Configuration factory service.
   *
   * @var \Drupal\Core\Config\ConfigFactory
   */
  protected $configFactory;

  /**
   * Class constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   Entity type manager service.
   * @param \Drupal\Core\Session\AccountProxy $currentUser
   *   Current user service.
   * @param \Drupal\votingapi\VoteResultFunctionManager $votingApiResults
   *   Voting API results service.
   * @param \Drupal\Core\Render\Renderer $renderer
   *   Renderer service.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   Configuration factory service.
   */
  public function __construct(EntityTypeManagerInterface $entityTypeManager, AccountProxy $currentUser, VoteResultFunctionManager $votingApiResults, Renderer $renderer, ConfigFactoryInterface $configFactory) {
    $this->voteStorage = $entityTypeManager->getStorage('vote');
    $this->voteTypeStorage = $entityTypeManager->getStorage('vote_type');
    $this->fileStorage = $entityTypeManager->getStorage('file');
    $this->currentUser = $currentUser;
    $this->votingApiResults = $votingApiResults;
    $this->renderer = $renderer;
    $this->configFactory = $configFactory;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('current_user'),
      $container->get('plugin.manager.votingapi.resultfunction'),
      $container->get('renderer'),
      $container->get('config.factory')
    );
  }

  /**
   * Load previous reaction of the user for certain field.
   *
   * @param \Drupal\votingapi\Entity\Vote $entity
   *   Current vote entity.
   * @param array $settings
   *   Field settings.
   *
   * @return \Drupal\Core\Entity\EntityInterface
   *   Last reaction for current user.
   */
  public function lastReaction(Vote $entity, array $settings) {
    $query = $this->voteStorage->getQuery()
      ->condition('entity_id', $entity->getVotedEntityId())
      ->condition('entity_type', $entity->getVotedEntityType())
      ->condition('field_name', $entity->get('field_name')->value)
      ->condition('user_id', $this->currentUser->id());

    if ($this->currentUser->isAnonymous()) {
      // Filter by Cookie method.
      if (in_array(VotingApiReactionItemInterface::BY_COOKIES, $settings['anonymous_detection'])) {
        $query->condition('id', $this->recallReaction($entity));
      }
      // Filter by IP method.
      if (in_array(VotingApiReactionItemInterface::BY_IP, $settings['anonymous_detection'])) {
        $query->condition('vote_source', Vote::getCurrentIp());
      }

      // Filter by rollover.
      $rollover = $settings['anonymous_rollover'];
      if ($rollover == VotingApiReactionItemInterface::VOTINGAPI_ROLLOVER) {
        $rollover = $this->configFactory
          ->get('votingapi.settings')
          ->get('anonymous_window');
      }
      if ($rollover != VotingApiReactionItemInterface::NEVER_ROLLOVER) {
        $query->condition('timestamp', time() - $rollover, '>=');
      }
    }

    $ids = $query->execute();

    return $this->voteStorage->load(intval(array_pop($ids)));
  }

  /**
   * Return voting results for each active reaction.
   *
   * @param \Drupal\votingapi\Entity\Vote $entity
   *   Current vote entity.
   * @param array $settings
   *   Field settings.
   *
   * @return array
   *   Array containing Voting API voting results.
   */
  public function getResults(Vote $entity, array $settings) {
    // Get results for each reaction.
    $results = $this->votingApiResults->getResults($entity->getVotedEntityType(), $entity->getVotedEntityId());
    $reactions = array_filter($settings['reactions']);

    return array_intersect_key($results, $reactions);
  }

  /**
   * Recalculate results for given reaction and entity.
   *
   * @param string $entity_type
   *   Voted entity type.
   * @param string $entity_id
   *   Voted entity id.
   * @param string $type
   *   Vote type.
   */
  public function recalculateResults($entity_type, $entity_id, $type) {
    $this->votingApiResults->recalculateResults($entity_type, $entity_id, $type);
  }

  /**
   * Return all vote types marked as reaction.
   *
   * @return array
   *   Active reaction vote types.
   */
  public function allReactions() {
    return array_filter($this->voteTypeStorage->loadMultiple(), function (VoteType $entity) {
      return $entity->getThirdPartySetting('votingapi_reaction', 'reaction');
    });
  }

  /**
   * Return rendered list of active reactions.
   *
   * @param array $settings
   *   Field settings.
   * @param array $results
   *   Array containing Voting API voting results.
   *
   * @return array
   *   Rendered reactions.
   */
  public function getReactions(array $settings, array $results) {
    // Get only enabled reactions.
    $entities = array_filter($this->allReactions(), function (VoteType $entity) use ($settings) {
      return in_array($entity->id(), array_filter($settings['reactions']));
    });

    // Configure the object.
    $reactions = array_map(function (VoteType $entity) use ($settings, $results) {
      $reaction = [
        '#theme' => 'votingapi_reaction_item',
        '#reaction' => $entity->id(),
      ];

      if ($settings['show_icon']) {
        $reaction['#icon'] = $this->getIcon($entity);
      }

      if ($settings['show_label']) {
        $reaction['#label'] = $entity->label();
      }

      if ($settings['show_count']) {
        $reaction['#count'] = isset($results[$entity->id()]['vote_sum'])
          ? $results[$entity->id()]['vote_sum']
          : 0;
      }

      return $reaction;
    }, $entities);

    // Sorting.
    if ($settings['sort_reactions'] != 'none') {
      uasort($reactions, function ($a, $b) use ($settings, $results) {
        $count_a = isset($results[$a['#reaction']]['vote_count'])
          ? $results[$a['#reaction']]['vote_count'] : 0;
        $count_b = isset($results[$b['#reaction']]['vote_count'])
          ? $results[$b['#reaction']]['vote_count'] : 0;

        if ($settings['sort_reactions'] == 'desc') {
          return $count_a > $count_b ? -1 : 1;
        }
        else {
          return $count_a < $count_b ? -1 : 1;
        }
      });
    }

    // Render reactions.
    return array_map(function ($reaction) {
      return $this->renderer->render($reaction);
    }, $reactions);
  }

  /**
   * Store reaction from session variable.
   *
   * @param \Drupal\votingapi\Entity\Vote $entity
   *   Current vote entity.
   */
  public function rememberReaction(Vote $entity) {
    $_SESSION['votingapi_reaction'][implode(':', [
      $entity->getVotedEntityId(),
      $entity->getVotedEntityType(),
      $entity->get('field_name')->value,
    ])] = $entity->id();
  }

  /**
   * Remove reaction from session variable.
   *
   * @param \Drupal\votingapi\Entity\Vote $entity
   *   Current vote entity.
   */
  public function forgetReaction(Vote $entity) {
    unset($_SESSION['votingapi_reaction'][implode(':', [
      $entity->getVotedEntityId(),
      $entity->getVotedEntityType(),
      $entity->get('field_name')->value,
    ])]);
  }

  /**
   * Restore reaction from session variable.
   *
   * @param \Drupal\votingapi\Entity\Vote $entity
   *   Current vote entity.
   *
   * @return string|null
   *   Reaction vote id based on session.
   */
  public function recallReaction(Vote $entity) {
    $key = implode(':', [
      $entity->getVotedEntityId(),
      $entity->getVotedEntityType(),
      $entity->get('field_name')->value,
    ]);
    return !empty($_SESSION['votingapi_reaction'][$key])
      ? $_SESSION['votingapi_reaction'][$key]
      : NULL;
  }

  /**
   * Return URL to reaction icon.
   *
   * @param \Drupal\votingapi\Entity\VoteType $entity
   *   Current vote entity.
   * @param bool $default
   *   Default icon is used.
   *
   * @return string
   *   Reaction icon url.
   */
  public function getIcon(VoteType $entity, &$default = TRUE) {
    $path = implode('/', [
      drupal_get_path('module', 'votingapi_reaction'),
      'svg',
      '', // Trailing slash.
    ]);

    // Fallback icon.
    $url = $path . 'reaction_noicon.svg';
    // User defined icon.
    $icon = $entity->getThirdPartySetting('votingapi_reaction', 'icon');
    if ($icon && $file = $this->fileStorage->load($icon)) {
      $url = $file->getFileUri();
      $default = FALSE;
    }
    // Default icon.
    elseif (file_exists(DRUPAL_ROOT . "/$path" . $entity->id() . '.svg')) {
      $url = $path . $entity->id() . '.svg';
    }

    return file_create_url($url);
  }

}
