<?php

namespace Drupal\administerusersbyrole\Constraint;

use Drupal\user\Plugin\Validation\Constraint\UserMailRequiredValidator;
use Symfony\Component\Validator\Constraint;

/**
 * Checks if the user's email address is provided if required.
 *
 * The user mail field is NOT required if account originally had no mail set
 * and the user performing the edit has permission to set empty user mail.
 * This allows users without email address to be edited and deleted.
 */
class OverrideUserMailRequiredValidator extends UserMailRequiredValidator {

  /**
   * {@inheritdoc}
   */
  public function validate($items, Constraint $constraint) {
    /** @var \Drupal\Core\Field\FieldItemListInterface $items */
    /** @var \Drupal\user\UserInterface $account */
    $account = $items->getEntity();
    $existing_value = NULL;
    if ($account->id()) {
      $account_unchanged = \Drupal::entityTypeManager()
        ->getStorage('user')
        ->loadUnchanged($account->id());
      $existing_value = $account_unchanged->getEmail();
    }

    $has_permission = \Drupal::currentUser()->hasPermission('administer users') || \Drupal::currentUser()->hasPermission('allow empty user mail');
    $required = !(!$existing_value && $has_permission);

    if ($required && (!isset($items) || $items->isEmpty())) {
      $this->context->addViolation($constraint->message, ['@name' => $account->getFieldDefinition('mail')->getLabel()]);
    }
  }

}
