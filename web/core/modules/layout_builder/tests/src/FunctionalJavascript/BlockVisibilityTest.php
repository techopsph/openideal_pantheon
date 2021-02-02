<?php

namespace Drupal\Tests\layout_builder\FunctionalJavascript;

use Drupal\FunctionalJavascriptTests\WebDriverTestBase;
use Drupal\Tests\contextual\FunctionalJavascript\ContextualLinkClickTrait;

/**
 * Test layout block visibility functionality.
 *
 * @group layout_builder
 */
class BlockVisibilityTest extends WebDriverTestBase {

  use ContextualLinkClickTrait;

  protected $defaultTheme = 'classy';

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'layout_builder',
    'node',
    'contextual',
  ];

  /**
   * Path prefix for the field UI for the test bundle.
   *
   * @var string
   */
  const FIELD_UI_PREFIX = 'admin/structure/types/manage/bundle_with_section_field';

  /**
   * CSS selector for the body field block.
   *
   * @var string
   */
  const BODY_FIELDBLOCK_SELECTOR = '.block-field-blocknodebundle-with-section-fieldbody';

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->createContentType(['type' => 'bundle_with_section_field']);

    $this->drupalLogin($this->drupalCreateUser([
      'configure any layout',
      'create and edit custom blocks',
      'administer node display',
      'administer node fields',
      'access contextual links',
    ]));

    // Enable layout builder.
    $this->drupalPostForm(
      static::FIELD_UI_PREFIX . '/display/default',
      ['layout[enabled]' => TRUE],
      'Save'
    );

    $this->createNode([
      'type' => 'bundle_with_section_field',
      'body' => [
        [
          'value' => 'The node body',
        ],
      ],
    ])->save();

    $this->createNode([
      'type' => 'bundle_with_section_field',
      'body' => [
        [
          'value' => 'The node body',
        ],
      ],
    ])->save();
  }

  /**
   * Tests conditional visibility.
   */
  public function testConditionalVisibility() {
    $assert_session = $this->assertSession();
    $page = $this->getSession()->getPage();

    // Remove all of the sections from the page.
    $this->drupalGet(static::FIELD_UI_PREFIX . '/display/default/layout');

    $page->clickLink('Remove Section 1');
    $assert_session->assertWaitOnAjaxRequest();
    $page->pressButton('Remove');
    $assert_session->assertWaitOnAjaxRequest();
    // Assert that there are no sections on the page.
    $assert_session->pageTextNotContains('Remove Section 1');
    $assert_session->pageTextNotContains('Add block');

    $page->clickLink('Add section');
    $assert_session->assertWaitOnAjaxRequest();
    $this->assertNotEmpty($assert_session->waitForElementVisible('css', '#drupal-off-canvas'));

    $page->clickLink('Three column');
    $assert_session->assertWaitOnAjaxRequest();
    $this->assertNotEmpty($assert_session->waitForElementVisible('css', '.layout-builder-configure-section'));
    $page->pressButton('Add section');

    $blocks_in_layout = [
      [
        'label' => 'ID',
        'region_selector' => '.layout__region--first',
        'rendered_block_selector' => '.block-field-blocknodebundle-with-section-fieldnid',
      ],
      [
        'label' => 'Powered by Drupal',
        'region_selector' => '.layout__region--second',
        'rendered_block_selector' => '.block-system-powered-by-block',
      ],
      [
        'label' => 'Body',
        'region_selector' => '.layout__region--third',
        'rendered_block_selector' => '.block-field-blocknodebundle-with-section-fieldbody',
      ],
    ];

    foreach ($blocks_in_layout as $block) {
      $rendered_block_selector = $block['rendered_block_selector'];
      $this->addBlock($block['label'], $block['region_selector'], "#layout-builder $rendered_block_selector");
    }

    $page->pressButton('Save layout');

    foreach (['node/1', 'node/2'] as $path) {
      $this->drupalGet($path);
      foreach ($blocks_in_layout as $block) {
        $this->assertSession()->elementExists('css', $block['rendered_block_selector']);
      }
      $assert_session->pageTextContains('The node body');
    }

    $this->drupalGet(static::FIELD_UI_PREFIX . '/display/default/layout');

    // Confirm "Control visibility" contextual links available on each block.
    foreach ($blocks_in_layout as $block) {
      $rendered_block_selector = $block['rendered_block_selector'];
      $assert_session->elementExists('css', "#layout-builder $rendered_block_selector .layout-builder-block-visibility a");
    }

    // Test Request Path visibility rule.
    $this->beginAddCondition('request_path');
    $page->checkField('settings[negate]');
    $page->findField('settings[pages]')->setValue('/node/2');
    $page->pressButton('Add condition');
    $assert_session->assertWaitOnAjaxRequest();
    $page->pressButton('Save layout');
    $this->drupalGet('node/1');
    $assert_session->pageTextContains('The node body');
    $this->drupalGet('node/2');
    $assert_session->pageTextNotContains('The node body');

    // Confirm that editing an existing condition works.
    $this->drupalGet(static::FIELD_UI_PREFIX . '/display/default/layout');
    $this->clickContextualLink(static::BODY_FIELDBLOCK_SELECTOR, 'Control visibility');
    $assert_session->assertWaitOnAjaxRequest();
    $this->assertNotEmpty($assert_session->waitForElementVisible('css', '#drupal-off-canvas'));
    $page->clickLink('Edit');
    $assert_session->assertWaitOnAjaxRequest();
    $this->assertNotEmpty($assert_session->waitForElementVisible('css', '[value="Update"]'));
    $page->findField('settings[pages]')->setValue('/node/1');
    $page->pressButton('Update');
    $assert_session->assertWaitOnAjaxRequest();
    $page->pressButton('Save layout');
    $this->drupalGet('node/1');
    $assert_session->pageTextNotContains('The node body');
    $this->drupalGet('node/2');
    $assert_session->pageTextContains('The node body');

    // Confirm 'or' operator works ('and' is the default operator)
    $this->beginAddCondition('request_path', 'or');
    $page->checkField('settings[negate]');
    $page->findField('settings[pages]')->setValue('/node/2');
    $page->pressButton('Add condition');
    $assert_session->assertWaitOnAjaxRequest();
    $page->pressButton('Save layout');

    // Test Current Theme visibility rule.
    $this->removeVisibilityConditions();
    $this->beginAddCondition('current_theme');
    $page->checkField('settings[negate]');
    $page->findField('settings[theme]')->setValue('classy');
    $page->pressButton('Add condition');
    $assert_session->assertWaitOnAjaxRequest();
    $page->pressButton('Save layout');
    $this->drupalGet('node/1');
    $assert_session->pageTextNotContains('The node body');
    $this->drupalGet('node/2');
    $assert_session->pageTextNotContains('The node body');

    // Test User Role visibility rule.
    $this->removeVisibilityConditions();
    $this->beginAddCondition('user_role');
    $page->checkField('settings[negate]');
    $page->checkField('settings[roles][anonymous]');
    $page->pressButton('Add condition');
    $assert_session->assertWaitOnAjaxRequest();
    $page->pressButton('Save layout');
    $this->drupalGet('node/1');
    $assert_session->pageTextContains('The node body');
    $this->drupalGet('node/2');
    $assert_session->pageTextContains('The node body');

    $this->drupalLogout();

    $this->drupalGet('node/1');
    $assert_session->pageTextNotContains('The node body');
    $this->drupalGet('node/2');
    $assert_session->pageTextNotContains('The node body');
  }

  /**
   * Begins adding a visibility condition to the body field block.
   *
   * @param string $condition
   *   The visibility condition to add.
   * @param string $operator
   *   The and/or operator when multiple conditions present.
   */
  protected function beginAddCondition($condition, $operator = '') {
    $assert_session = $this->assertSession();
    $page = $this->getSession()->getPage();

    $this->drupalGet(static::FIELD_UI_PREFIX . '/display/default/layout');
    $this->clickContextualLink(static::BODY_FIELDBLOCK_SELECTOR, 'Control visibility');
    $assert_session->assertWaitOnAjaxRequest();
    $this->assertNotEmpty($assert_session->waitForElementVisible('css', '#drupal-off-canvas'));
    $page->findField('condition')->setValue($condition);
    $this->assertNotEmpty($assert_session->waitForElementVisible('css', '[value="Add condition"]'));
    if (!empty($operator)) {
      $page->findField('operator')->setValue($operator);
    }
    $page->pressButton('Add condition');
    $this->assertNotEmpty($assert_session->waitForElementVisible('css', '[name="settings[negate]"]'));
  }

  /**
   * Removes the visibility rules from the body field block.
   */
  protected function removeVisibilityConditions() {
    $assert_session = $this->assertSession();
    $page = $this->getSession()->getPage();

    $this->drupalGet(static::FIELD_UI_PREFIX . '/display/default/layout');
    $this->clickContextualLink(static::BODY_FIELDBLOCK_SELECTOR, 'Control visibility');
    $assert_session->assertWaitOnAjaxRequest();
    $this->assertNotEmpty($assert_session->waitForElementVisible('css', '#drupal-off-canvas'));
    $page->clickLink('Delete');
    $assert_session->assertWaitOnAjaxRequest();
    $this->assertNotEmpty($assert_session->waitForElementVisible('css', '[value="Confirm"]'));
    $page->pressButton('Confirm');
    $assert_session->assertWaitOnAjaxRequest();

    // If multiple conditions present, this method will need to run again.
    $this->clickContextualLink(static::BODY_FIELDBLOCK_SELECTOR, 'Control visibility');
    $assert_session->assertWaitOnAjaxRequest();
    $close_button = $assert_session->waitForElementVisible('css', '[title="Close"]');
    if ($page->hasLink('Delete')) {
      $close_button->click();
      $this->removeVisibilityConditions();
    }
    else {
      $page->pressButton('Save layout');
      $this->drupalGet('node/1');
      $assert_session->pageTextContains('The node body');
      $this->drupalGet('node/2');
      $assert_session->pageTextContains('The node body');
    }
  }

  /**
   * Adds a block in the Layout Builder.
   *
   * @param string $block_link_text
   *   The link text to add the block.
   * @param string $region_selector
   *   The link text to add the block.
   * @param string $rendered_locator
   *   The CSS locator to confirm the block was rendered.
   */
  protected function addBlock($block_link_text, $region_selector, $rendered_locator) {
    $assert_session = $this->assertSession();
    $page = $this->getSession()->getPage();

    // Add a new block.
    $add_block_link_selector = "#layout-builder $region_selector a:contains('Add block')";
    $this->assertNotEmpty($assert_session->waitForElementVisible('css', $add_block_link_selector));

    $add_block_link = $page->find('css', $add_block_link_selector);
    $add_block_link->click();
    $this->assertNotEmpty($assert_session->waitForElementVisible('css', '#drupal-off-canvas'));
    $assert_session->assertWaitOnAjaxRequest();
    $page->clickLink($block_link_text);

    // Wait for off-canvas dialog to reopen with block form.
    $this->assertNotEmpty($assert_session->waitForElementVisible('css', '.layout-builder-add-block'));
    $assert_session->assertWaitOnAjaxRequest();
    $page->pressButton('Add block');

    // Wait for block form to be rendered in the Layout Builder.
    $this->assertNotEmpty($assert_session->waitForElement('css', $rendered_locator));
  }

}
