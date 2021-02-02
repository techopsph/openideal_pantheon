<?php

namespace Drupal\votingapi_reaction;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityFieldManager;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Field permissions.
 */
class FieldPermissions implements ContainerInjectionInterface {

  use StringTranslationTrait;

  /**
   * Entity field manager service.
   *
   * @var \Drupal\Core\Entity\EntityFieldManager
   */
  protected $fieldManager;

  /**
   * Constructs a FieldPermissionsService instance.
   *
   * @param \Drupal\Core\Entity\EntityFieldManager $fieldManager
   *   Entity field manager service.
   */
  public function __construct(EntityFieldManager $fieldManager) {
    $this->fieldManager = $fieldManager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static($container->get('entity_field.manager'));
  }

  /**
   * Get implemets permissions invoke in field_permissions.permissions.yml.
   *
   * @return array
   *   Add custom permissions.
   */
  public function permissions() {
    $map = $this->fieldManager->getFieldMapByFieldType('votingapi_reaction');
    $permissions = [];

    foreach ($map as $entity_type => $info) {
      foreach ($info as $field_name => $field_info) {
        foreach ($field_info['bundles'] as $bundle) {
          $permissions['view reactions on ' . $entity_type . ':' . $bundle . ':' . $field_name] = [
            'title' => $this->t('View reactions to field %field in bundle %bundle of entity type %type', [
              '%type' => $entity_type,
              '%bundle' => $bundle,
              '%field' => $field_name,
            ]),
          ];
          $permissions['create reaction on ' . $entity_type . ':' . $bundle . ':' . $field_name] = [
            'title' => $this->t('React to field %field in bundle %bundle of entity type %type', [
              '%type' => $entity_type,
              '%bundle' => $bundle,
              '%field' => $field_name,
            ]),
          ];
          $permissions['modify reaction on ' . $entity_type . ':' . $bundle . ':' . $field_name] = [
            'title' => $this->t('Modify reaction to field %field in bundle %bundle of entity type %type', [
              '%type' => $entity_type,
              '%bundle' => $bundle,
              '%field' => $field_name,
            ]),
          ];
          $permissions['control reaction status on ' . $entity_type . ':' . $bundle . ':' . $field_name] = [
            'title' => $this->t('Conrol reactions status in field %field in bundle %bundle of entity type %type', [
              '%type' => $entity_type,
              '%bundle' => $bundle,
              '%field' => $field_name,
            ]),
          ];
        }
      }
    }

    return $permissions;
  }

}
