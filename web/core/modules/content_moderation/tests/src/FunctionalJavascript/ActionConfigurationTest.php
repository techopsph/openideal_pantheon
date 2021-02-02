<?php

namespace Drupal\Tests\content_moderation\FunctionalJavascript;

use Drupal\FunctionalJavascriptTests\WebDriverTestBase;
use Drupal\system\Entity\Action;
use Drupal\Tests\content_moderation\Traits\ContentModerationTestTrait;
use Drupal\Tests\node\Traits\ContentTypeCreationTrait;
use Drupal\workflows\Entity\Workflow;

/**
 * Tests AJAX on actions configuration form.
 *
 * @group content_moderation
 */
class ActionConfigurationTest extends WebDriverTestBase {

  use ContentTypeCreationTrait;
  use ContentModerationTestTrait;

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'action',
    'node',
    'content_moderation',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->createContentType(['type' => 'page']);
    $this->createContentType(['type' => 'article']);
    $workflow = $this->createEditorialWorkflow();
    $workflow->getTypePlugin()->addEntityTypeAndBundle('node', 'page');
    $workflow->save();
    $workflow = Workflow::create([
      'id' => 'moderation',
      'label' => 'Moderation',
      'type' => 'content_moderation',
    ]);
    $workflow->getTypePlugin()
      ->addState('moderated', 'Moderated')
      ->addEntityTypeAndBundle('node', 'article');
    $workflow->save();
  }

  /**
   * Tests configuration of action through administration interface.
   */
  public function testActionConfiguration() {
    // Create a user with permission to view the actions administration pages.
    $user = $this->drupalCreateUser(['administer actions']);
    $this->drupalLogin($user);
    $assert_session = $this->assertSession();

    $edit = [
      'action' => 'moderation_state_change:node',
    ];
    $this->drupalPostForm('admin/config/system/actions', $edit, t('Create'));
    $workflow_select = $assert_session->selectExists('workflow');
    $this->assertNotNull($workflow_select->find('named', ['option', 'editorial']));
    $this->assertNotNull($workflow_select->find('named', ['option', 'moderation']));

    $state_select = $assert_session->selectExists('state');
    $this->assertNotNull($state_select->find('named', ['option', 'archived']));
    $this->assertNotNull($state_select->find('named', ['option', 'draft']));
    $this->assertNotNull($state_select->find('named', ['option', 'published']));
    // Trigger the AJAX.
    $workflow_select->selectOption('moderation');
    $assert_session->assertWaitOnAjaxRequest();
    $state_select = $assert_session->selectExists('state');
    $this->assertNotNull($state_select->find('named', ['option', 'draft']));
    $this->assertNotNull($state_select->find('named', ['option', 'moderated']));
    $this->assertNotNull($state_select->find('named', ['option', 'published']));
    // Trigger the AJAX.
    $workflow_select->selectOption('editorial');
    $assert_session->assertWaitOnAjaxRequest();
    $state_select = $assert_session->selectExists('state');
    $this->assertNotNull($state_select->find('named', ['option', 'archived']));
    $this->assertNotNull($state_select->find('named', ['option', 'draft']));
    $this->assertNotNull($state_select->find('named', ['option', 'published']));
  }

  /**
   * Tests action instance creation through administration interface.
   */
  public function testActionCreation() {
    // Create a user with permission to view the actions administration pages.
    $user = $this->drupalCreateUser(['administer actions']);
    $this->drupalLogin($user);
    $assert_session = $this->assertSession();

    $edit = [
      'action' => 'moderation_state_change:node',
    ];
    $this->drupalPostForm('admin/config/system/actions', $edit, t('Create'));
    $page = $this->getSession()->getPage();
    $id = 'change_moderation_state_of_content';
    $page->fillField('label', 'Change moderation state of content to draft');
    $page->pressButton('Edit');
    $page->fillField('id', $id);
    // Trigger the AJAX.
    $page->selectFieldOption('workflow', 'editorial');
    $assert_session->assertWaitOnAjaxRequest();
    $page->selectFieldOption('state', 'draft');
    // Submit the form.
    $this->submitForm([], t('Save'));
    $assert_session->addressEquals('admin/config/system/actions');

    /** @var \Drupal\system\ActionConfigEntityInterface $action */
    $action = Action::load($id);
    $this->assertNotNull($action);

    /** @var \Drupal\content_moderation\Plugin\Action\ModerationStateChange $plugin */
    $plugin = $action->getPlugin();
    // Make sure the configuration are saved correctly.
    $configuration = $plugin->getConfiguration();
    $this->assertEquals('editorial', $configuration['workflow']);
    $this->assertEquals('draft', $configuration['state']);
  }

}
