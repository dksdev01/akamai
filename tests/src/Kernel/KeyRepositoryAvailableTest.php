<?php

namespace Drupal\Tests\akamai\Kernel;

use Drupal\KernelTests\KernelTestBase;

/**
 * Tests key provider functionality with key module is installed.
 *
 * @group Akamai
 */
class KeyRepositoryAvailableTest extends KernelTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = ['akamai', 'key'];

  /**
   * {@inheritdoc}
   */
  protected function setUp() : void {
    parent::setUp();
    $this->installConfig(['akamai', 'key']);
    $this->generateKeys();
  }

  /**
   * Helper function to dummy key entities.
   */
  protected function generateKeys() {
    $this->container->get('entity_type.manager')->getStorage('key')->create([
      'id' => 'my_key',
      'label' => 'My Key',
      'key_provider_settings' => [
        'key_value' => 'Super secret value',
      ],
    ])->save();
    $this->container->get('entity_type.manager')->getStorage('key')->create([
      'id' => 'second_key',
      'label' => 'Second Key',
      'key_provider_settings' => [
        'key_value' => 'Yet another key',
      ],
    ])->save();
  }

  /**
   * Tests that KeyProvider::hasKeyRepository() returns TRUE.
   */
  public function testHasKeyRepositoryIsTrue() {
    $this->assertTrue($this->container->get('akamai.key_provider')->hasKeyRepository());
  }

  /**
   * Tests that the key provider retrieves keys from key module.
   */
  public function testKeyProviderCanGetKeys() {
    $keys = $this->container->get('akamai.key_provider')->getKeys();
    $this->assertEquals('My Key', $keys['my_key']->label());
    $this->assertEquals('Super secret value', $keys['my_key']->getKeyValue());
    $this->assertEquals('Second Key', $keys['second_key']->label());
    $this->assertEquals('Yet another key', $keys['second_key']->getKeyValue());
  }

  /**
   * Tests that KeyProvider::getKey() retrieves specific key.
   */
  public function testCanGetSpecificKey() {
    $this->assertEquals('Yet another key', $this->container->get('akamai.key_provider')->getKey('second_key'));
  }

  /**
   * Tests that KeyProvider::getKey() retrieves specific key.
   */
  public function testInvalidKeyIsNull() {
    $this->assertNull($this->container->get('akamai.key_provider')->getKey('some_invalid_key'));
  }

}
