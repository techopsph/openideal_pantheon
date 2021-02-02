<?php

namespace Drupal\administerusersbyrole\Form;

use Drupal\Component\Utility\Html;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\administerusersbyrole\Services\AccessManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Configure AlbanyWeb settings for this site.
 */
class SettingsForm extends ConfigFormBase {

  /**
   * The access manager.
   *
   * @var \Drupal\administerusersbyrole\Services\AccessManagerInterface
   */
  protected $accessManager;

  /**
   * Constructs a new AdministerusersbyrolePermissions instance.
   *
   * @param \Drupal\administerusersbyrole\Services\AccessManagerInterface $access_manager
   *   The entity manager.
   */
  public function __construct(AccessManagerInterface $access_manager) {
    $this->accessManager = $access_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static($container->get('administerusersbyrole.access'));
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'administerusersbyrole_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'administerusersbyrole.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('administerusersbyrole.settings');

    $form['roles'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Roles'),
    ];

    $options = [
      AccessManagerInterface::SAFE => $this->t('Allowed'),
      AccessManagerInterface::UNSAFE => $this->t('Forbidden'),
      AccessManagerInterface::PERM => $this->t('Custom'),
    ];

    foreach ($this->accessManager->managedRoles() as $rid => $role) {
      $form['roles'][$rid] = [
        '#type' => 'select',
        '#title' => Html::escape($role->label()),
        '#default_value' => $config->get("roles.$rid"),
        '#options' => $options,
        '#required' => TRUE,
      ];
    }

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('administerusersbyrole.settings');
    $values = $form_state->cleanValues()->getValues();
    foreach ($values as $rid => $value) {
      $config->set("roles.$rid", $value);
    }
    $config->save();

    parent::submitForm($form, $form_state);
  }

}
