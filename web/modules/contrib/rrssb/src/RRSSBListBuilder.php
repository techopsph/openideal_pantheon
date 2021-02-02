<?php

namespace Drupal\rrssb;

use Drupal\Core\Config\Entity\ConfigEntityListBuilder;
use Drupal\Core\Entity\EntityInterface;

/**
 * Defines a class to build a listing of image style entities.
 *
 * @see \Drupal\image\Entity\ImageStyle
 */
class RRSSBListBuilder extends ConfigEntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['label'] = $this->t('Button set');
    $header['follow'] = $this->t('Button type');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    $row['label'] = $entity->label();
    $row['follow'] = $entity->get("follow") ? $this->t('Follow') : $this->t('Share');
    return $row + parent::buildRow($entity);
  }

}
