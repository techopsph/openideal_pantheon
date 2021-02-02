<?php

namespace Drupal\layout_builder\Form;

use Drupal\Core\Ajax\AjaxFormHelperTrait;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\OpenOffCanvasDialogCommand;
use Drupal\Core\Executable\ExecutableManagerInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormBuilderInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\layout_builder\Context\LayoutBuilderContextTrait;
use Drupal\layout_builder\Controller\LayoutRebuildTrait;
use Drupal\layout_builder\LayoutBuilderHighlightTrait;
use Drupal\layout_builder\LayoutTempstoreRepositoryInterface;
use Drupal\layout_builder\SectionComponentTrait;
use Drupal\layout_builder\SectionStorageInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Provides a form for applying visibility conditions to a block.
 *
 * @internal
 *   Form classes are internal.
 */
class BlockVisibilityForm extends FormBase {

  use AjaxFormHelperTrait;
  use LayoutBuilderContextTrait;
  use LayoutBuilderHighlightTrait;
  use LayoutRebuildTrait;
  use SectionComponentTrait;

  /**
   * The condition manager.
   *
   * @var \Drupal\Core\Condition\ConditionManager
   */
  protected $conditionManager;

  /**
   * The form builder.
   *
   * @var \Drupal\Core\Form\FormBuilderInterface
   */
  protected $formBuilder;

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
   * The layout tempstore repository.
   *
   * @var \Drupal\layout_builder\LayoutTempstoreRepositoryInterface
   */
  protected $layoutTempstoreRepository;

  /**
   * Constructs a BlockVisibilityForm object.
   *
   * @param \Drupal\Core\Executable\ExecutableManagerInterface $condition_manager
   *   The condition plugin manager.
   * @param \Drupal\Core\Form\FormBuilderInterface $form_builder
   *   The form builder.
   * @param \Drupal\layout_builder\LayoutTempstoreRepositoryInterface $layout_tempstore_repository
   *   The layout tempstore repository.
   */
  public function __construct(ExecutableManagerInterface $condition_manager, FormBuilderInterface $form_builder, LayoutTempstoreRepositoryInterface $layout_tempstore_repository) {
    $this->conditionManager = $condition_manager;
    $this->formBuilder = $form_builder;
    $this->layoutTempstoreRepository = $layout_tempstore_repository;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('plugin.manager.condition'),
      $container->get('form_builder'),
      $container->get('layout_builder.tempstore_repository')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'layout_builder_block_visibility';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, SectionStorageInterface $section_storage = NULL, $delta = NULL, $uuid = NULL) {
    $this->sectionStorage = $section_storage;
    $this->delta = $delta;
    $this->uuid = $uuid;

    // Any visibility conditions that have already been added to the block.
    $visibility_conditions_applied_to_block = $this->getCurrentComponent()->get('visibility') ?: [];

    // Visibility condition types that can be added to a block.
    $conditions_available_to_block = [];
    foreach ($this->conditionManager->getFilteredDefinitions('layout_builder', $this->getAvailableContexts($section_storage)) as $plugin_id => $definition) {
      $conditions_available_to_block[$plugin_id] = $definition['label'];
    }

    $items = [];
    foreach ($visibility_conditions_applied_to_block as $visibility_id => $configuration) {
      /** @var \Drupal\Core\Condition\ConditionInterface $condition */
      $condition = $this->conditionManager->createInstance($configuration['id'], $configuration);
      $options = [
        'attributes' => [
          'class' => ['use-ajax'],
          'data-dialog-type' => 'dialog',
          'data-dialog-renderer' => 'off_canvas',
          'data-outside-in-edit' => TRUE,
        ],
      ];
      $items[$visibility_id] = [
        'label' => [
          'data' => [
            'condition_name' => [
              '#type' => 'html_tag',
              '#tag' => 'b',
              '#value' => $condition->getPluginId(),
            ],
            'condition_summary' => [
              '#type' => 'container',
              '#markup' => $condition->summary(),
            ],
          ],
        ],
        'edit' => [
          'data' => [
            '#type' => 'link',
            '#title' => $this->t('Edit'),
            '#url' => Url::fromRoute('layout_builder.add_visibility', $this->getParameters($visibility_id), $options),
          ],
        ],
        'delete' => [
          'data' => [
            '#type' => 'link',
            '#title' => $this->t('Delete'),
            '#url' => Url::fromRoute('layout_builder.delete_visibility', $this->getParameters($visibility_id), $options),
          ],
        ],
      ];
    }

    if ($items) {
      $form['visibility'] = [
        '#prefix' => '<div class="configured-conditions">',
        '#suffix' => '</div>',
        '#theme' => 'table',
        '#rows' => $items,
        '#caption' => $this->t('Configured Conditions'),
        '#weight' => 10,
      ];
    }

    $form['condition'] = [
      '#type' => 'select',
      '#title' => $this->t('Add a visibility condition'),
      '#options' => $conditions_available_to_block,
      '#empty_value' => '',
      '#weight' => 20,
    ];

    // Determines if multiple conditions should be applied with 'and' or 'or'.
    $form['operator'] = [
      '#type' => 'radios',
      '#title' => $this->t('Operator'),
      '#options' => [
        'and' => $this->t('And'),
        'or' => $this->t('Or'),
      ],
      '#default_value' => $this->getCurrentComponent()->get('visibility_operator') ?: 'and',
      // This field is not necessary until multiple conditions are added.
      '#access' => count($items) > 0,
      // If there are two or more visibility conditions, this field appears
      // above the list of existing conditions. If there is only one visibility
      // condition, and a second one is being added, then this field appears
      // between the 'Add a visibility condition' dropdown and the submit
      // button.
      '#weight' => count($items) === 1 ? 30 : 3,
    ];

    // This is a submit button that only appears once two or more visibility
    // conditions are present. This submit button appears so the user can
    // update the visibility operator, a setting that impacts the entire block.
    // This is different than the default submit button/handler for this form,
    // which is used to add a visibility condition to the block.
    $form['update_operator'] = [
      '#type' => 'submit',
      '#access' => count($items) > 1,
      '#weight' => 5,
      '#value' => $this->t('Update operator'),
      '#submit' => ['::updateOperator'],
    ];

    if (count($items) === 1) {
      // If there is only one visibility condition, hide the operator field
      // until a second condition is selected to be added to the block.
      $form['operator']['#states'] = [
        'invisible' => [
          '[name="condition"]' => ['value' => ''],
        ],
      ];
    }

    $form['actions']['#weight'] = 40;
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Add condition'),
      // Submit button is only visible if a condition is selected.
      '#states' => [
        'invisible' => [
          '[name="condition"]' => ['value' => ''],
        ],
      ],
    ];

