<?php

namespace Drupal\layout_builder\Form;

use Drupal\Component\Uuid\UuidInterface;
use Drupal\Core\Ajax\AjaxFormHelperTrait;
use Drupal\Core\Condition\ConditionInterface;
use Drupal\Core\Condition\ConditionManager;
use Drupal\Core\DependencyInjection\ClassResolverInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\SubformState;
use Drupal\Core\Plugin\ContextAwarePluginAssignmentTrait;
use Drupal\Core\Plugin\ContextAwarePluginInterface;
use Drupal\Core\Plugin\PluginFormFactoryInterface;
use Drupal\Core\Plugin\PluginWithFormsInterface;
use Drupal\Core\Url;
use Drupal\layout_builder\Context\LayoutBuilderContextTrait;
use Drupal\layout_builder\Controller\LayoutRebuildTrait;
use Drupal\layout_builder\LayoutBuilderHighlightTrait;
use Drupal\layout_builder\LayoutTempstoreRepositoryInterface;
use Drupal\layout_builder\SectionComponentTrait;
use Drupal\layout_builder\SectionStorageInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a form to configure a visibility condition.
 *
 * @internal
 *   Form classes are internal.
 */
class ConfigureVisibilityForm extends FormBase {

  use AjaxFormHelperTrait;
  use ContextAwarePluginAssignmentTrait;
  use LayoutBuilderContextTrait;
  use LayoutBuilderHighlightTrait;
  use LayoutRebuildTrait;
  use SectionComponentTrait;

  /**
   * The layout tempstore repository.
   *
   * @var \Drupal\layout_builder\LayoutTempstoreRepositoryInterface
   */
  protected $layoutTempstoreRepository;

  /**
   * The condition manager.
   *
   * @var \Drupal\Core\Condition\ConditionManager
   */
  protected $conditionManager;

  /**
   * The uuid generator.
   *
   * @var \Drupal\Component\Uuid\UuidInterface
   */
  protected $uuidGenerator;

  /**
   * The plugin form factory.
   *
   * @var \Drupal\Core\Plugin\PluginFormFactoryInterface
   */
  protected $pluginFormFactory;

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
   * The condition plugin being configured.
   *
   * @var \Drupal\Core\Condition\ConditionInterface
   */
  protected $condition;

  /**
   * The condition plugin being configured.
   *
   * @var \Drupal\Core\Condition\ConditionInterface
   */
  protected $configuration;

  /**
   * Constructs a ConfigureVisibilityForm object.
   *
   * @param \Drupal\layout_builder\LayoutTempstoreRepositoryInterface $layout_tempstore_repository
   *   The layout tempstore repository.
   * @param \Drupal\Core\Condition\ConditionManager $condition_manager
   *   The condition plugin manager.
   * @param \Drupal\Component\Uuid\UuidInterface $uuid_generator
   *   The uuid generator.
   * @param \Drupal\Core\Plugin\PluginFormFactoryInterface $plugin_form_manager
   *   The plugin form manager.
   * @param \Drupal\Core\DependencyInjection\ClassResolverInterface $class_resolver
   *   The class resolver.
   */
  public function __construct(LayoutTempstoreRepositoryInterface $layout_tempstore_repository, ConditionManager $condition_manager, UuidInterface $uuid_generator, PluginFormFactoryInterface $plugin_form_manager, ClassResolverInterface $class_resolver) {
    $this->layoutTempstoreRepository = $layout_tempstore_repository;
    $this->conditionManager = $condition_manager;
    $this->uuidGenerator = $uuid_generator;
    $this->pluginFormFactory = $plugin_form_manager;
    $this->classResolver = $class_resolver;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('layout_builder.tempstore_repository'),
      $container->get('plugin.manager.condition'),
      $container->get('uuid'),
      $container->get('plugin_form.factory'),
      $container->get('class_resolver')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'layout_builder_configure_visibility';
  }

