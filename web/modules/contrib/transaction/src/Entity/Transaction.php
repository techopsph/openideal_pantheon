<?php

namespace Drupal\transaction\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\transaction\TransactionInterface;
use Drupal\user\UserInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\transaction\InvalidTransactionStateException;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\transaction\TransactionTypeInterface;

/**
 * Provides the transaction content entity.
 *
 * @ContentEntityType(
 *   id = "transaction",
 *   label = @Translation("Transaction"),
 *   label_singular = @Translation("transaction"),
 *   label_plural = @Translation("transactions"),
 *   label_count = @PluralTranslation(
 *     singular = "@count transaction",
 *     plural = "@count transaction",
 *   ),
 *   admin_permission = "administer transactions",
 *   bundle_label = @Translation("Transaction type"),
 *   handlers = {
 *     "list_builder" = "Drupal\transaction\TransactionListBuilder",
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "access" = "Drupal\transaction\TransactionAccessControlHandler",
 *     "views_data" = "Drupal\transaction\TransactionViewsData",
 *     "form" = {
 *       "default" = "Drupal\transaction\Form\TransactionForm",
 *       "add" = "Drupal\transaction\Form\TransactionForm",
 *       "edit" = "Drupal\transaction\Form\TransactionForm",
 *       "delete" = "Drupal\transaction\Form\TransactionDeleteForm",
 *       "execute" = "Drupal\transaction\Form\TransactionExecuteForm"
 *     },
 *     "route_provider" = {
 *       "default" = "Drupal\Core\Entity\Routing\DefaultHtmlRouteProvider",
 *     },
 *     "transactor" = "Drupal\transaction\TransactorHandler",
 *   },
 *   base_table = "transaction",
 *   translatable = FALSE,
 *   entity_keys = {
 *     "id" = "id",
 *     "bundle" = "type",
 *     "uuid" = "uuid",
 *     "uid" = "uid",
 *   },
 *   bundle_entity_type = "transaction_type",
 *   field_ui_base_route = "entity.transaction_type.edit_form",
 *   permission_granularity = "bundle",
 *   links = {
 *     "canonical" = "/transaction/{transaction}",
 *     "add-form" = "/transaction/add/{transaction_type}/{target_entity_type}/{target_entity}",
 *     "edit-form" = "/transaction/{transaction}/edit",
 *     "delete-form" = "/transaction/{transaction}/delete",
 *     "execute-form" = "/transaction/{transaction}/execute",
 *     "collection" = "/transaction/{transaction_type}/{target_entity_type}/{target_entity}",
 *   }
 * )
 */
class Transaction extends ContentEntityBase implements TransactionInterface {

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    // Generic base fields.
    $fields = parent::baseFieldDefinitions($entity_type);

    // Creation timestamp.
    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Authored on'))
      ->setDescription(t('The time that the transaction was created.'))
      ->setRequired(TRUE)
      ->setDisplayOptions('view', [
        'type' => 'timestamp',
        'weight' => 0,
      ])
      ->setDisplayConfigurable('view', TRUE)
      ->setDisplayOptions('form', [
        'type' => 'datetime_timestamp',
        'weight' => 10,
      ])
      ->setDisplayConfigurable('form', TRUE);

