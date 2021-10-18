<?php

namespace Drupal\Tests\akamai\Kernel;

use Drupal\KernelTests\KernelTestBase;

/**
 * Tests key provider functionality with key module is not installed.
 *
 * @group Akamai
 */
class KeyRepositoryUnavailableTest extends KernelTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = ['akamai'];

  /**
   * {@inheritdoc}
   */
  protected function setUp() : void {
    parent::setUp();
    $this->installConfig(['akamai']);
  }

  /**
   * Tests that KeyProvider::hasKeyRepository() returns FALSE.
   */
  public function testHasKeyRepositoryIsFalse() {
    $this->assertFalse($this->container->get('akamai.key_provider')->hasKeyRepository());
  }

  /**
   * Tests key provider can't retrieve keys if key module is missing.
   */
  public function testKeyProviderGetKeysThrowsException() {
    $this->expectException(\Exception::class);
    $keys = $this->container->get('akamai.key_provider')->getKeys();
  }

  /**
   * Tests key provider can't retrieve specific keys if key module is missing.
   */
  public function testKeyProviderGetKeyThrowsException() {
    $this->expectException(\Exception::class);
    $keys = $this->container->get('akamai.key_provider')->getKey('some key');
  }

}
