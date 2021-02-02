<?php

namespace Drupal\transaction\Plugin\Derivative;

use Drupal\Component\Plugin\Derivative\DeriverBase;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\Discovery\ContainerDeriverInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslationInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides local action definitions for transaction list in target entities.
 *
 * @see \Drupal\transaction\Plugin\Transaction\GenericTransactor
 */
class TransactionLocalAction extends DeriverBase implements ContainerDeriverInterface {

  use StringTranslationTrait;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Creates an TransactionLocalTask object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   * @param \Drupal\Core\StringTranslation\TranslationInterface $string_translation
   *   The translation manager.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, ConfigFactoryInterface $config_factory, TranslationInterface $string_translation) {
    $this->entityTypeManager = $entity_type_manager;
    $this->configFactory = $config_factory;
    $this->stringTranslation = $string_translation;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, $base_plugin_id) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('config.factory'),
      $container->get('string_translation')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getDerivativeDefinitions($base_plugin_definition) {
    $this->derivatives = [];

    $tabs = $this->configFactory->get('transaction.settings')->get('tabs') ?: [];
    foreach ($tabs as $tab) {
      list($transaction_type_id, $target_entity_type_id) = explode('-', $tab);
      $this->derivatives["transaction.$target_entity_type_id.$transaction_type_id.add_transaction_action"] = [
        'route_name' => "entity.transaction.$target_entity_type_id.$transaction_type_id.add_form",
        'title' => $this->t('Add @type transaction', ['@type' => $this->entityTypeManager->getStorage('transaction_type')->load($transaction_type_id)->label()]),
        'appears_on' => [
          "entity.$target_entity_type_id.$transaction_type_id-transaction",
        ],
        'route_parameters' => [
          'target_entity_type' => $target_entity_type_id,
          'transaction_type' => $transaction_type_id,
        ],
      ];
    }

    foreach ($this->derivatives as &$entry) {
      $entry += $base_plugin_definition;
    }

    return $this->derivatives;
  }

}
