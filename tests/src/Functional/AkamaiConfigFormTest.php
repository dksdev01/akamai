<?php

namespace Drupal\Tests\akamai\Functional;

use Drupal\Tests\BrowserTestBase;
use Drupal\Core\Url;

/**
 * Test the Akamai Config Form.
 *
 * @group Akamai
 */
class AkamaiConfigFormTest extends BrowserTestBase {

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
      'purge akamai cache',
      'administer akamai',
      'purge akamai cache',
    ]);
    $this->drupalLogin($this->privilegedUser);
  }

  /**
   * Tests that Akamai Configuration Form.
   */
  public function testConfigForm() {
    $edit['basepath'] = 'http://www.example.com';
    $edit['timeout'] = 20;
    $edit['domain'] = 'staging';
    $edit['ccu_version'] = 'v3';
    $edit['v3[action]'] = 'invalidate';

    $this->drupalGet('admin/config/akamai/config');
    $this->submitForm($edit, t('Save configuration'));

    // Tests that we can't save non-integer timeouts.
    $edit['timeout'] = 'lol';
    $this->drupalGet(Url::fromRoute('akamai.settings')->getInternalPath());
    $this->submitForm($edit, t('Save configuration'));
    $this->assertSession()
      ->responseContains(t('Please enter only integer values in this field.'));
  }

}
