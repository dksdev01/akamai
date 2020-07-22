<?php

namespace Drupal\Tests\akamai\Unit\Plugin\Purge\Purger;

use Drupal\Tests\UnitTestCase;
use Drupal\Tests\akamai\Kernel\EventSubscriber\MockSubscriber;
use Drupal\akamai\Event\AkamaiPurgeEvents;
use Drupal\akamai\Helper\CacheTagFormatter;
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

    $formatter = new CacheTagFormatter();

    $container = new ContainerBuilder();
    $container->set('akamai.helper.cachetagformatter', $formatter);
    \Drupal::setContainer($container);

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
    $invalidation_1 = $this->getMockBuilder('Drupal\purge\Plugin\Purge\Invalidation\TagInvalidation')
      ->disableOriginalConstructor()
      ->getMock();
    $invalidation_1->method('getExpression')
      ->willReturn('foo');
    // Create duplicate stub for response class.
    $invalidation_2 = $this->getMockBuilder('Drupal\purge\Plugin\Purge\Invalidation\TagInvalidation')
      ->disableOriginalConstructor()
      ->getMock();
    $invalidation_2->method('getExpression')
      ->willReturn('foo');
    // Create third stub for response class.
    $invalidation_3 = $this->getMockBuilder('Drupal\purge\Plugin\Purge\Invalidation\TagInvalidation')
      ->disableOriginalConstructor()
      ->getMock();
    $invalidation_3->method('getExpression')
      ->willReturn('bar');
    // Create string numeric stubs for response class.
    $invalidation_4 = $this->getMockBuilder('Drupal\purge\Plugin\Purge\Invalidation\TagInvalidation')
      ->disableOriginalConstructor()
      ->getMock();
    $invalidation_4->method('getExpression')
      ->willReturn('123');
    $invalidation_5 = $this->getMockBuilder('Drupal\purge\Plugin\Purge\Invalidation\TagInvalidation')
      ->disableOriginalConstructor()
      ->getMock();
    $invalidation_5->method('getExpression')
      ->willReturn('234');
    // Create integer stubs for response class.
    $invalidation_6 = $this->getMockBuilder('Drupal\purge\Plugin\Purge\Invalidation\TagInvalidation')
      ->disableOriginalConstructor()
      ->getMock();
    $invalidation_6->method('getExpression')
      ->willReturn(123);
    $invalidation_7 = $this->getMockBuilder('Drupal\purge\Plugin\Purge\Invalidation\TagInvalidation')
      ->disableOriginalConstructor()
      ->getMock();
    $invalidation_7->method('getExpression')
      ->willReturn(456);
    // Create float stub for response class.
    $invalidation_8 = $this->getMockBuilder('Drupal\purge\Plugin\Purge\Invalidation\TagInvalidation')
      ->disableOriginalConstructor()
      ->getMock();
    $invalidation_8->method('getExpression')
      ->willReturn(1.01);
    // Create boolean stub for response class.
    $invalidation_9 = $this->getMockBuilder('Drupal\purge\Plugin\Purge\Invalidation\TagInvalidation')
      ->disableOriginalConstructor()
      ->getMock();
    $invalidation_9->method('getExpression')
      ->willReturn(TRUE);

    $purger->invalidate([
      $invalidation_1,
      $invalidation_2,
      $invalidation_3,
      $invalidation_4,
      $invalidation_5,
      $invalidation_6,
      $invalidation_7,
      $invalidation_8,
      $invalidation_9,
    ]);

    $this->assertSame(
      ['foo', 'bar', '123', '234', '456', '1.01', '1', 'on_purge_creation'],
      $subscriber->event->data
    );
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

      $logger = $this->getMockBuilder('\Psr\Log\LoggerInterface')->getMock();

      $purger = new AkamaiTagPurger(['id' => 'my_id'], 'my_id', 'my_definition', $config_factory, $event_dispatcher, $akamai_client_factory, $logger);

      $this->assertEquals($purger->getTimeHint(), $returned_value);
    }

  }

  /**
   * Tests AkamaiTagPurger::invalidate() for warning on exceeding tag length.
   */
  public function testInvalidateTagLength() {
    $long_tag = str_repeat('a', 129);

    $logger = $this->getMockBuilder('\Drupal\Core\Logger\LoggerChannel')
      ->disableOriginalConstructor()
      ->setMethods(['warning'])
      ->getMock();
    $logger->expects($this->once())
      ->method('warning')
      ->with('Cache Tag %tag has exceeded the Akamai 128 character tag maximum length.', ['%tag' => $long_tag]);

    $purger = $this->getMockBuilder('Drupal\akamai\Plugin\Purge\Purger\AkamaiTagPurger')
      ->disableOriginalConstructor()
      ->setMethods(NULL)
      ->getMock();
    $reflection = new \ReflectionClass($purger);
    $reflection_property = $reflection->getProperty('logger');
    $reflection_property->setAccessible(TRUE);
    $reflection_property->setValue($purger, $logger);

    $formatter = new CacheTagFormatter();

    $container = new ContainerBuilder();
    $container->set('akamai.helper.cachetagformatter', $formatter);
    \Drupal::setContainer($container);

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
    $invalidation->method('setState')
      ->willReturn('foo');
    $invalidation->method('getExpression')
      ->willReturn($long_tag);

    $purger->invalidate([
      $invalidation,
    ]);
  }

}
