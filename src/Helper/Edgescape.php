<?php

namespace Drupal\akamai\Helper;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Component\Utility\Html;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Provides help for working with Akamai Edgescape.
 *
 * @see https://developer.akamai.com/edgescape
 */
class Edgescape {

  const EDGESCAPE_HEADER = 'X-Akamai-Edgescape';
  const AKAMAI_SETTINGS = 'akamai.settings';
  const EDGESCAPE_SUPPORT = 'edgescape_support';
  const EMPTY_STRING = '';
  const EDGESCAPE_SEPARATOR = ',';
  const ARG_SEPARATOR = '&';

  /**
   * The config factory service.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The current request.
   *
   * @var \Symfony\Component\HttpFoundation\Request
   */
  protected $request;

  /**
   * Constructs an Edgescape helper object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory service.
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   The request stack service.
   */
  public function __construct(ConfigFactoryInterface $config_factory, RequestStack $request_stack) {
    $this->configFactory = $config_factory;
    $this->request = $request_stack->getCurrentRequest();
  }

  /**
   * Get Edgecape information by type.
   *
   * @param string $type
   *   An Edgescape location information type identifier string.
   *
   * @return string
   *   A string containing Edgescape location information, if available.
   */
  public function getInformationByType($type) {
    $info = self::EMPTY_STRING;
    $enabled = $this->configFactory
      ->get(self::AKAMAI_SETTINGS)
      ->get(self::EDGESCAPE_SUPPORT);
    if ($enabled && $header = $this->request->headers->get(self::EDGESCAPE_HEADER, self::EMPTY_STRING)) {
      $header = strtr($header, self::EDGESCAPE_SEPARATOR, self::ARG_SEPARATOR);
      $header_values = [];
      parse_str($header, $header_values);
      if (array_key_exists($type, $header_values)) {
        $info = Html::escape($header_values[(string) $type]);
      }
    }
    return $info;
  }

}
