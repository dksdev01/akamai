<?php

namespace Drupal\akamai\Plugin\Purge\Purger;

use Drupal\akamai\AkamaiClientFactory;
use Drupal\akamai\Event\AkamaiPurgeEvents;
use Drupal\purge\Plugin\Purge\Purger\PurgerBase;
use Drupal\Core\Config\ConfigFactoryInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Drupal\purge\Plugin\Purge\Invalidation\InvalidationInterface;

/**
 * Akamai Tag Purger.
 *
 * @PurgePurger(
 *   id = "akamai_tag",
 *   label = @Translation("Akamai Tag Purger"),
 *   description = @Translation("Provides a Purge service for Akamai Fast Purge Cache Tags."),
 *   types = {"tag"},
 *   configform = "Drupal\akamai\Form\ConfigForm",
 * )
 */
class AkamaiTagPurger extends PurgerBase {


  /**
   * Web services client for Akamai API.
   *
   * @var \Drupal\akamai\AkamaiClient
   */
  protected $client;

  /**
   * Akamai client config.
   *
   * @var \Drupal\Core\Config
   */
  protected $akamaiClientConfig;

  /**
   * The event dispatcher.
   *
   * @var \Symfony\Component\EventDispatcher\EventDispatcherInterface
   */
  protected $eventDispatcher;

  /**
   * A logger instance.
   *
   * @var \Drupal\Core\Logger\LoggerChannelInterface
   */
  protected $logger;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('config.factory'),
      $container->get('event_dispatcher'),
      $container->get('akamai.client.factory'),
      $container->get('logger.channel.akamai')
    );
  }

  /**
   * Constructs a \Drupal\Component\Plugin\AkamaiPurger.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config
   *   The factory for configuration objects.
   * @param \Symfony\Component\EventDispatcher\EventDispatcherInterface $event_dispatcher
   *   The event dispatcher.
   * @param \Drupal\akamai\AkamaiClientFactory $akamai_client_factory
   *   The akamai client factory.
   * @param \Psr\Log\LoggerInterface $logger
   *   Logger interface.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, ConfigFactoryInterface $config, EventDispatcherInterface $event_dispatcher, AkamaiClientFactory $akamai_client_factory, LoggerInterface $logger) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->client = $akamai_client_factory->get();
    $this->akamaiClientConfig = $config->get('akamai.settings');
    $this->eventDispatcher = $event_dispatcher;
    $this->logger = $logger;
  }

  /**
   * {@inheritdoc}
   */
  public function getTimeHint() {
    $timeout = (float) $this->akamaiClientConfig->get('timeout');
    // The max value for getTimeHint is 10.00.
    if ($timeout > 10) {
      return 10;
    }
    elseif ($timeout > 0) {
      return $timeout;
    }
    return 0;
  }

  /**
   * {@inheritdoc}
   */
  public function invalidate(array $invalidations) {
    // Build array of tag strings.
    $tags_to_clear = [];
    // Get Cache Tag formatter.
    $formatter = \Drupal::service('akamai.helper.cachetagformatter');
    foreach ($invalidations as $invalidation) {
      $invalidation->setState(InvalidationInterface::PROCESSING);
      $tag = $formatter->format($invalidation->getExpression());
      if (mb_strlen($tag) > 128) {
        $this->logger->warning('Cache Tag %tag has exceeded the Akamai 128 character tag maximum length.', ['%tag' => $tag]);
      }
      // Remove duplicate entries.
      $tags_to_clear[$tag] = $tag;
    }
    // Change it to a normal array so the JSON conversion goes as expected.
    $tags_to_clear = array_values($tags_to_clear);
    // Set invalidation type to tag.
    $this->client->setType('tag');

    // Instantiate event and alter tags with subscribers.
    $event = new AkamaiPurgeEvents($tags_to_clear);
    $this->eventDispatcher->dispatch(AkamaiPurgeEvents::PURGE_CREATION, $event);
    $tags_to_clear = $event->data;

    // Purge tags.
    $invalidation_state = InvalidationInterface::SUCCEEDED;
    $result = $this->client->purgeTags($tags_to_clear);
    if (!$result) {
      $invalidation_state = InvalidationInterface::FAILED;
    }
    // Set Invalidation status.
    foreach ($invalidations as $invalidation) {
      $invalidation->setState($invalidation_state);
    }
  }

  /**
   * Use a static value for purge queuer performance.
   *
   * @see parent::hasRunTimeMeasurement()
   */
  public function hasRuntimeMeasurement() {
    return FALSE;
  }

}
