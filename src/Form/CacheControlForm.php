<?php

namespace Drupal\akamai\Form;

use Drupal\akamai\AkamaiClientFactory;
use Drupal\Component\Utility\Unicode;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * A simple form for testing the Akamai integration, or doing manual clears.
 */
class CacheControlForm extends FormBase {

  /**
   * The akamai client.
   *
   * @var \Drupal\akamai\AkamaiClientInterface
   */
  protected $akamaiClient;

  /**
   * A path alias manager.
   *
   * @var \Drupal\Core\Path\AliasManager
   */
  protected $aliasManager;

  /**
   * A messenger interface.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

  /**
   * Constructs a new CacheControlForm.
   *
   * @param \Drupal\akamai\AkamaiClientFactory $factory
   *   The akamai client factory.
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   The Drupal messenger service.
   */
  public function __construct(AkamaiClientFactory $factory, MessengerInterface $messenger) {
    $this->akamaiClient = $factory->get();
    $this->messenger = $messenger;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('akamai.client.factory'),
      $container->get('messenger')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'akamai_cache_control_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('akamai.settings');
    $version = $this->akamaiClient->getPluginId();

    $settings_link = Url::fromRoute('akamai.settings');
    $settings_link = Link::fromTextAndUrl($settings_link->getInternalPath(), $settings_link)->toString();
    $base_path = $config->get('basepath');
    if (!empty($base_path)) {
      $paths_description = $this->t(
        'Enter one URL or CPCode per line. URL entries should either be relative
        to the basepath (e.g. node/1, content/pretty-title,
        sites/default/files/some/image.png), or absolute URLs matching the
        base path :basepath. If you would like to change the configured base path,
        you can do so at @settings.',
        [
          ':basepath' => $base_path,
          '@settings' => $settings_link,
        ]
      );
    }
    else {
      $paths_description = $this->t(
        'There is presently no base path configured. Please set it at @settings.',
        ['@settings' => $settings_link],
      );
    }

    $form['paths'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Paths/URLs/CPCodes'),
      '#description' => $paths_description,
      '#required' => TRUE,
      '#default_value' => $form_state->get('paths'),
    ];

    $domain_override_default = $form_state->get('domain_override') ?: key(array_filter($config->get('domain')));
    $form['domain_override'] = [
      '#type' => 'select',
      '#title' => $this->t('Domain'),
      '#options' => [
        'production' => $this->t('Production'),
        'staging' => $this->t('Staging'),
      ],
      '#default_value' => $domain_override_default,
      '#description' => $this->t('The Akamai domain to use for cache clearing.  Defaults to the Domain setting from the settings page.'),
    ];

    $action_default = $form_state->get('action') ?: $config->get("action_{$version}");
    $actions = $this->akamaiClient->validActions();
    $form['action'] = [
      '#type' => 'radios',
      '#title' => $this->t('Clearing Action Type'),
      '#options' => array_combine($actions, array_map(function ($action) {
        return Unicode::ucwords($action);
      }, $actions)),
      '#default_value' => key(array_filter($action_default)),
      '#description' => $this->t('<b>Remove:</b> Purge the content from Akamai edge server caches. The next time the edge server receives a request for the content, it will retrieve the current version from the origin server. If it cannot retrieve a current version, it will follow instructions in your edge server configuration.<br/><br/><b>Invalidate:</b> Mark the cached content as invalid. The next time the Akamai edge server receives a request for the content, it will send an HTTP conditional get (If-Modified-Since) request to the origin. If the content has changed, the origin server will return a full fresh copy; otherwise, the origin normally will respond that the content has not changed, and Akamai can serve the already-cached content.<br/><br/><b>Note that <em>Remove</em> can increase the load on the origin more than <em>Invalidate</em>.</b> With <em>Invalidate</em>, objects are not removed from cache and full objects are not retrieved from the origin unless they are newer than the cached versions.'),
    ];

    $form['method'] = [
      '#type' => 'radios',
      '#title' => $this->t('Purge Method'),
      '#options' => [
        'url'    => $this->t('URL'),
        'cpcode' => $this->t('Content Provider Code'),
      ],
      '#default_value' => $form_state->get('method') ?: 'url',
      '#description' => $this->t('The Akamai API method to use for cache purge requests.'),
    ];

    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Start Refreshing Content'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $objects = explode(PHP_EOL, trim($form_state->getValue('paths')));
    $method = $form_state->getValue('method');

    if ($method == 'url') {
      if (!empty($objects)) {
        foreach ($objects as $path) {
          // Remove any leading slashes so we can control them later.
          if (isset($path[0]) && $path[0] === '/') {
            $path = ltrim($path, '/');
          }
          $path = trim($path);
          if (!empty($path)) {
            $paths_to_clear[] = $path;
          }
        }
      }
    }
    // Handle cpcodes.
    else {
      $paths_to_clear = $objects;
    }

    if (empty($paths_to_clear)) {
      $form_state->setErrorByName('paths', $this->t('Please enter at least one valid object for %method purging.', ['%method' => $method]));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $action = $form_state->getValue('action');
    $method = $form_state->getValue('method');
    $objects = explode(PHP_EOL, trim($form_state->getValue('paths')));
    $urls_to_clear = [];

    if ($method == 'url') {
      foreach ($objects as $path) {
        $normalized = $this->akamaiClient->normalizeUrl($path);
        if (!empty($normalized)) {
          $urls_to_clear[] = $normalized;
        }
      }
    }
    else {
      $urls_to_clear = $objects;
      $this->akamaiClient->setType('cpcode');
    }

    $this->akamaiClient->setAction($action);
    $this->akamaiClient->setDomain($form_state->getValue('domain_override'));

    try {
      if ($method == 'url') {
        $response = $this->akamaiClient->purgeUrls($urls_to_clear);
      }
      // Handle cpcodes.
      else {
        $response = $this->akamaiClient->purgeCpCodes($urls_to_clear);
      }

      if (!$response) {
        throw new \Exception($this->t('There was an error clearing the cache. Check logs for further detail.')->__toString());
      }

      $this->messenger->addMessage($this->t('Requested :action of the following objects: :objects',
        [':action' => $action, ':objects' => implode(', ', $urls_to_clear)])
      );
    }
    catch (\Exception $e) {
      $this->messenger->addError($e->getMessage());
    }
  }

}
