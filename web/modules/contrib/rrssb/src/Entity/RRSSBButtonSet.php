<?php

namespace Drupal\rrssb\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;

/**
 * Defines the RRSSB button set entity.
 *
 * @ConfigEntityType(
 *   id = "rrssb_button_set",
 *   label = @Translation("RRSSB Button Set"),
 *   handlers = {
 *     "list_builder" = "Drupal\rrssb\RRSSBListBuilder",
 *     "form" = {
 *       "add" = "Drupal\rrssb\Form\RRSSBSettingsForm",
 *       "edit" = "Drupal\rrssb\Form\RRSSBSettingsForm",
 *       "delete" = "Drupal\rrssb\Form\RRSSBDeleteForm",
 *     }
 *   },
 *   config_prefix = "button_set",
 *   admin_permission = "administer rrssb",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label"
 *   },
 *   links = {
 *     "edit-form" = "/admin/config/content/rrssb/{button_set}",
 *     "delete-form" = "/admin/config/content/rrssb/{button_set}/delete",
 *     "collection" = "/admin/config/content/rrssb"
 *   },
 *   config_export = {
 *     "id",
 *     "label",
 *     "follow",
 *     "chosen",
 *     "appearance",
 *     "prefix",
 *     "image_tokens",
 *   }
 * )
 */
class RRSSBButtonSet extends ConfigEntityBase {

  /**
   * The name of the button set.
   *
   * @var string
   */
  protected $id;

  /**
   * The button set label.
   *
   * @var string
   */
  protected $label;

}
