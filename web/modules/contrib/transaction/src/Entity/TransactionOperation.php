<?php

namespace Drupal\transaction\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\transaction\TransactionOperationInterface;

/**
 * Transaction operations provide templates that describe transactions.
 *
 * @ConfigEntityType(
 *   id = "transaction_operation",
 *   label = @Translation("Transaction operation"),
 *   label_singular = @Translation("Transaction operation"),
 *   label_plural = @Translation("Transaction operations"),
 *   label_count = @PluralTranslation(
 *     singular = "@count transaction operation",
 *     plural = "@count transaction operations",
 *   ),
 *   admin_permission = "administer transaction types",
 *   handlers = {
 *     "list_builder" = "Drupal\transaction\TransactionOperationListBuilder",
 *     "form" = {
 *       "add" = "Drupal\transaction\Form\TransactionOperationForm",
 *       "edit" = "Drupal\transaction\Form\TransactionOperationForm",
 *       "delete" = "Drupal\Core\Entity\EntityDeleteForm",
 *     },
 *   },
 *   config_prefix = "operation",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *   },
 *   config_export = {
 *     "id",
 *     "label",
 *     "transaction_type",
 *     "description",
 *     "details",
 *   },
 *   links = {
 *     "add-form" = "/admin/config/workflow/transaction/edit/{transaction_type}/operation/add",
 *     "edit-form" = "/admin/config/workflow/transaction/edit/{transaction_type}/operation/edit/{transaction_operation}",
 *     "delete-form" = "/admin/config/workflow/transaction/edit/{transaction_type}/operation/delete/{transaction_operation}",
 *     "collection" = "/admin/config/workflow/transaction/edit/{transaction_type}/operation",
 *   },
 * )
 */
class TransactionOperation extends ConfigEntityBase implements TransactionOperationInterface {

  /**
   * The transaction operation ID.
   *
   * @var string
   */
  protected $id;

  /**
   * The transaction operation label.
   *
   * @var string
   */
  protected $label;

  /**
   * The transaction type to which the operation belongs.
   *
   * @var string
   */
  protected $transaction_type;

  /**
   * The transaction operation description template.
   *
   * @var string
   */
  protected $description = '';

  /**
   * The transaction operation detail templates.
   *
   * @var string[]
   */
  protected $details = [];

  /**
   * {@inheritdoc}
   */
  public function getTransactionTypeId() {
    return $this->transaction_type;
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return $this->description;
  }

  /**
   * {@inheritdoc}
   */
  public function setDescription($description = '') {
    $this->description = $description;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getDetails() {
    return $this->details;
  }

  /**
   * {@inheritdoc}
   */
  public function setDetails(array $details = []) {
    $this->details = $details;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function addDetail($detail) {
    $this->details[] = $detail;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  protected function urlRouteParameters($rel) {
    $uri_route_parameters = parent::urlRouteParameters($rel);

    // Add the transaction type ID when available.
    if ($transaction_type_id = $this->getTransactionTypeId()) {
      $uri_route_parameters['transaction_type'] = $transaction_type_id;
    }

    return $uri_route_parameters;
  }

  /**
   * {@inheritdoc}
   */
  public function calculateDependencies() {
    parent::calculateDependencies();
    $transaction_type = TransactionType::load($this->getTransactionTypeId());
    $this->addDependency($transaction_type->getConfigDependencyKey(), $transaction_type->getConfigDependencyName());
    return $this;
  }

}
