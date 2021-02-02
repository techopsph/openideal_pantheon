<?php

namespace Drupal\transaction;

use Drupal\Core\Cache\MemoryCache\MemoryCacheInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Routing\RouteBuilderInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Cache\CacheTagsInvalidatorInterface;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Config\Entity\ConfigEntityStorage;
use Drupal\Component\Uuid\UuidInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Entity\EntityInterface;

/**
 * Transaction type storage handler.
 */
class TransactionTypeStorage extends ConfigEntityStorage {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The field manager.
   *
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  protected $fieldManager;

  /**
   * The route builder.
   *
   * @var \Drupal\Core\Routing\RouteBuilderInterface
   */
  protected $routeBuilder;

  /**
   * The cache tags invalidator.
   *
   * @var \Drupal\Core\Cache\CacheTagsInvalidatorInterface
   */
  protected $cacheTagsInvalidator;

  /**
   * The cache discovery.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface
   */
  protected $cacheDiscovery;

  /**
   * Constructs the TransactionTypeFormBase object.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type definition.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory service.
   * @param \Drupal\Component\Uuid\UuidInterface $uuid_service
   *   The UUID service.
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   *   The language manager.
   * @param \Drupal\Core\Cache\MemoryCache\MemoryCacheInterface|null $memory_cache
   *   The memory cache backend.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Entity\EntityFieldManagerInterface $field_manager
   *   The entity field manager.
   * @param \Drupal\Core\Routing\RouteBuilderInterface $route_builder
   *   The route builder.
   * @param \Drupal\Core\Cache\CacheTagsInvalidatorInterface $cache_tags_invalidator
   *   The cache tags invalidator.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_discovery
   *   The cache discovery.
   */
  public function __construct(EntityTypeInterface $entity_type, ConfigFactoryInterface $config_factory, UuidInterface $uuid_service, LanguageManagerInterface $language_manager, MemoryCacheInterface $memory_cache = NULL, EntityTypeManagerInterface $entity_type_manager, EntityFieldManagerInterface $field_manager, RouteBuilderInterface $route_builder, CacheTagsInvalidatorInterface $cache_tags_invalidator, CacheBackendInterface $cache_discovery) {
    parent::__construct($entity_type, $config_factory, $uuid_service, $language_manager, $memory_cache);
    $this->entityTypeManager = $entity_type_manager;
    $this->fieldManager = $field_manager;
    $this->routeBuilder = $route_builder;
    $this->cacheTagsInvalidator = $cache_tags_invalidator;
    $this->cacheDiscovery = $cache_discovery;
  }

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type) {
    return new static(
      $entity_type,
      $container->get('config.factory'),
      $container->get('uuid'),
      $container->get('language_manager'),
      $container->get('entity.memory_cache'),
      $container->get('entity_type.manager'),
      $container->get('entity_field.manager'),
      $container->get('router.builder'),
      $container->get('cache_tags.invalidator'),
      $container->get('cache.discovery')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function doPostSave(EntityInterface $entity, $update) {
    parent::doPostSave($entity, $update);

    // Update local task in the target entity type.
    if ($this->updateLocalTask($entity, $entity->getOption('local_task', FALSE))) {
      $this->clearLocalTaskCache();
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function doDelete($entities) {
    parent::doDelete($entities);

    // Remove existent local task in the target entity type.
    $changes = FALSE;
    foreach ($entities as $entity) {
      $changes = $changes || $this->updateLocalTask($entity, FALSE);
    }
    if ($changes) {
      $this->clearLocalTaskCache();
    }
  }

  /**
   * Update the settings local task.
   *
   * @param \Drupal\transaction\TransactionTypeInterface $transaction_type
   *   The transaction type.
   * @param bool $has_tab
   *   Indicates if the transaction type has a local task in the target entity.
   *
   * @return bool
   *   TRUE if the settings were changed.
   */
  protected function updateLocalTask(TransactionTypeInterface $transaction_type, $has_tab) {
    // Local task option has reflection in module settings.
    $config = $this->configFactory->getEditable('transaction.settings');
    $tabs = $config->get('tabs');
    $option_id = $transaction_type->id() . '-' . $transaction_type->getTargetEntityTypeId();
    $has_config = array_search($option_id, $tabs);

    if (is_numeric($has_config) != $has_tab) {
      if ($has_tab) {
        $tabs[] = $option_id;
      }
      else {
        unset($tabs[$has_config]);
      }

      $config->set('tabs', $tabs);
      $config->save();

      return TRUE;
    }

    return FALSE;
  }

  /**
   * Clear caches involved in target entity local task.
   */
  protected function clearLocalTaskCache() {
    // When local task enabled, a link template is added to the target entity
    // type definitions.
    $this->entityTypeManager->clearCachedDefinitions();
    $this->fieldManager->clearCachedFieldDefinitions();
    // Invalidate block view to rebuild menu and local task blocks.
    $this->cacheTagsInvalidator->invalidateTags(['block_view']);
    // Action links has to be re-discovered.
    $this->cacheDiscovery->invalidateAll();
    // A route per transaction type and target entity will be added.
    $this->routeBuilder->rebuild();
  }

}
