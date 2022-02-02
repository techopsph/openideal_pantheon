<?php

namespace Drupal\Tests\rrssb\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Test RRSSB.
 *
 * @group title
 */
class RrssbTest extends BrowserTestBase {

  /**
   * Modules to install.
   *
   * @var array
   */
  protected static $modules = ['node', 'field_ui', 'rrssb'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->drupalCreateContentType(['type' => 'article', 'name' => 'Article']);
  }

  /**
   * Test the entity extra field.
   */
  public function testEntityExtraField() {
    // Create user and node.
    $user = $this->drupalCreateUser([
      'access administration pages',
      'administer content types',
      'administer nodes',
      'administer node display',
      'view the administration theme',
      'administer rrssb',
    ]);
    $this->drupalLogin($user);
    $node = $this->drupalCreateNode(['type' => 'article']);
    $assert = $this->assertSession();

    // Enable RRSSB.
    $edit = ['button_set' => 'default'];
    $this->drupalGet('admin/structure/types/manage/article');
    $this->submitForm($edit, 'Save content type');

    // Check access to the important pages.
    // @todo Add testing for these.
    $this->drupalGet('admin/structure/types/manage/article/display');
    $this->drupalGet('admin/config/content/rrssb');
    $this->drupalGet('admin/config/content/rrssb/default');

    // Check button display.
    $this->drupalGet($node->toUrl());
    $share_url = "https://www.facebook.com/sharer/sharer.php?u=" . urlencode($node->toUrl('canonical', ['absolute' => TRUE])->toString());
    $assert->elementTextContains('css', 'ul.rrssb-buttons li.rrssb-facebook a[href="' . $share_url . '"]', 'Facebook');
  }

}
