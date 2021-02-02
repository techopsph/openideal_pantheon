<?php

namespace Drupal\layout_builder\Form;

use Drupal\Core\Ajax\AjaxFormHelperTrait;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\OpenOffCanvasDialogCommand;
use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\layout_builder\Controller\LayoutRebuildTrait;
use Drupal\layout_builder\LayoutBuilderHighlightTrait;
use Drupal\layout_builder\LayoutTempstoreRepositoryInterface;
use Drupal\layout_builder\SectionComponentTrait;
use Drupal\layout_builder\SectionStorageInterface;

use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a form to delete a visibility condition.
 *
 * @internal
 *   Form classes are internal.
 */
class DeleteVisibilityForm extends ConfirmFormBase {

  use AjaxFormHelperTrait;
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
   * The plugin id of the condition to be removed.
   *
   * @var string
   */
  protected $pluginId;

  /**
   * Constructs a DeleteVisibilityForm object.
   *
   * @param \Drupal\layout_builder\LayoutTempstoreRepositoryInterface $layout_tempstore_repository
   *   The layout tempstore repository.
   */
  public function __construct(LayoutTempstoreRepositoryInterface $layout_tempstore_repository) {
    $this->layoutTempstoreRepository = $layout_tempstore_repository;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('layout_builder.tempstore_repository')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, SectionStorageInterface $section_storage = NULL, $delta = NULL, $uuid = NULL, $plugin_id = NULL) {
    $this->sectionStorage = $section_storage;
    $this->delta = $delta;
    $this->uuid = $uuid;
    $this->pluginId = $plugin_id;
    $form = parent::buildForm($form, $form_state);
    $form['actions']['cancel'] = $this->buildCancelLink();
    $form['#attributes']['data-layout-builder-target-highlight-id'] = $this->blockUpdateHighlightId($this->uuid);
    if ($this->isAjax()) {
      $form['actions']['submit']['#ajax']['callback'] = '::ajaxSubmit';
    }
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Are you sure you want to delete this visibility condition?');
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    $parameters = $this->getParameters();
    return new Url('layout_builder.visibility', $parameters);
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'layout_builder_delete_visibility';
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $component = $this->getCurrentComponent();
    $visibility_conditions = $component->get('visibility');
    unset($visibility_conditions[$this->pluginId]);
    $component->set('visibility', $visibility_conditions);
    $this->layoutTempstoreRepository->set($this->sectionStorage);
    $form_state->setRedirectUrl($this->sectionStorage->getLayoutBuilderUrl());
  }

  /**
   * Build a cancel button for the confirm form.
   */
  protected function buildCancelLink() {
    return [
      '#type' => 'button',
      '#value' => $this->getCancelText(),
      '#ajax' => [
        'callback' => '::ajaxCancel',
      ],
    ];
  }

  /**
   * Provides an ajax callback for the cancel button.
   */
  public function ajaxCancel(array &$form, FormStateInterface $form_state) {
    $parameters = $this->getParameters();
    $new_form = \Drupal::formBuilder()->getForm(BlockVisibilityForm::class, $this->sectionStorage, $parameters['delta'], $parameters['uuid']);
    $new_form['#action'] = $this->getCancelUrl()->toString();
    $response = new AjaxResponse();
    $response->addCommand(new OpenOffCanvasDialogCommand($this->t('Delete condition'), $new_form));
    return $response;
  }

  /**
   * Gets the parameters needed for the various Url() and form invocations.
   *
   * @return array
   *   List of Url parameters.
   */
  protected function getParameters() {
    return [
      'section_storage_type' => $this->sectionStorage->getStorageType(),
      'section_storage' => $this->sectionStorage->getStorageId(),
      'delta' => $this->delta,
      'uuid' => $this->uuid,
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

    return $this->t('Delete visibility rule for the @block_label block', ['@block_label' => $block_label]);
  }

  protected function successfulAjaxSubmit(array $form, FormStateInterface $form_state) {
    return $this->rebuildAndClose($this->sectionStorage);
  }

}
