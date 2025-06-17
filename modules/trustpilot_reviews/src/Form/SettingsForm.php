<?php

namespace Drupal\trustpilot_reviews\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\trustpilot_reviews\Service\ReviewService;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Configuration form for Trustpilot Reviews settings.
 */
class SettingsForm extends ConfigFormBase {

  /**
   * The review service.
   *
   * @var \Drupal\trustpilot_reviews\Service\ReviewService
   */
  protected $reviewService;

  /**
   * Constructs a SettingsForm object.
   *
   * @param \Drupal\trustpilot_reviews\Service\ReviewService $review_service
   *   The review service.
   */
  public function __construct(ReviewService $review_service) {
    $this->reviewService = $review_service;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('trustpilot_reviews.review_service')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['trustpilot_reviews.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'trustpilot_reviews_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('trustpilot_reviews.settings');

    $form['cache_settings'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Cache Settings'),
    ];

    $form['cache_settings']['clear_cache'] = [
      '#type' => 'submit',
      '#value' => $this->t('Clear Reviews Cache'),
      '#submit' => ['::clearCache'],
      '#button_type' => 'primary',
    ];

    $form['cache_settings']['cache_info'] = [
      '#type' => 'item',
      '#markup' => $this->t('Reviews are cached for 1 hour to improve performance. Use the button above to manually clear the cache.'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);
    $this->messenger()->addMessage($this->t('Settings have been saved.'));
  }

  /**
   * Clear cache submit handler.
   */
  public function clearCache(array &$form, FormStateInterface $form_state) {
    $this->reviewService->clearCache();
    $this->messenger()->addMessage($this->t('Reviews cache has been cleared.'));
  }

}