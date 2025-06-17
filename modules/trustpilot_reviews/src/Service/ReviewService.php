<?php

namespace Drupal\trustpilot_reviews\Service;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\RequestException;

/**
 * Service for fetching and caching Trustpilot reviews.
 */
class ReviewService {

  /**
   * The HTTP client.
   *
   * @var \GuzzleHttp\ClientInterface
   */
  protected $httpClient;

  /**
   * The cache backend.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface
   */
  protected $cache;

  /**
   * The logger factory.
   *
   * @var \Drupal\Core\Logger\LoggerChannelFactoryInterface
   */
  protected $loggerFactory;

  /**
   * Cache ID for reviews.
   *
   * @var string
   */
  const CACHE_ID = 'trustpilot_reviews_data';

  /**
   * Cache lifetime (1 hour).
   *
   * @var int
   */
  const CACHE_LIFETIME = 3600;

  /**
   * Constructs a ReviewService object.
   *
   * @param \GuzzleHttp\ClientInterface $http_client
   *   The HTTP client.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache
   *   The cache backend.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger_factory
   *   The logger factory.
   */
  public function __construct(ClientInterface $http_client, CacheBackendInterface $cache, LoggerChannelFactoryInterface $logger_factory) {
    $this->httpClient = $http_client;
    $this->cache = $cache;
    $this->loggerFactory = $logger_factory;
  }

  /**
   * Fetches reviews from the mock API with caching.
   *
   * @param int $limit
   *   Number of reviews to return.
   *
   * @return array
   *   Array of review data.
   */
  public function getReviews($limit = 10) {
    // Try to get from cache first.
    $cached_data = $this->cache->get(self::CACHE_ID);
    if ($cached_data && !empty($cached_data->data)) {
      $reviews = $cached_data->data;
    }
    else {
      // Fetch from API.
      $reviews = $this->fetchFromApi();
      
      // Cache the results.
      if (!empty($reviews)) {
        $this->cache->set(
          self::CACHE_ID,
          $reviews,
          time() + self::CACHE_LIFETIME,
          ['trustpilot_reviews']
        );
      }
    }

    // Limit the number of reviews returned.
    if (!empty($reviews) && count($reviews) > $limit) {
      $reviews = array_slice($reviews, 0, $limit);
    }

    return $reviews ?: [];
  }

  /**
   * Fetches reviews from the mock API endpoint.
   *
   * @return array
   *   Array of review data or empty array on failure.
   */
  protected function fetchFromApi() {
    try {
      $base_url = \Drupal::request()->getSchemeAndHttpHost();
      $response = $this->httpClient->get($base_url . '/mock-reviews');
      $data = json_decode($response->getBody(), TRUE);
      
      if (isset($data['reviews']) && is_array($data['reviews'])) {
        return $data['reviews'];
      }
    }
    catch (RequestException $e) {
      $this->loggerFactory->get('trustpilot_reviews')->error('Failed to fetch reviews: @message', [
        '@message' => $e->getMessage(),
      ]);
    }
    catch (\Exception $e) {
      $this->loggerFactory->get('trustpilot_reviews')->error('Unexpected error fetching reviews: @message', [
        '@message' => $e->getMessage(),
      ]);
    }

    return [];
  }

  /**
   * Clears the reviews cache.
   */
  public function clearCache() {
    $this->cache->delete(self::CACHE_ID);
    $this->loggerFactory->get('trustpilot_reviews')->info('Reviews cache cleared.');
  }

}