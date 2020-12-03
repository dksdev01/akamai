<?php

namespace Drupal\akamai;

use Drupal\Component\Plugin\PluginInspectionInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Plugin\PluginFormInterface;

/**
 * Contains the \Drupal\akamai\AkamaiClientInterface interface.
 */
interface AkamaiClientInterface extends ContainerFactoryPluginInterface, PluginInspectionInterface, PluginFormInterface {

  /**
   * String constant for the production network.
   */
  const NETWORK_PRODUCTION = 'production';

  /**
   * String constant for the staging network.
   */
  const NETWORK_STAGING = 'staging';

  /**
   * The maximum size, in bytes, of a request body allowed by the API.
   */
  const MAX_BODY_SIZE = 50000;

  /**
   * Sets the domain to clear.
   *
   * @param string $domain
   *   The domain to clear, either 'production' or 'staging'.
   *
   * @return $this
   */
  public function setDomain($domain);

  /**
   * Helper function to set the action for purge request.
   *
   * @param string $action
   *   Action to be taken while purging.
   *
   * @return $this
   */
  public function setAction($action);

  /**
   * Sets the type of purge.
   *
   * @param string $type
   *   The type of purge, either 'arl' or 'cpcode'.
   *
   * @return $this
   */
  public function setType($type);

  /**
   * Verifies that the body of a purge request will be under 50,000 bytes.
   *
   * @param array $paths
   *   An array of paths to be purged.
   *
   * @return bool
   *   TRUE if the body size is below the limit, otherwise FALSE.
   */
  public function bodyIsBelowLimit(array $paths = []);

}
