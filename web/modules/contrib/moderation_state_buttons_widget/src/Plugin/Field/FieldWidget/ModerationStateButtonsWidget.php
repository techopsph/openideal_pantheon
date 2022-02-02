<?php

namespace Drupal\moderation_state_buttons_widget\Plugin\Field\FieldWidget;

use Drupal\Component\Utility\NestedArray;
use Drupal\content_moderation\Plugin\Field\FieldWidget\ModerationStateWidget;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Symfony\Component\HttpFoundation\Request;

/**
 * Plugin implementation of the 'moderation_state_buttons_widget' widget.
 *
 * @FieldWidget(
 *   id = "moderation_state_buttons",
 *   label = @Translation("Moderation state buttons"),
 *   field_types = {
 *     "string"
 *   }
 * )
 */
class ModerationStateButtonsWidget extends ModerationStateWidget {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'show_forbidden_transitions' => FALSE,
    // 'forward_limit' => -1,
    //      'backward_limit' => -1,.
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $elements = parent::settingsForm($form, $form_state);

    $elements['show_forbidden_transitions'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Show forbidden transitions'),
      '#default_value' => $this->getSetting('show_forbidden_transitions'),
      '#description' => $this->t('Show the target states that the current user cannot select as disabled buttons.'),
    ];
    // $elements['forward_limit'] = [
    //      '#type' => 'number',
    //      '#min' => -1,
    //      '#title' => $this->t('Show only X next states'),
    //      '#default_value' => $this->getSetting('forward_limit'),
    //      '#description' => $this->t('Limits the buttons only to X next states. Set to -1 to disable the limit.'),
    //    ];
    //    $elements['backward_limit'] = [
    //      '#type' => 'number',
    //      '#min' => -1,
    //      '#title' => $this->t('Show only X previous states'),
    //      '#default_value' => $this->getSetting('backward_limit'),
    //      '#description' => $this->t('Limits the buttons only to X previous states. Set to -1 to disable the limit.'),
    //    ];.
    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = parent::settingsSummary();

    $summary[] = $this->t('Show forbidden: @value', [
      '@value' => $this->getSetting('show_forbidden_transitions') ? $this->t('yes') : $this->t('no'),
    ]);

    // $summary[] = $this->t('Forward limit: @value', [
    //      '@value' => $this->getSetting('forward_limit') == -1 ? $this->t('none') : $this->getSetting('forward_limit'),
    //    ]);
    //
    //    $summary[] = $this->t('Backward limit: @value', [
    //      '@value' => $this->getSetting('backward_limit') == -1 ? $this->t('none') : $this->getSetting('backward_limit'),
    //    ]);.
    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    /** @var \Drupal\Core\Entity\ContentEntityInterface $entity */
    $entity = $items->getEntity();
    $showForbidden = $this->getSetting('show_forbidden_transitions');
    $initialState = $entity->moderation_state->value;
    $workflow = $this->moderationInformation->getWorkflowForEntity($entity);
    $currentState = $items->get($delta)->value ? $workflow->getTypePlugin()->getState($items->get($delta)->value) : $workflow->getTypePlugin()->getInitialState($entity);
    $wrapperId = "moderation_state_columns--{$items->getName()}--$delta";
    $element['#prefix'] = "<div id=\"$wrapperId\">";
    $element['#suffix'] = "</div>";

    /** @var \Drupal\workflows\Transition[] $transitions */
    $transitions = array_values($currentState->getTransitions());
    $transitionsMap = [];

    foreach ($transitions as $transition) {
      $targetState = $transition->to();
      $isValidTransition = $this->validator->isTransitionValid($workflow, $currentState, $targetState, $this->currentUser);
      if (!$isValidTransition && !$showForbidden) {
        continue;
      }

      $id = $targetState->id();
      $label = $targetState->label();
      $transitionsMap[$label] = $id;

      $element['buttons'][$id] = [
        '#type' => 'button',
        '#value' => $label,
        '#ajax' => [
          'callback' => [$this, 'moderationStateChanged'],
          'wrapper' => $wrapperId,
        ],
        '#limit_validation_errors' => [],
      ];

      if (!$isValidTransition) {
        $element['buttons'][$id]['#attributes']['disabled'] = TRUE;
        $element['buttons'][$id]['#value'] .= ' ' . $this->t('(no access)');
      }
    }

    $triggeringElement = $form_state->getTriggeringElement();
    // Sometimes the result of t() gets passed to array_key_exists below, which
    // causes errors with strict error reporting.
    $triggeringElementValue = $triggeringElement['#value'] ?? '';
    if ($triggeringElementValue instanceof TranslatableMarkup) {
      $triggeringElementValue = $triggeringElementValue->render();
    }
    if ($triggeringElementValue && array_key_exists($triggeringElementValue, $transitionsMap)) {
      $selectedStateId = $transitionsMap[$triggeringElementValue];
    }
    else {
      $selectedStateId = $currentState->id();
    }

    $element['buttons'][$selectedStateId]['#attributes']['disabled'] = TRUE;
    $element['buttons'][$initialState]['#attributes']['class'] = ['moderation-state-button-widget--current-state'];

    $element += [
      'label' => [
        '#type' => 'label',
        '#title' => $items->getFieldDefinition()->getLabel(),
        '#weight' => -1,
      ],
      $this->column => [
        '#type' => 'hidden',
        '#value' => $selectedStateId,
      ],
    ];

    $element['#attached']['library'][] = 'moderation_state_buttons_widget/widget';

    return $element;
  }

  /**
   * Returns the part of the form that needs to be replaced with AJAX.
   *
   * @param array $form
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   * @param \Symfony\Component\HttpFoundation\Request $request
   *
   * @return array
   */
  public function moderationStateChanged(array &$form, FormStateInterface $form_state, Request $request) {
    $buttonParents = $form_state->getTriggeringElement()['#array_parents'];
    return NestedArray::getValue($form, array_slice($buttonParents, 0, -2));
  }

  /**
   * {@inheritdoc}
   */
  public static function validateElement(array $element, FormStateInterface $form_state) {
    // We don't want to inherit this behavior from the parent plugin.
  }

}
