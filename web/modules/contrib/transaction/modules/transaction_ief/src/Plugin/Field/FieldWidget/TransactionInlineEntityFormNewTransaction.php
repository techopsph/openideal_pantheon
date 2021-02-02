<?php

namespace Drupal\transaction_ief\Plugin\Field\FieldWidget;

use Drupal\inline_entity_form\Plugin\Field\FieldWidget\InlineEntityFormSimple;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Field\FieldDefinitionInterface;

/**
 * Transaction creation inline widget.
 *
 * @FieldWidget(
 *   id = "transaction_ief_new_transaction",
 *   label = @Translation("Inline entity form - New transaction"),
 *   field_types = {
 *     "entity_reference"
 *   },
 *   multiple_values = false
 * )
 */
class TransactionInlineEntityFormNewTransaction extends InlineEntityFormSimple {

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $items->get($delta)->setValue(NULL);
    return parent::formElement($items, $delta, $element, $form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public static function isApplicable(FieldDefinitionInterface $field_definition) {
    return parent::isApplicable($field_definition)
      && $field_definition->getFieldStorageDefinition()->getSetting('target_type') == 'transaction';
  }

}
