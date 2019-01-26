<?php

namespace Drupal\Tests\akamai\Unit;

use Drupal\akamai\AkamaiAuthentication;
use Drupal\Tests\UnitTestCase;

/**
 * @coversDefaultClass \Drupal\akamai\AkamaiAuthentication
 *
 * @group Akamai
 */
class AkamaiAuthenticationTest extends UnitTestCase {

  /**
   * Tests that we can authorise when specifying authentication keys.
   *
   * @covers ::create
   * @covers ::getAuth
   */
  public function testSetupClient() {
    $config = $this->getLiveConfig();
    $auth = AkamaiAuthentication::create($this->getConfigFactoryStub(['akamai.settings' => $config]));
    $expected = $config;
    unset($expected['rest_api_url'], $expected['storage_method']);
    $this->assertEquals($expected, $auth->getAuth());
    $this->assertEquals(get_class($auth), 'Drupal\akamai\AkamaiAuthentication');
  }

  /**
   * Tests that we can authorise when specifying edgerc file.
   *
   * @covers ::create
   * @covers ::getAuth
   */
  public function testSetupEdgeRc() {
    $config = $this->getEdgeRcConfig();
    $auth = AkamaiAuthentication::create($this->getConfigFactoryStub(['akamai.settings' => $config]));
    $expected = [
      'client_token' => 'edgerc-test-client-token',
      'client_secret' => 'edgerc-test-client-secret',
      'access_token' => 'edgerc-test-access-token',
    ];
    $this->assertEquals($expected, $auth->getAuth());
    $this->assertEquals(get_class($auth), 'Drupal\akamai\AkamaiAuthentication');
  }

  /**
   * Returns config for live mode.
   *
   * @return array
   *   An array of config values.
   */
  protected function getLiveConfig() {
    return [
      'storage_method' => 'database',
      'rest_api_url' => 'example.com',
      'client_token' => 'test',
      'client_secret' => 'test',
      'access_token' => 'test',
    ];
  }

  /**
   * Returns config for edge rc authentication mode.
   *
   * @return array
   *   An array of config values.
   */
  protected function getEdgeRcConfig() {
    return [
      'storage_method' => 'file',
      'edgerc_path' => realpath(__DIR__ . '/fixtures/.edgerc'),
      'edgerc_section' => 'default',
    ];
  }

}
