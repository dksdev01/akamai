<?php

namespace Drupal\akamai\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Builds a form to clear urls.
 */
class ClearUrlForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'akamai_clear_url_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $current_uri = $this->getRequest()->getRequestUri();

    $form['path'] = [
      '#type'  => 'hidden',
      '#value' => $current_uri,
    ];
    $form['message'] = [
      '#type'  => 'item',
      '#title' => $this->t('Refresh URL'),
      '#markup' => $current_uri,
    ];
    $form['submit'] = [
      '#type'  => 'submit',
      '#value' => $this->t('Refresh Akamai Cache'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $uri_to_purge = $form_state->getValues()['path'];
    \Drupal::service('akamai.client.factory')->get()->purgeUrl($uri_to_purge);
    drupal_set_message($this->t('Asked Akamai to purge :uri', [':uri' => $uri_to_purge]));
  }

}
