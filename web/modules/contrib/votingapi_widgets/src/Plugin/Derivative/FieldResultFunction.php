<?php

namespace Drupal\votingapi_widgets\Plugin\Derivative;

use Drupal\Component\Plugin\Derivative\DeriverBase;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Plugin\Discovery\ContainerDeriverInterface;

/**
 * Deriver base class for field vote calculations.
 */
class FieldResultFunction extends DeriverBase implements ContainerDeriverInterface {

  /**
   * @var EntityFieldManagerInterface $entityField
   */
  protected $entityField;

  /**
   * Constructs a FieldResultFunction instance.
   *
   * @param \Drupal\Core\Entity\EntityFieldManagerInterface $entity_field_manager
   *   The entity field manager.
   */
  public function __construct(EntityFieldManagerInterface $entity_field_manager) {
    $this->entityField = $entity_field_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, $base_plugin_id) {
    return new static(
      $container->get('entity_field.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getDerivativeDefinitions($base_plugin_definition) {
    $instances = $this->entityField->getFieldMapByFieldType('voting_api_field');
    $this->derivatives = [];
    foreach ($instances as $entity_type => $fields) {
      foreach (array_keys($fields) as $field_name) {
        $plugin_id = $entity_type . '.' . $field_name;
        $this->derivatives[$plugin_id] = $base_plugin_definition;
      }
    }
    return $this->derivatives;
  }

}
