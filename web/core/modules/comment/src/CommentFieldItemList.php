<?php

namespace Drupal\comment;

use Drupal\comment\Entity\Comment;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Field\FieldItemList;
use Drupal\Core\Session\AccountInterface;

/**
 * Defines a item list class for comment fields.
 */
class CommentFieldItemList extends FieldItemList {

  /**
   * {@inheritdoc}
   */
  public function get($index) {
    // The Field API only applies the "field default value" to newly created
    // entities. In the specific case of the "comment status", though, we need
    // this default value to be also applied for existing entities created
    // before the comment field was added, which have no value stored for the
    // field.
    if ($index == 0 && empty($this->list)) {
      $field_default_value = $this->getFieldDefinition()->getDefaultValue($this->getEntity());
      return $this->appendItem($field_default_value[0]);
    }
    return parent::get($index);
  }

  /**
   * {@inheritdoc}
   */
  public function offsetExists($offset) {
    // For consistency with what happens in get(), we force offsetExists() to
    // be TRUE for delta 0.
    if ($offset === 0) {
      return TRUE;
    }
    return parent::offsetExists($offset);
  }

  /**
   * {@inheritdoc}
   */
  public function access($operation = 'view', AccountInterface $account = NULL, $return_as_object = FALSE) {
    $account = $account ?: \Drupal::currentUser();
    if ($operation === 'edit') {
      // Only users with administer comments permission can edit the comment
      // status field.
      $result = AccessResult::allowedIfHasPermission($account, 'administer comments');
      return $return_as_object ? $result : $result->isAllowed();
    }
    if ($operation === 'view') {
      // This operation is only used by EntityViewDisplay::buildMultiple().
      // In this case check both: 'view' or 'create' access, since this field
      // is considered as composition of listed comments and comment form.
      // Decision about to show only comments or only comment form or both
      // will be made by CommentDefaultFormatter::viewElements() later.
      // Uses recursive calls on same method invoking lower operations.
      $result = $this->access('view only', $account, TRUE);
      if (!$result->isAllowed()) {
        $result = $result->orIf($this->access('create', $account, TRUE));
      }

      return $return_as_object ? $result : $result->isAllowed();
    }
    if ($operation === 'view only') {
      // In contrast to 'view', this operation is used as the lowest
      // operation by various methods to check only single permission on last
      // comment.
      return $this->lastCommentAccess($account, 'view', $return_as_object);
    }
    if ($operation === 'create') {
      // In contrast to 'view', this operation is used as the lowest
      // operation by various methods to check only single 'create' permission
      // on comment entity.
      $bundle = $this->getSetting('comment_type');
      $access_control_handler = \Drupal::entityTypeManager()->getAccessControlHandler('comment');
      $result = $access_control_handler->createAccess($bundle, $account, [], TRUE);

      return $return_as_object ? $result : $result->isAllowed();
    }
    return parent::access($operation, $account, $return_as_object);
  }

  /**
  * Check access on last comment.
  *
  * @param \Drupal\Core\Session\AccountInterface $account
  *   The user for which to check access.
  * @param string $operation
  *   (optional) Operation to perform. Defaults to 'view'.
  * @param bool $return_as_object
  *   (optional) Defaults to FALSE.
  *
  * @return bool|AccessResult
  */
  public function lastCommentAccess(AccountInterface $account, $operation = 'view', $return_as_object = FALSE) {
    // If there are no comments, then access does not matter.
    $result = AccessResult::allowed();
    // Load last comment in thread.
    $last_comment_id = $this->first()->getValue();
    if (isset($last_comment_id['cid'])) {
      $cid = intval($last_comment_id['cid']);
      if ($comment = Comment::load($cid)) {
        // Allow if access on comment is allowed.
        $result = $comment->access($operation, $account, $return_as_object);
      }
    }
    return $result;
  }

}