    // User ID (transaction author).
    $fields['uid'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Authored by'))
      ->setDescription(t('The user ID of the author.'))
      ->setRequired(TRUE)
      ->setSetting('target_type', 'user')
      ->setSetting('handler', 'default')
      ->setDefaultValueCallback('Drupal\transaction\Entity\Transaction::getCurrentUserId')
      ->setDisplayOptions('view', [
        'type' => 'author',
        'weight' => 0,
      ])
      ->setDisplayConfigurable('view', TRUE)
      ->setDisplayOptions('form', [
        'type' => 'entity_reference_autocomplete',
        'weight' => 5,
        'settings' => [
          'match_operator' => 'CONTAINS',
          'size' => '60',
          'autocomplete_type' => 'tags',
          'placeholder' => '',
        ],
      ])
      ->setDisplayConfigurable('form', TRUE);

    // Target entity.
    $fields['target_entity'] = BaseFieldDefinition::create('dynamic_entity_reference')
      ->setLabel(t('Target entity'))
      ->setDescription(t('The target entity of the transaction.'))
      ->setRequired(TRUE)
      ->setDisplayOptions('view', [
        'type' => 'author',
        'weight' => 0,
      ])
      ->setDisplayConfigurable('view', TRUE);

    // Operation.
    $fields['operation'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Operation'))
      ->setDescription(t('Reference to a transaction operation that describes the transaction.'))
      ->setRequired(FALSE)
      ->setSetting('target_type', 'transaction_operation')
      ->setSetting('handler', 'default:transaction_operation')
      ->setDisplayOptions('view', [
        'type' => 'entity_reference_label',
        'weight' => 0,
      ])
      ->setDisplayConfigurable('view', TRUE)
      ->setDisplayOptions('form', [
        'type' => 'options_select',
        'weight' => 0,
        'settings' => [
          'size' => '60',
        ],
      ])
      ->setDisplayConfigurable('form', TRUE);

    // Execution timestamp.
    $fields['executed'] = BaseFieldDefinition::create('timestamp')
      ->setLabel(t('Executed on'))
      ->setDescription(t('The time that the transaction was executed.'))
      ->setRequired(FALSE)
      ->setDisplayOptions('view', [
        'type' => 'timestamp',
        'weight' => 0,
      ])
      ->setDisplayConfigurable('view', TRUE);

    // The execution sequence number.
    $fields['execution_sequence'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Execution sequence'))
      ->setDescription(t('The order number on which this transaction was executed.'))
      ->setSetting('unsigned', TRUE)
      ->setReadOnly(TRUE)
      ->setDisplayOptions('view', [
        'type' => 'number_integer',
        'region' => 'hidden',
      ])
      ->setDisplayConfigurable('view', TRUE);

    // The execution result code.
    $fields['result_code'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Result code'))
      ->setReadOnly(TRUE)
      ->setRequired(TRUE);

    // The execution result message.
    $fields['result_message'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Result message'))
      ->setComputed(TRUE)
      ->setClass('\Drupal\transaction\Plugin\Field\TransactionResultMessageItemList')
      ->setDisplayOptions('view', [
        'type' => 'string',
        'weight' => 0,
      ])
      ->setDisplayConfigurable('view', TRUE);

    // The user that executes the transaction.
    $fields['executor'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Executed by'))
      ->setDescription(t('The user ID of the user that executed the transaction.'))
      ->setRequired(FALSE)
      ->setSetting('target_type', 'user')
      ->setSetting('handler', 'default')
      ->setDisplayOptions('view', [
        'type' => 'entity_reference_label',
        'weight' => 0,
      ])
      ->setDisplayConfigurable('view', TRUE);

    // Properties.
    $fields['properties'] = BaseFieldDefinition::create('map')
      ->setLabel(t('Properties'))
      ->setDescription(t('A name-value map managed by the transactor plugin.'));

    // Description (computed).
    $fields['description'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Description'))
      ->setDescription(t('A human-readable description of the transaction.'))
      ->setComputed(TRUE)
      ->setClass('\Drupal\transaction\Plugin\Field\TransactionDescriptionItemList')
      ->setDisplayOptions('view', [
        'type' => 'string',
        'weight' => 0,
      ])
      ->setDisplayConfigurable('view', TRUE);

    // Details (computed).
    $fields['details'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Details'))
      ->setDescription(t('A list of details of the transaction.'))
      ->setComputed(TRUE)
      ->setClass('\Drupal\transaction\Plugin\Field\TransactionDetailsItemList')
      ->setCardinality(FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED)
      ->setDisplayOptions('view', [
        'type' => 'string',
        'weight' => 0,
      ])
      ->setDisplayConfigurable('view', TRUE);

    return $fields;
  }

  /**
   * Default value callback for 'uid' base field definition.
   *
   * @see ::baseFieldDefinitions()
   *
   * @return array
   *   An array of default values.
   */
  public static function getCurrentUserId() {
    return [\Drupal::currentUser()->id()];
  }

  /**
   * {@inheritdoc}
   */
  public function label() {
    return strip_tags($this->getDescription());
  }

  /**
   * {@inheritdoc}
   */
  public function getOwner() {
    return $this->get('uid')->entity;
  }

  /**
   * {@inheritdoc}
   */
  public function setOwner(UserInterface $account) {
    $this->set('uid', $account->id());
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getOwnerId() {
    return $this->getEntityKey('uid');
  }

  /**
   * {@inheritdoc}
   */
  public function setOwnerId($uid) {
    $this->set('uid', $uid);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getExecutionSequence() {
    return $this->get('execution_sequence')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setExecutionSequence($execution_sequence) {
    if ($execution_sequence !== NULL && $execution_sequence < 1) {
      throw new \InvalidArgumentException('The execution sequence number must be greater than 0.');
    }
    $this->set('execution_sequence', $execution_sequence);

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getExecutionTime() {
    return $this->get('executed')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setExecutionTime($timestamp) {
    $this->set('executed', $timestamp);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getCreatedTime() {
    return $this->get('created')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setCreatedTime($timestamp) {
    $this->set('created', $timestamp);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getProperty($key) {
    $properties = $this->getProperties();
    return isset($properties[$key]) ? $properties[$key] : NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function setProperty($key, $value = NULL) {
    $item = $this->get('properties')->first() ?: $this->get('properties')->appendItem();

    $map = $item->getValue();
    if ($value === NULL) {
      unset($map[$key]);
    }
    else {
      $map[$key] = $value;
    }

    $item->setValue($map);

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getProperties() {
    $properties = $this->get('properties');
    return $properties->isEmpty() ? [] : $properties->first()->toArray();
  }

  /**
   * {@inheritdoc}
   */
  public function execute($save = TRUE, UserInterface $user = NULL) {
    return $this->transactorHandler()->doExecute($this, $save, $user);
  }

  /**
   * {@inheritdoc}
   */
  public function getResultCode() {
    return $this->get('result_code')->value ?: FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function setResultCode($code) {
    $this->get('result_code')->setValue($code);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getResultMessage($reset = FALSE) {
    if ($reset || $this->get('result_message')->isEmpty()) {
      return $this->transactorHandler()->composeResultMessage($this);
    }

    return $this->get('result_message')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function getExecutorId() {
    return $this->get('executor')->isEmpty() ? FALSE : $this->get('executor')->target_id;
  }

  /**
   * {@inheritdoc}
   */
  public function getExecutor() {
    return $this->get('executor')->isEmpty() ? NULL : $this->get('executor')->entity;
  }

  /**
   * {@inheritdoc}
   */
  public function setExecutor(UserInterface $user) {
    $this->get('executor')->setValue($user->id());
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getType() {
    return $this->get('type')->entity;
  }

  /**
   * {@inheritdoc}
   */
  public function getTypeId() {
    return $this->get('type')->getString();
  }

  /**
   * {@inheritdoc}
   */
  public function getOperation() {
    return $this->get('operation')->entity;
  }

  /**
   * {@inheritdoc}
   */
  public function getOperationId() {
    return $this->get('operation')->target_id;
  }

  /**
   * {@inheritdoc}
   */
  public function setOperation($operation = NULL) {
    $this->set('operation', is_string($operation) ? $operation : $operation->id());
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getPrevious() {
    if ($this->isPending()) {
      throw new InvalidTransactionStateException();
    }

    return $this->transactorHandler()->getPreviousTransaction($this);
  }

  /**
   * {@inheritdoc}
   */
  public function getNext() {
    if ($this->isPending()) {
      throw new InvalidTransactionStateException();
    }

    return $this->transactorHandler()->getNextTransaction($this);
  }

  /**
   * {@inheritdoc}
   */
  public function getTargetEntity() {
    return $this->get('target_entity')->isEmpty() ? NULL : $this->get('target_entity')->entity;
  }

  /**
   * {@inheritdoc}
   */
  public function getTargetEntityId() {
    return $this->get('target_entity')->isEmpty() ? FALSE : $this->get('target_entity')->target_id;
  }

  /**
   * {@inheritdoc}
   */
  public function setTargetEntity(ContentEntityInterface $entity) {
    $this->get('target_entity')->setValue($entity);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription($reset = FALSE) {
    if ($reset || $this->get('description')->isEmpty()) {
      return $this->transactorHandler()->composeDescription($this);
    }

    return $this->get('description')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function getDetails($reset = FALSE) {
    if ($reset) {
      return $this->transactorHandler()->composeDetails($this);
    }

    $details = [];
    foreach ($this->get('details')->getValue() as $detail_value) {
      $details[] = $detail_value['value'];
    }

    return $details;
  }

  /**
   * {@inheritdoc}
   */
  public function isPending() {
    return $this->getExecutionTime() === NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function preSave(EntityStorageInterface $storage) {
    parent::preSave($storage);

    // Execute the transaction if inmediate execution is enabled.
    if ($this->isNew()
      && $this->getType()->getOption('execution') == TransactionTypeInterface::EXECUTION_IMMEDIATE) {
      $this->execute(FALSE);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function postSave(EntityStorageInterface $storage, $update = TRUE) {
    parent::postSave($storage, $update);

    // Save the target entity if it was updated during the transaction
    // execution.
    $from_pending = isset($this->original)
      && !$this->original->getProperty(TransactionInterface::PROPERTY_TARGET_ENTITY_UPDATED);

    if (($from_pending || !$update)
      && $this->getProperty(TransactionInterface::PROPERTY_TARGET_ENTITY_UPDATED)) {
      $this->getTargetEntity()->save();
    }
  }

  /**
   * Gets the transactor handler.
   *
   * @return \Drupal\transaction\TransactorHandlerInterface
   *   The transactor entity handler.
   */
  protected function transactorHandler() {
    return $this->entityTypeManager()->getHandler($this->getEntityTypeId(), 'transactor');
  }

  /**
   * {@inheritdoc}
   */
  protected function urlRouteParameters($rel) {
    $uri_route_parameters = parent::urlRouteParameters($rel);
    if ($rel === 'collection' || $rel == 'add-form') {
      $uri_route_parameters['transaction_type'] = $this->getTypeId();
      $uri_route_parameters['target_entity_type'] = $this->getType()->getTargetEntityTypeId();
      // Transactions with empty target entity field is inconsistent. Returning
      // 0 to avoid exceptions. URL will end in a page not found.
      $uri_route_parameters['target_entity'] = $this->getTargetEntityId() ?: 0;
    }

    return $uri_route_parameters;
  }

}
