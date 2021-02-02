<?php

namespace Drupal\Tests\ckeditor_mentions\FunctionalJavascript;

use Drupal\filter\Entity\FilterFormat;
use Drupal\FunctionalJavascriptTests\JavascriptTestBase;

/**
 * Tests the JavaScript functionality of webprofiler.
 *
 * @group webprofiler
 */
class MentionPlugin extends JavascriptTestBase {

  /**
   * Admin user.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $adminUser;

  /**
   * {@inheritdoc}
   */
  public static $modules = ['ckeditor_mentions', 'node'];

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();
    // Create text format.
    $filtered_html_format = FilterFormat::create([
      'format' => 'filtered_html',
      'name' => 'Filtered HTML',
      'weight' => 0,
      'editor[editor]' => 'ckeditor',
      'filters' => [],
    ]);
    $filtered_html_format->save();

    // Create a content type.
    $this->drupalCreateContentType(['type' => 'page', 'name' => 'Page']);

    // Create admin user.
    $this->adminUser = $this->drupalCreateUser([], 'test', TRUE);

    $user2 = $this->createUser([], 'user2');
    $this->drupalLogin($user2);
    $this->drupalLogout();

  }

  /**
   * Tests if the toolbar appears on front page.
   */
  public function testAdminUi() {
    $this->drupalLogin($this->adminUser);
    $this->drupalGet('admin/config/content/formats/manage/filtered_html');
    $this->getSession()->getPage()->selectFieldOption('editor[editor]', 'ckeditor');
    $this->assertSession()->waitForElement('css', '.editor-settings-wrapper');
    $this->getSession()->getpage()->clickLink('Mentions');
    $this->getSession()->getPage()->checkField('Enable Mentions');
    $this->submitForm([], 'Save configuration');

    $this->drupalGet('admin/config/content/formats/manage/filtered_html');
    $this->getSession()->getpage()->clickLink('Mentions');
    $this->assertSession()->checkboxChecked('Enable Mentions');

    // @todo Find a way to simulate the CKeditor's Key event to test that the
    // suggestion div is being displayed correctly.
  }

}
