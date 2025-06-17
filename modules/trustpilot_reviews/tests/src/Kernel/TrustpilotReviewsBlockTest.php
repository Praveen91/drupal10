<?php

namespace Drupal\Tests\trustpilot_reviews\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Drupal\trustpilot_reviews\Plugin\Block\TrustpilotReviewsBlock;

/**
 * Tests the Trustpilot Reviews block.
 *
 * @group trustpilot_reviews
 */
class TrustpilotReviewsBlockTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['system', 'block', 'trustpilot_reviews'];

  /**
   * The block plugin manager.
   *
   * @var \Drupal\Core\Block\BlockManagerInterface
   */
  protected $blockManager;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->installSchema('system', ['sequences']);
    $this->installConfig(['system']);

    $this->blockManager = $this->container->get('plugin.manager.block');
  }

  /**
   * Tests block creation and default configuration.
   */
  public function testBlockCreation() {
    $block = $this->blockManager->createInstance('trustpilot_reviews_block', []);
    $this->assertInstanceOf(TrustpilotReviewsBlock::class, $block);

    $default_config = $block->defaultConfiguration();
    $this->assertEquals(5, $default_config['review_count']);
    $this->assertTrue($default_config['show_title']);
    $this->assertEquals('Customer Reviews', $default_config['block_title']);
    $this->assertTrue($default_config['autoplay']);
    $this->assertEquals(5000, $default_config['autoplay_delay']);
  }

  /**
   * Tests block configuration.
   */
  public function testBlockConfiguration() {
    $configuration = [
      'review_count' => 10,
      'show_title' => FALSE,
      'block_title' => 'Custom Title',
      'autoplay' => FALSE,
      'autoplay_delay' => 3000,
    ];

    $block = $this->blockManager->createInstance('trustpilot_reviews_block', $configuration);
    $block_config = $block->getConfiguration();

    $this->assertEquals(10, $block_config['review_count']);
    $this->assertFalse($block_config['show_title']);
    $this->assertEquals('Custom Title', $block_config['block_title']);
    $this->assertFalse($block_config['autoplay']);
    $this->assertEquals(3000, $block_config['autoplay_delay']);
  }

  /**
   * Tests block build with mock data.
   */
  public function testBlockBuild() {
    // Mock the review service to return test data
    $mock_reviews = [
      [
        'author' => 'Test Author',
        'rating' => 5,
        'title' => 'Test Review',
        'content' => 'This is a test review.',
        'date' => '2024-03-18',
      ],
    ];

    $review_service = $this->getMockBuilder('\Drupal\trustpilot_reviews\Service\ReviewService')
      ->disableOriginalConstructor()
      ->getMock();
    
    $review_service->expects($this->once())
      ->method('getReviews')
      ->with(5)
      ->willReturn($mock_reviews);

    $this->container->set('trustpilot_reviews.review_service', $review_service);

    $block = $this->blockManager->createInstance('trustpilot_reviews_block', []);
    $build = $block->build();

    $this->assertEquals('trustpilot_reviews_carousel', $build['#theme']);
    $this->assertEquals($mock_reviews, $build['#reviews']);
    $this->assertTrue($build['#show_title']);
    $this->assertEquals('Customer Reviews', $build['#block_title']);
    $this->assertTrue($build['#autoplay']);
    $this->assertEquals(5000, $build['#autoplay_delay']);
    $this->assertArrayHasKey('library', $build['#attached']);
    $this->assertContains('trustpilot_reviews/carousel', $build['#attached']['library']);
  }

  /**
   * Tests block build with empty reviews.
   */
  public function testBlockBuildWithEmptyReviews() {
    $review_service = $this->getMockBuilder('\Drupal\trustpilot_reviews\Service\ReviewService')
      ->disableOriginalConstructor()
      ->getMock();
    
    $review_service->expects($this->once())
      ->method('getReviews')
      ->with(5)
      ->willReturn([]);

    $this->container->set('trustpilot_reviews.review_service', $review_service);

    $block = $this->blockManager->createInstance('trustpilot_reviews_block', []);
    $build = $block->build();

    $this->assertArrayHasKey('#markup', $build);
    $this->assertStringContainsString('No reviews available', (string) $build['#markup']);
  }

  /**
   * Tests block access.
   */
  public function testBlockAccess() {
    $block = $this->blockManager->createInstance('trustpilot_reviews_block', []);
    $access = $block->access($this->container->get('current_user'));
    $this->assertTrue($access->isAllowed());
  }

}