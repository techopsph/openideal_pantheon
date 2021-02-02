<?php

namespace Drupal\ckeditor_mentions;

use Symfony\Component\EventDispatcher\Event;

/**
 * Class CKEditorMentionEvent.
 *
 * @package Drupal\ckeditor_mentions
 */
class CKEditorMentionSuggestionEvent extends Event {

  const SUGGESTION = 'ckeditor_mentions.suggestion';


  /**
   * The keyword used by the user.
   *
   * @var string
   */
  protected $keyword;

  /**
   * The suggestion generated using the keyword.
   *
   * @var array
   */
  protected $suggestions = [];

  /**
   * CKEditorMentionEvent constructor.
   *
   * @param string $keyword
   *   The keyword  used by the user.
   */
  public function __construct($keyword) {
    $this->keyword = $keyword;
  }

  /**
   * Return the keyword searched by the user.
   *
   * @return string
   *   The keyword.
   */
  public function getKeyword() {
    return $this->keyword;
  }

  /**
   * Return the array of suggestion generated using the keyword.
   *
   * @return array
   *   Suggestion list.
   */
  public function getSuggestions() {
    return $this->suggestions;
  }

  /**
   * The suggestion list.
   *
   * @param array $suggestions
   *   The suggestion list.
   */
  public function setSuggestions(array $suggestions) {
    $this->suggestions = array_merge($suggestions, $this->suggestions);
  }

}
