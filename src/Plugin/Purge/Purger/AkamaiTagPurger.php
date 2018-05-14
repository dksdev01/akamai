<?php

namespace Drupal\akamai\Plugin\Purge\Purger;

use Drupal\purge\Plugin\Purge\Purger\PurgerBase;
use Drupal\Core\Config\ConfigFactoryInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
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
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('config.factory')
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
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, ConfigFactoryInterface $config) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->client = \Drupal::service('akamai.client.factory')->get();
    $this->akamaiClientConfig = $config->get('akamai.settings');
  }

  /**
   * {@inheritdoc}
   */
  public function getTimeHint() {
    // The max value for getTimeHint is 10.00.
    $return = $this->akamaiClientConfig->get('timeout') <= 10 ?: 10;
    return (float) $return;
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
      $tags_to_clear[] = $formatter->format($invalidation->getExpression());
    }
    // Remove duplicate entries.
    $tags_to_clear = array_keys(array_flip($tags_to_clear));
    // Set invalidation type to tag.
    $this->client->setType('tag');
    // Purge tags.
    $invalidation_state = InvalidationInterface::SUCCEEDED;
    $result = $this->client->purgeTags($tags_to_clear);
    $invalidation_state = $result ?: InvalidationInterface::FAILED;
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
