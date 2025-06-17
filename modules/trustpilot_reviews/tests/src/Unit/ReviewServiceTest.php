<?php

namespace Drupal\Tests\trustpilot_reviews\Unit;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\Tests\UnitTestCase;
use Drupal\trustpilot_reviews\Service\ReviewService;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Psr7\Response;
use Prophecy\PhpUnit\ProphecyTrait;

/**
 * @coversDefaultClass \Drupal\trustpilot_reviews\Service\ReviewService
 * @group trustpilot_reviews
 */
class ReviewServiceTest extends UnitTestCase {

  use ProphecyTrait;

  /**
   * The mocked HTTP client.
   *
   * @var \GuzzleHttp\ClientInterface|\Prophecy\Prophecy\ObjectProphecy
   */
  protected $httpClient;

  /**
   * The mocked cache backend.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface|\Prophecy\Prophecy\ObjectProphecy
   */
  protected $cache;

  /**
   * The mocked logger factory.
   *
   * @var \Drupal\Core\Logger\LoggerChannelFactoryInterface|\Prophecy\Prophecy\ObjectProphecy
   */
  protected $loggerFactory;

  /**
   * The mocked logger channel.
   *
   * @var \Drupal\Core\Logger\LoggerChannelInterface|\Prophecy\Prophecy\ObjectProphecy
   */
  protected $logger;

  /**
   * The service under test.
   *
   * @var \Drupal\trustpilot_reviews\Service\ReviewService
   */
  protected $reviewService;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->httpClient = $this->prophesize(ClientInterface::class);
    $this->cache = $this->prophesize(CacheBackendInterface::class);
    $this->loggerFactory = $this->prophesize(LoggerChannelFactoryInterface::class);
    $this->logger = $this->prophesize(LoggerChannelInterface::class);

    $this->loggerFactory->get('trustpilot_reviews')->willReturn($this->logger->reveal());

    $this->reviewService = new ReviewService(
      $this->httpClient->reveal(),
      $this->cache->reveal(),
      $this->loggerFactory->reveal()
    );
  }

  /**
   * @covers ::getReviews
   */
  public function testGetReviewsFromCache() {
    $cached_reviews = [
      [
        'author' => 'John Doe',
        'rating' => 5,
        'title' => 'Great service',
        'content' => 'Very satisfied',
        'date' => '2024-03-18',
      ],
    ];

    $cache_item = (object) ['data' => $cached_reviews];
    $this->cache->get('trustpilot_reviews_data')->willReturn($cache_item);

    $result = $this->reviewService->getReviews(5);

    $this->assertEquals($cached_reviews, $result);
  }

  /**
   * @covers ::getReviews
   */
  public function testGetReviewsFromApiWhenCacheEmpty() {
    $api_reviews = [
      [
        'author' => 'Jane Smith',
        'rating' => 4,
        'title' => 'Good experience',
        'content' => 'Mostly satisfied',
        'date' => '2024-03-17',
      ],
    ];

    $response_data = ['reviews' => $api_reviews];
    $response = new Response(200, [], json_encode($response_data));

    $this->cache->get('trustpilot_reviews_data')->willReturn(FALSE);
    $this->httpClient->get(\Prophecy\Argument::any())->willReturn($response);
    $this->cache->set(
      'trustpilot_reviews_data',
      $api_reviews,
      \Prophecy\Argument::any(),
      ['trustpilot_reviews']
    )->shouldBeCalled();

    $result = $this->reviewService->getReviews(5);

    $this->assertEquals($api_reviews, $result);
  }

  /**
   * @covers ::getReviews
   */
  public function testGetReviewsHandlesApiError() {
    $this->cache->get('trustpilot_reviews_data')->willReturn(FALSE);
    $this->httpClient->get(\Prophecy\Argument::any())
      ->willThrow(new RequestException('API Error', $this->prophesize(\Psr\Http\Message\RequestInterface::class)->reveal()));

    $this->logger->error(
      'Failed to fetch reviews: @message',
      ['@message' => 'API Error']
    )->shouldBeCalled();

    $result = $this->reviewService->getReviews(5);

    $this->assertEquals([], $result);
  }

  /**
   * @covers ::getReviews
   */
  public function testGetReviewsLimitsResults() {
    $cached_reviews = [
      ['author' => 'User 1', 'rating' => 5, 'title' => 'Title 1', 'content' => 'Content 1', 'date' => '2024-03-18'],
      ['author' => 'User 2', 'rating' => 4, 'title' => 'Title 2', 'content' => 'Content 2', 'date' => '2024-03-17'],
      ['author' => 'User 3', 'rating' => 3, 'title' => 'Title 3', 'content' => 'Content 3', 'date' => '2024-03-16'],
    ];

    $cache_item = (object) ['data' => $cached_reviews];
    $this->cache->get('trustpilot_reviews_data')->willReturn($cache_item);

    $result = $this->reviewService->getReviews(2);

    $this->assertCount(2, $result);
    $this->assertEquals($cached_reviews[0], $result[0]);
    $this->assertEquals($cached_reviews[1], $result[1]);
  }

  /**
   * @covers ::clearCache
   */
  public function testClearCache() {
    $this->cache->delete('trustpilot_reviews_data')->shouldBeCalled();
    $this->logger->info('Reviews cache cleared.')->shouldBeCalled();

    $this->reviewService->clearCache();
  }

}