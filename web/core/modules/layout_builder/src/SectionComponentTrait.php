<?php

namespace Drupal\layout_builder;

/**
 * Methods for retrieving sections and components from layouts.
 */
trait SectionComponentTrait {

  /**
   * The section storage.
   *
   * @var \Drupal\layout_builder\SectionStorageInterface
   */
  protected $sectionStorage;

  /**
   * The layout section delta.
   *
   * @var int
   */
  protected $delta;

  /**
   * The uuid of the block component.
   *
   * @var string
   */
  protected $uuid;

  /**
   * Retrieves the current layout section being edited by the form.
   *
   * @return \Drupal\layout_builder\Section
   *   The current layout section.
   */
  public function getCurrentSection() {
    return $this->sectionStorage->getSection($this->delta);
  }

  /**
   * Retrieves the current component being edited by the form.
   *
   * @return \Drupal\layout_builder\SectionComponent
   *   The current section component.
   */
  public function getCurrentComponent() {
    return $this->getCurrentSection()->getComponent($this->uuid);
  }

}
