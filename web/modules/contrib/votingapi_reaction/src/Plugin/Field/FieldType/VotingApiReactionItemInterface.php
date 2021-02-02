<?php

namespace Drupal\votingapi_reaction\Plugin\Field\FieldType;

/**
 * Interface definition for reactions.
 */
interface VotingApiReactionItemInterface {

  /**
   * Reactions for this entity are hidden.
   */
  const HIDDEN = 0;

  /**
   * Reactions for this entity are closed.
   */
  const CLOSED = 1;

  /**
   * Reactions for this entity are open.
   */
  const OPEN = 2;

  /**
   * Reactions for this entity will rollover based on Voting API settings.
   */
  const VOTINGAPI_ROLLOVER = -1;

  /**
   * Reactions for this entity will never rollover.
   */
  const NEVER_ROLLOVER = -2;

  /**
   * Methods by which sessions for anonymous users will be detected.
   */
  const BY_COOKIES = 1;

  const BY_IP = 2;

}
