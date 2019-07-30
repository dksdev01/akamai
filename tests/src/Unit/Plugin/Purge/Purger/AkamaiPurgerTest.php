<?php

namespace Drupal\Tests\akamai\Unit\Plugin\Purge\Purger;

use Drupal\Tests\UnitTestCase;
use Drupal\akamai\Plugin\Purge\Purger\AkamaiPurger;

/**
 * @coversDefaultClass \Drupal\akamai\Plugin\Purge\Purger\AkamaiPurger
 *
 * @group Akamai
 */
class AkamaiPurgerTest extends UnitTestCase {

  /**
   * Tests AkamaiPurger::getTimeHint().
   */
  public function testGetTimeHintReturnsCorrectValues() {
    // Mock the akamai client factory.
    $akamai_client_factory = $this->getMockBuilder('Drupal\akamai\AkamaiClientFactory')
      ->disableOriginalConstructor()
      ->getMock();
    $akamai_client_factory->method('get')
      ->willReturn(NULL);

    // Mock the event dispatcher.
    $event_dispatcher = $this->getMockBuilder('Symfony\Component\EventDispatcher\EventDispatcherInterface')
      ->getMock();

    $result_map = [
      '-5' => 0,
      '0' => 0,
      '7' => 7,
      '9.5' => 9.5,
      '10' => 10,
      '10.5' => 10,
      '11' => 10,
    ];

    foreach ($result_map as $config_value => $returned_value) {
      // Mock the config.
      $config = $this->getMockBuilder('Drupal\Core\Config\ImmutableConfig')
        ->disableOriginalConstructor()
        ->getMock();
      $config->method('get')
        ->willReturn($config_value);

      // Mock the config factory.
      $config_factory = $this->getMockBuilder('Drupal\Core\Config\ConfigFactoryInterface')
        ->getMock();
      $config_factory->method('get')
        ->willReturn($config);

      $purger = new AkamaiPurger(['id' => 'my_id'], 'my_id', 'my_definition', $config_factory, $event_dispatcher, $akamai_client_factory);

      $this->assertEquals($purger->getTimeHint(), $returned_value);
    }

  }

}
