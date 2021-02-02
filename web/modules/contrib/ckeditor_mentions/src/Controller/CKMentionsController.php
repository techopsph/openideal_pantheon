<?php

namespace Drupal\ckeditor_mentions\Controller;

use Drupal\ckeditor_mentions\CKEditorMentionSuggestionEvent;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Database\Connection;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Route callback for matches.
 */
class CKMentionsController extends ControllerBase {

  /**
   * The Event Dispatcher.
   *
   * @var \Symfony\Component\EventDispatcher\EventDispatcherInterface
   */
  protected $eventDispatcher;

  /**
   * Database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * CKMentionsController constructor.
   *
   * @param \Symfony\Component\EventDispatcher\EventDispatcherInterface $eventDispatcher
   *   The Event dispatcher service.
   * @param \Drupal\Core\Database\Connection $database
   *   The database connection.
   */
  public function __construct(EventDispatcherInterface $eventDispatcher, Connection $database) {
    $this->eventDispatcher = $eventDispatcher;
    $this->database = $database;
    $this->entityTypeManager();
    $this->moduleHandler();
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('event_dispatcher'),
      $container->get('database')
    );
  }

  /**
   * Return a list of suggestions based in the keyword provided by the user.
   *
   * @param string $match
   *   Match value.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse|void
   *   Json of matches.
   */
  public function getRealNameMatch($match = '') {
    // Initialize the response array.
    $response_array = [];
    // Load up user storage.
    /* @var \Drupal\user\UserStorage $user_storage **/
    $user_storage = $this->entityTypeManager->getStorage('user');

    $ids = $this->getUserIds($match);

    // Load up all user IDs.
    $users = $user_storage->loadMultiple($ids);

    // The image style to use.
    // @Todo: Check if the style was deleted. Move style type into configuration form?
    $style = $this->entityTypeManager->getStorage('image_style')->load('mentions_icon');

    // @Todo: add placeholder image.
    $placeholder_image = base_path() . $this->moduleHandler->getModule('ckeditor_mentions')->getPath() . '/img/placeholder.png';

    // Form response array.
    /**
     * @var \Drupal\user\Entity\User $user
     */
    foreach ($users as $id => $user) {
      $user_image_url = NULL;
      if ($user->hasField('user_picture') && !$user->user_picture->isEmpty()) {
        $user_image_url = $style->buildUrl($user->user_picture->entity->getFileUri());
      }

      $response_array[] = [
        'id' => $id,
        'realname' => $user->realname,
        'account_name' => $user->getAccountName(),
        'email' => $user->getEmail(),
        'avatar' => $user_image_url ?? $placeholder_image,
        'user_page' => $user->toUrl()->toString(),
      ];

      $suggestion_event = new CKEditorMentionSuggestionEvent($match);
      $suggestion_event->setSuggestions($response_array);
      $this->eventDispatcher->dispatch(CKEditorMentionSuggestionEvent::SUGGESTION, $suggestion_event);
      $response_array = $suggestion_event->getSuggestions();
    }

    return new JsonResponse($response_array);
  }

  /**
   * Get user ids matched the string.
   *
   * @param string $match
   *   The string to match.
   *
   * @return array
   *   User ids.
   */
  protected function getUserIds(string $match) {
    $query = $this->database->select('realname', 'rn');

    // @Todo: Add ability to match the account name?
    $query->leftJoin('users_field_data', 'ud', 'ud.uid = rn.uid');
    $query->fields('rn', ['uid', 'realname']);
    $query->condition('rn.realname', '%' . $query->escapeLike($match) . '%', 'LIKE');
    $query->isNotNull('rn.realname');
    $query->condition('ud.status', 1);
    $query->condition('rn.uid', $this->currentUser()->id(), '!=');

    // @Todo: add ability to limit query and sort?
    return $query->execute()
      ->fetchCol();
  }

}