    $form['#attributes']['data-layout-builder-target-highlight-id'] = $this->blockUpdateHighlightId($this->uuid);

    if ($this->isAjax()) {
      $form['actions']['submit']['#ajax']['callback'] = '::ajaxSubmit';
      $form['actions']['submit']['#ajax']['event'] = 'click';
      $form['update_operator']['#ajax']['callback'] = '::ajaxSubmit';
      $form['update_operator']['#ajax']['event'] = 'click';
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  protected function successfulAjaxSubmit(array $form, FormStateInterface $form_state) {
    $triggering_element = $form_state->getTriggeringElement();
    // If the submit was triggered by the "update operator" button, just
    // rebuild the layout UI and close the dialog.
    if (isset($triggering_element['#submit'][0]) && ($triggering_element['#submit'][0] === '::updateOperator')) {
      return $this->rebuildAndClose($this->sectionStorage);
    }

    // Adding a visibility condition to a block is a two step process. This
    // submit handler is triggered after completion of step 1: choosing the
    // condition to add. The logic below opens a configuration form for step 2:
    // configuring the condition that was just added.
    $condition = $form_state->getValue('condition');
    $parameters = $this->getParameters($condition);

    // Build the configuration form to be used in step 2.
    $new_form = $this->formBuilder->getForm('\Drupal\layout_builder\Form\ConfigureVisibilityForm', $this->sectionStorage, $parameters['delta'], $parameters['uuid'], $parameters['plugin_id']);

    // @todo The changes to #action/actions need to be documented or refactored
    //   to better resemble other dual-dialog forms in Layout Builder.
    $new_form['#action'] = (new Url('layout_builder.add_visibility', $parameters))->toString();
    $url = new Url('layout_builder.add_visibility', $parameters, ['query' => [FormBuilderInterface::AJAX_FORM_REQUEST => TRUE, '_wrapper_format' => 'drupal_ajax']]);
    $new_form['actions']['submit']['#attached']['drupalSettings']['ajax'][$new_form['actions']['submit']['#id']]['url'] = $url->toString();
    $response = new AjaxResponse();
    $response->addCommand(new OpenOffCanvasDialogCommand($this->t('Configure condition'), $new_form));
    return $response;
  }

  /**
   * Submit handler for updating just the visibility operator.
   *
   * @param array $form
   *   The form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state object.
   */
  public function updateOperator(array $form, FormStateInterface $form_state) {
    $operator_value = $form_state->getValue('operator');
    $component = $this->getCurrentComponent();
    $component->set('visibility_operator', $operator_value);
    $this->layoutTempstoreRepository->set($this->sectionStorage);
    $form_state->setRedirectUrl($this->sectionStorage->getLayoutBuilderUrl());
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $parameters = $this->getParameters($form_state->getValue('condition'));
    $operator = $form_state->getValue('operator');
    $parameters['operator'] = $operator === 'or' ? $operator : 'and';
    $url = new Url('layout_builder.add_visibility', $parameters);
    $response = new RedirectResponse($url->toString());
    $form_state->setResponse($response);
  }

  /**
   * Gets the parameters needed for the various Url() and form invocations.
   *
   * @param string $visibility_id
   *   The id of the visibility plugin.
   *
   * @return array
   *   List of Url parameters.
   */
  protected function getParameters($visibility_id) {
    return [
      'section_storage_type' => $this->sectionStorage->getStorageType(),
      'section_storage' => $this->sectionStorage->getStorageId(),
      'delta' => $this->delta,
      'uuid' => $this->uuid,
      'plugin_id' => $visibility_id,
    ];
  }

  /**
   * Provides a title callback.
   *
   * @param \Drupal\layout_builder\SectionStorageInterface $section_storage
   *   The section storage.
   * @param int $delta
   *   The original delta of the section.
   * @param string $uuid
   *   The UUID of the block being updated.
   *
   * @return string
   *   The title for the block visibility form.
   */
  public function title(SectionStorageInterface $section_storage, $delta, $uuid) {
    $block_label = $section_storage
      ->getSection($delta)
      ->getComponent($uuid)
      ->getPlugin()
      ->label();

    return $this->t('Configure visibility rules for the @block_label block', ['@block_label' => $block_label]);
  }

}
