<?php

namespace Drupal\Tests\akamai\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Test the Akamai Homepage Clearing.
 *
 * @description Test Akamai cache clearings of the site homepage.
 *
 * @group Akamai
 */
class AkamaiCacheControlFormTest extends BrowserTestBase {

  /**
   * Node created.
   *
   * @var \Drupal\node\NodeInterface
   */
  protected $node;

  /**
   * User with admin rights.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $privilegedUser;

  /**
   * Default theme.
   *
   * @var string
   */
  protected $defaultTheme = 'stark';

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = ['system_test', 'node', 'user', 'akamai'];

  /**
   * {@inheritdoc}
   */
  protected function setUp() : void {
    parent::setUp();
    // Create and log in our privileged user.
    $this->privilegedUser = $this->drupalCreateUser([
      'administer akamai',
      'purge akamai cache',
    ]);
    $this->drupalLogin($this->privilegedUser);
    $this->drupalCreateContentType(['type' => 'article']);
    $this->node = $this->drupalCreateNode(['type' => 'article']);

    $edit['basepath'] = 'http://www.example.com';
    $this->drupalGet('admin/config/akamai/config');
    $this->submitForm($edit, t('Save configuration'));
  }

  /**
   * Tests manual purging via Akamai Cache Clear form.
   */
  public function testValidUrlPurging() {
    $edit['paths'] = '   ';
    $edit['domain_override'] = 'staging';
    $edit['action'] = 'invalidate';
    $edit['method'] = 'url';
    $this->drupalGet('admin/config/akamai/cache-clear');
    $this->submitForm($edit, t('Start Refreshing Content'));
    $this->assertSession()
      ->responseContains(t('Paths/URLs/CPCodes field is required.'));

    $edit['paths'] = 'https://www.google.com';
    $edit['domain_override'] = 'staging';
    $edit['action'] = 'invalidate';
    $edit['method'] = 'url';
    $this->drupalGet('admin/config/akamai/cache-clear');
    $this->submitForm($edit, t('Start Refreshing Content'));
    $this->assertSession()
      ->responseContains(t('The URL(s) [https://www.google.com] are not configured to be work with Akamai.'));
  }

}
