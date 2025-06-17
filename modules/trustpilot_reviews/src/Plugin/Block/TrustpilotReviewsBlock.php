<?php

namespace Drupal\trustpilot_reviews\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\trustpilot_reviews\Service\ReviewService;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a 'Trustpilot Reviews Carousel' block.
 *
 * @Block(
 *   id = "trustpilot_reviews_block",
 *   admin_label = @Translation("Trustpilot Reviews Carousel"),
 *   category = @Translation("Custom"),
 * )
 */
class TrustpilotReviewsBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * The review service.
   *
   * @var \Drupal\trustpilot_reviews\Service\ReviewService
   */
  protected $reviewService;

  /**
   * Constructs a new TrustpilotReviewsBlock instance.
   *
   * @param array $configuration
   *   The plugin configuration.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\trustpilot_reviews\Service\ReviewService $review_service
   *   The review service.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, ReviewService $review_service) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->reviewService = $review_service;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('trustpilot_reviews.review_service')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'review_count' => 5,
      'show_title' => TRUE,
      'block_title' => $this->t('Customer Reviews'),
      'autoplay' => TRUE,
      'autoplay_delay' => 5000,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $form = parent::blockForm($form, $form_state);

    $config = $this->getConfiguration();

    $form['review_count'] = [
      '#type' => 'number',
      '#title' => $this->t('Number of reviews to display'),
      '#default_value' => $config['review_count'],
      '#min' => 1,
      '#max' => 20,
      '#required' => TRUE,
    ];

    $form['show_title'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Show block title'),
      '#default_value' => $config['show_title'],
    ];

    $form['block_title'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Block title'),
      '#default_value' => $config['block_title'],
      '#states' => [
        'visible' => [
          ':input[name="settings[show_title]"]' => ['checked' => TRUE],
        ],
      ],
    ];

    $form['autoplay'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable autoplay'),
      '#default_value' => $config['autoplay'],
    ];

    $form['autoplay_delay'] = [
      '#type' => 'number',
      '#title' => $this->t('Autoplay delay (milliseconds)'),
      '#default_value' => $config['autoplay_delay'],
      '#min' => 1000,
      '#max' => 10000,
      '#states' => [
        'visible' => [
          ':input[name="settings[autoplay]"]' => ['checked' => TRUE],
        ],
      ],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    parent::blockSubmit($form, $form_state);
    $values = $form_state->getValues();
    $this->configuration['review_count'] = $values['review_count'];
    $this->configuration['show_title'] = $values['show_title'];
    $this->configuration['block_title'] = $values['block_title'];
    $this->configuration['autoplay'] = $values['autoplay'];
    $this->configuration['autoplay_delay'] = $values['autoplay_delay'];
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $config = $this->getConfiguration();
    $reviews = $this->reviewService->getReviews($config['review_count']);

    if (empty($reviews)) {
      return [
        '#markup' => $this->t('No reviews available at the moment.'),
      ];
    }

    return [
      '#theme' => 'trustpilot_reviews_carousel',
      '#reviews' => $reviews,
      '#show_title' => $config['show_title'],
      '#block_title' => $config['block_title'],
      '#autoplay' => $config['autoplay'],
      '#autoplay_delay' => $config['autoplay_delay'],
      '#attached' => [
        'library' => ['trustpilot_reviews/carousel'],
      ],
      '#cache' => [
        'tags' => ['trustpilot_reviews'],
        'max-age' => 3600, // 1 hour
      ],
    ];
  }

}