  /**
   * Prepares the condition plugin based on the condition ID.
   *
   * @param string $condition_id
   *   A condition UUID, or the plugin ID used to create a new condition.
   * @param array $value
   *   The condition configuration.
   *
   * @return \Drupal\Core\Condition\ConditionInterface
   *   The condition plugin.
   */
  protected function prepareCondition($condition_id, array $value) {
    if ($value) {
      return $this->conditionManager->createInstance($value['id'], $value);
    }
    /** @var \Drupal\Core\Condition\ConditionInterface $condition */
    $condition = $this->conditionManager->createInstance($condition_id);
    $configuration = $condition->getConfiguration();
    $configuration['uuid'] = $this->uuidGenerator->generate();
    $condition->setConfiguration($configuration);
    return $condition;
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, SectionStorageInterface $section_storage = NULL, $delta = NULL, $uuid = NULL, $plugin_id = NULL) {
    $this->sectionStorage = $section_storage;
    $this->delta = $delta;
    $this->uuid = $uuid;

    $visibility_conditions = $this->getCurrentComponent()->get('visibility');
    $configuration = !empty($visibility_conditions[$plugin_id]) ? $visibility_conditions[$plugin_id] : [];
    $this->configuration = $configuration;
    $this->condition = $this->prepareCondition($plugin_id, $configuration);

    $form_state->setTemporaryValue('gathered_contexts', $this->getAvailableContexts($section_storage));

    $form['#tree'] = TRUE;
    $form['settings'] = [];
    $subform_state = SubformState::createForSubform($form['settings'], $form, $form_state);
    $form['settings'] = $this->getConditionPluginForm($this->condition)->buildConfigurationForm($form['settings'], $subform_state);
    $form['settings']['id'] = [
      '#type' => 'value',
      '#value' => $this->condition->getPluginId(),
    ];

    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $configuration ? $this->t('Update') : $this->t('Add condition'),
      '#button_type' => 'primary',
    ];

    // If one is not already present, add a hidden field with the value of
    // the operator field from BlockVisibilityForm - the form that precedes
    // this one when adding/updating a visibility condition.
    if (!$form_state->getValue('operator')) {
      // Get the input values from the form that preceded this one.
      $user_input = $form_state->getUserInput();
      $form['operator']['#type'] = 'hidden';
      if (isset($user_input['operator']) && $user_input['operator'] === 'or') {
        $form['operator']['#value'] = $user_input['operator'];
      }
      else {
        $form['operator']['#value'] = 'and';
      }
    }

    $form['back_button'] = [
      '#type' => 'link',
      '#url' => Url::fromRoute('layout_builder.visibility',
        [
          'section_storage_type' => $section_storage->getStorageType(),
          'section_storage' => $section_storage->getStorageId(),
          'delta' => $delta,
          'uuid' => $uuid,
        ]
      ),
      '#title' => $this->t('Back'),
    ];

    $form['#attributes']['data-layout-builder-target-highlight-id'] = $this->blockUpdateHighlightId($this->uuid);

    if ($this->isAjax()) {
      $form['actions']['submit']['#ajax']['callback'] = '::ajaxSubmit';
      $form['back_button']['#attributes'] = [
        'class' => ['use-ajax'],
        'data-dialog-type' => 'dialog',
        'data-dialog-renderer' => 'off_canvas',
      ];
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $subform_state = SubformState::createForSubform($form['settings'], $form, $form_state);
    $this->getConditionPluginForm($this->condition)->validateConfigurationForm($form['settings'], $subform_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Call the plugin submit handler.
    $subform_state = SubformState::createForSubform($form['settings'], $form, $form_state);
    $this->getConditionPluginForm($this->condition)->submitConfigurationForm($form, $subform_state);

    // If this block is context-aware, set the context mapping.
    if ($this->condition instanceof ContextAwarePluginInterface) {
      $this->condition->setContextMapping($subform_state->getValue('context_mapping', []));
    }

    $configuration = $this->condition->getConfiguration();

    $component = $this->getCurrentComponent();
    $visibility_conditions = $component->get('visibility');
    $visibility_conditions[$configuration['uuid']] = $configuration;
    $component->set('visibility', $visibility_conditions);
    $component->set('visibility_operator', $form_state->getValue('operator'));

    $this->layoutTempstoreRepository->set($this->sectionStorage);
    $form_state->setRedirectUrl($this->sectionStorage->getLayoutBuilderUrl());
  }

  /**
   * {@inheritdoc}
   */
  protected function successfulAjaxSubmit(array $form, FormStateInterface $form_state) {
    return $this->rebuildAndClose($this->sectionStorage);
  }

  /**
   * Retrieves the plugin form for a given condition.
   *
   * @param \Drupal\Core\Condition\ConditionInterface $condition
   *   The condition plugin.
   *
   * @return \Drupal\Core\Plugin\PluginFormInterface
   *   The plugin form for the condition.
   */
  protected function getConditionPluginForm(ConditionInterface $condition) {
    if ($condition instanceof PluginWithFormsInterface) {
      return $this->pluginFormFactory->createInstance($condition, 'configure');
    }
    return $condition;
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

    return $this->t('Configure visibility rule for the @block_label block', ['@block_label' => $block_label]);
  }

}
