<?php

namespace Drupal\Tests\akamai\Unit\Plugin\Purge\Purger;

use Drupal\Tests\UnitTestCase;
use Drupal\Tests\akamai\Kernel\EventSubscriber\MockSubscriber;
use Drupal\akamai\Event\AkamaiPurgeEvents;
use Drupal\akamai\Plugin\Purge\Purger\AkamaiTagPurger;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\EventDispatcher\EventDispatcher;

/**
 * @coversDefaultClass \Drupal\akamai\Plugin\Purge\Purger\AkamaiTagPurger
 *
 * @group Akamai
 */
class AkamaiTagPurgerTest extends UnitTestCase {

  /**
   * Tests purge creation event dispatch.
   */
  public function testPurgeCreationEvent() {

    $purger = $this->getMockBuilder('Drupal\akamai\Plugin\Purge\Purger\AkamaiTagPurger')
      ->disableOriginalConstructor()
      ->setMethods(NULL)
      ->getMock();

    $container = new ContainerBuilder();

    $formatter = $this->getMockBuilder('Drupal\akamai\Helper\CacheTagFormatter')->getMock();
    $formatter->method('format')
      ->willReturn('foo');

    $container->set('akamai.helper.cachetagformatter', $formatter);
    \Drupal::setContainer($container);

    $formatter = $this->getMockBuilder('Drupal\akamai\Helper\CacheTagFormatter')->getMock();

    $client = $this->getMockBuilder('Drupal\akamai\Plugin\Client\AkamaiClientV3')
      ->disableOriginalConstructor()
      ->setMethods(['setType', 'purgeTags'])
      ->getMock();

    $reflection = new \ReflectionClass($purger);
    $reflection_property = $reflection->getProperty('client');
    $reflection_property->setAccessible(TRUE);
    $reflection_property->setValue($purger, $client);

    // Setup the mock event subscriber.
    $subscriber = new MockSubscriber();
    $event_dispatcher = new EventDispatcher();
    $event_dispatcher->addListener(AkamaiPurgeEvents::PURGE_CREATION, [$subscriber, 'onPurgeCreation']);

    $reflection_property = $reflection->getProperty('eventDispatcher');
    $reflection_property->setAccessible(TRUE);
    $reflection_property->setValue($purger, $event_dispatcher);

    // Create stub for response class.
    $invalidation = $this->getMockBuilder('Drupal\purge\Plugin\Purge\Invalidation\TagInvalidation')
      ->disableOriginalConstructor()
      ->getMock();
    $invalidation->method('getExpression')
      ->willReturn('foo');

    $purger->invalidate([$invalidation]);

    $this->assertEquals(['foo', 'on_purge_creation'], $subscriber->event->data);
  }

  /**
   * Tests AkamaiTagPurger::getTimeHint().
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

      $purger = new AkamaiTagPurger(['id' => 'my_id'], 'my_id', 'my_definition', $config_factory, $event_dispatcher, $akamai_client_factory);

      $this->assertEquals($purger->getTimeHint(), $returned_value);
    }

  }

}
