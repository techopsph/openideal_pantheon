<?php

namespace Drupal\ckeditor_mentions;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Database\Database;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\Core\Session\AccountInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use DOMDocument;

/**
 * Class MentionService.
 *
 * @package Drupal\ckeditor_mentions
 */
class MentionEventDispatcher {

  /**
   * ConfigFactory Service.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * The event dispatcher.
   *
   * @var \Symfony\Component\EventDispatcher\EventDispatcherInterface
   */
  protected $eventDispatcher;

  /**
   * MentionService constructor.
   *
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The current user.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   A configuration factory instance.
   * @param \Symfony\Component\EventDispatcher\EventDispatcherInterface $event_dispatcher
   *   The event dispatcher.
   */
  public function __construct(AccountInterface $current_user, ConfigFactoryInterface $config_factory, EventDispatcherInterface $event_dispatcher) {
    $this->configFactory = $config_factory;
    $this->currentUser = $current_user;
    $this->eventDispatcher = $event_dispatcher;
  }

  /**
   * Triggers the Mention Event.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   Trigger the mention event.
   * @param string $event_name
   *   The name of the event.
   */
  public function dispatchMentionEvent(EntityInterface $entity, $event_name) {
    // Load the Symfony event dispatcher object through services.
    $dispatcher = $this->eventDispatcher;
    // Creating our event class object.
    $mentioned_users = $this->getMentionsFromEntity($entity);
    // Nothing to do here, if mentioned users not present.
    if (empty($mentioned_users)) {
      return;
    }
    $event = new CKEditorMentionEvent($entity, $mentioned_users);
    // Dispatching the event through the ‘dispatch’  method, passing event name
    // and event object ‘$event’ as parameters.
    $dispatcher->dispatch($event_name, $event);
  }

  /**
   * Reads all the fields from an entity and return all the users mentioned.
   *
   * The array returned has this format:
   *
   * [user_id] => [
   *   'field_name' => [
   *     'delta' => [
   *       0 => 0,
   *       1 => 1,
   *       2 => 2,
   *     ]
   *   ]
   * ];
   *
   * The first key is the user id, the next key is the field_name where the
   * user was mentioned and finally the deltas of the fields.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity from which will get the mentions.
   *
   * @return array
   *   The users mentioned.
   */
  public function getMentionsFromEntity(EntityInterface $entity) {
    $users_mentioned = [];
    // Check if some of the fields is using the CKEditor editor.
    if (!$entity instanceof FieldableEntityInterface) {
      return $users_mentioned;
    }

    $bundle_fields = $entity->getFieldDefinitions();
    $format_using_mentions = $this->getTexformatsUsingMentions();

    foreach ($bundle_fields as $field_name => $field) {
      $field_value = $entity->get($field_name)->getValue();
      foreach ($field_value as $key => $item) {
        if (isset($item['format']) && in_array($item['format'], $format_using_mentions)) {
          foreach ($this->getMentionedUsers($item['value']) as $uid) {
            $users_mentioned[$uid][$field_name]['delta'][$key] = $key;
          }
        }
      }
    }
    return $users_mentioned;
  }

  /**
   * Returns the list of text formats using the Mentions plugin.
   *
   * @return array
   *   An array with the editors using the mentions plugin.
   */
  public function getTexformatsUsingMentions() {
    $config_factory = $this->configFactory;
    $editor_using_mentions = [];
    foreach ($config_factory->listAll('editor.editor.') as $editor_name) {
      $editor = $config_factory->getEditable($editor_name);
      $editor = $editor->get();
      if (isset($editor['settings']['plugins']['mentions']) && $editor['settings']['plugins']['mentions']['enable']) {
        $editor_using_mentions[] = $editor['format'];
      }
    }

    return $editor_using_mentions;
  }

  /**
   * Returns an array of the user mentioned in the text.
   *
   * @param string $field_value
   *   The field text $field_text.
   *
   * @return array
   *   An array with the uid of the user mentioned.
   */
  public function getMentionedUsers($field_value) {
    $users_mentioned = [];
    $database = Database::getConnection('default');
    $current_user_uid = $this->currentUser->id();

    if (empty($field_value)) {
      return $users_mentioned;
    }

    $dom = new DOMDocument();
    $dom->loadHTML($field_value);
    $anchors = $dom->getElementsByTagName('a');
    foreach ($anchors as $anchor) {
      $mentioned_user_id = $anchor->getAttribute('data-mention');
      $link_text = $anchor->textContent;

      // Strip the @ from the beginins of the name.
      $link_text = ltrim($link_text, '@');

      if (empty($mentioned_user_id)) {
        continue;
      }

      // Third party modules can send custom suggestion via an event, if the
      // user_id is not numeric then let it pass as it was sent so it can
      // be handled by the third party module.
      if (!is_numeric($mentioned_user_id)) {
        $users_mentioned[$mentioned_user_id] = $mentioned_user_id;
        continue;
      }

      $query = $database->select('realname', 'rn');
      $query->fields('rn', ['realname']);
      $query->condition('rn.uid', $mentioned_user_id);

      // Exclude currently logged in user from returned list.
      if ($current_user_uid) {
        $query->condition('rn.uid', $current_user_uid, '!=');
      }

      $result = $query->execute();
      $result = $result->fetch();
      $realname = $result->realname;

      // Check if the realname is used inside the link.
      if ($link_text !== $realname) {
        continue;
      }

      $users_mentioned[$mentioned_user_id] = $mentioned_user_id;
    }

    return $users_mentioned;
  }

}
