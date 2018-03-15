<?php

namespace Drupal\akamai\Commands;

use Drush\Commands\DrushCommands;

/**
 * Drush commands for interacting with Akamai's CCU API.
 */
class AkamaiCommands extends DrushCommands {

  /**
   * Akamai clear cpcode.
   *
   * @param $cpcode A cpcode to clear. You can provide as many cpcodes you like.
   * @option action Clearing action type.
   * @option domain The Akamai domain to use for cache clearing.
   *
   * @command akamai:clear:cpcode
   * @aliases akcc
   */
  public function clearCpCode($cpcode, $options = [
    'action' => 'remove',
    'domain' => 'production',
  ]) {
    $client = \Drupal::service('akamai.edgegridclient');
    $client->setType('cpcode');
    $client->setAction($options['action']);
    $client->setDomain($options['domain']);
    $cpcodes = explode(' ', $cpcode);
    if ($client->purgeCpCodes($cpcodes)) {
      $this->logger()->success(dt('Akamai Cache Request has been made successfully, please allow 10 minutes for changes to take effect.'));
      $this->logger()->success(dt('Asked Akamai to purge: :cpcode', [':cpcode' => $cpcode]));
    }
  }

  /**
   * Akamai clear URL.
   *
   * @param $path A path to clear. You can provide as many paths you like.
   * @option action Clearing action type.
   * @option domain The Akamai domain to use for cache clearing.
   *
   * @command akamai:clear:url
   * @aliases akcu
   */
  public function clearUrl($path, $options = [
    'action' => 'remove',
    'domain' => 'production',
  ]) {
    $client = \Drupal::service('akamai.edgegridclient');
    $client->setAction($options['action']);
    $client->setDomain($options['domain']);
    $paths = explode(' ', $path);
    if ($client->purgeUrls($paths)) {
      $this->logger()->success(dt('Akamai Cache Request has been made successfully, please allow 10 minutes for changes to take effect.'));
      $this->logger()->success(dt('Asked Akamai to purge: :uri', [':uri' => $path]));
    }
  }

}
