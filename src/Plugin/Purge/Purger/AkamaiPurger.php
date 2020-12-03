<?php

namespace Drupal\akamai\Plugin\Purge\Purger;

use Drupal\akamai\AkamaiClientFactory;
use Drupal\akamai\Event\AkamaiPurgeEvents;
use Drupal\purge\Plugin\Purge\Purger\PurgerBase;
use Drupal\Core\Config\ConfigFactoryInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Drupal\purge\Plugin\Purge\Invalidation\InvalidationInterface;

/**
 * Akamai Purger.
 *
 * @PurgePurger(
 *   id = "akamai",
 *   label = @Translation("Akamai Purger"),
 *   description = @Translation("Provides a Purge service for Akamai CCU."),
 *   types = {"path", "url", "everything"},
 *   configform = "Drupal\akamai\Form\ConfigForm",
 * )
 */
class AkamaiPurger extends PurgerBase {


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
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('config.factory'),
      $container->get('event_dispatcher'),
      $container->get('akamai.client.factory')
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
   * @param Symfony\Component\EventDispatcher\EventDispatcherInterface $event_dispatcher
   *   The event dispatcher.
   * @param \Drupal\akamai\AkamaiClientFactory $akamai_client_factory
   *   The akamai client factory.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, ConfigFactoryInterface $config, EventDispatcherInterface $event_dispatcher, AkamaiClientFactory $akamai_client_factory) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->client = $akamai_client_factory->get();
    $this->akamaiClientConfig = $config->get('akamai.settings');
    $this->eventDispatcher = $event_dispatcher;
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
    $urls_to_clear = [];
    foreach ($invalidations as $invalidation) {
      $invalidation->setState(InvalidationInterface::PROCESSING);
      $invalidation_type = $invalidation->getPluginId();

      switch ($invalidation_type) {
        case 'path':
          $urls_to_clear[] = $this->normalizePath($invalidation->getExpression());
          break;

        case 'url':
          // URL invalidations should be of type
          // \Drupal\purge\Plugin\Purge\Invalidation\UrlInvalidation.
          try {
            // This SHOULD be an internal path, but in some cases, like
            // when a database is moved between environments, might not be.
            $url = $invalidation->getUrl()->getInternalPath();
          }
          catch (\UnexpectedValueException $e) {
            $url = $this->normalizePath($invalidation->getUrl()->getUri());
          }
          $urls_to_clear[] = $url;
          break;
      }
    }

    // Instantiate event and alter tags with subscribers.
    $event = new AkamaiPurgeEvents($urls_to_clear);
    $this->eventDispatcher->dispatch(AkamaiPurgeEvents::PURGE_CREATION, $event);
    $urls_to_clear = $event->data;

    // Purge all URLs in a single request. Akamai accepts up to 50 (?)
    // invalidations per request.
    if ($this->client->purgeUrls($urls_to_clear)) {
      // Now mark all URLs as cleared.
      foreach ($invalidations as $invalidation) {
        $invalidation->setState(InvalidationInterface::SUCCEEDED);
      }
    }
  }

  /**
   * Use a static value for purge queuer performance.
   *
   * @todo investigate whether we can track performance asynchronously.
   *
   * @see parent::hasRunTimeMeasurement()
   */
  public function hasRuntimeMeasurement() {
    return FALSE;
  }

  /**
   * Converts any path or URL into a normalized path.
   *
   * @param string $url
   *   URL to normalize.
   *
   * @return string
   *   Path suitable for passing to AkamaiClient, like my/path?myquery=str.
   */
  public function normalizePath($url) {
    $parsed_url = parse_url($url);
    $path = isset($parsed_url['path']) ? $parsed_url['path'] : '';
    $query = isset($parsed_url['query']) ? '?' . $parsed_url['query'] : '';
    return $path . $query;
  }

}
