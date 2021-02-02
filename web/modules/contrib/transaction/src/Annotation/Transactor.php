<?php

namespace Drupal\transaction\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines a Transactor annotation object.
 *
 * @see \Drupal\transaction\TransactorPluginManager
 * @see plugin_api
 *
 * @Annotation
 */
class Transactor extends Plugin {

  /**
   * The plugin ID.
   *
   * @var string
   */
  public $id;

  /**
   * The title of the plugin.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public $title;

  /**
   * The description of the plugin.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public $description;

  /**
   * A list of entity types this transactor supports.
   *
   * @var string[]|optional
   */
  public $supported_entity_types = [];

  /**
   * Fields in transaction entity.
   *
   * @var array|optional
   */
  public $transaction_fields = [];

  /**
   * Fields in the target entity.
   *
   * @var array|optional
   */
  public $target_fields = [];

  /**
   * The default settings for the transactor.
   *
   * @var array|optional
   */
  public $settings = [];

}
