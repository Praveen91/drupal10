<?php

namespace Drupal\Tests\trustpilot_reviews\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Tests the Trustpilot Reviews functionality.
 *
 * @group trustpilot_reviews
 */
class TrustpilotReviewsTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['block', 'trustpilot_reviews'];

  /**
   * A user with admin permissions.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $adminUser;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->adminUser = $this->drupalCreateUser([
      'administer blocks',
      'administer trustpilot reviews',
    ]);
  }

  /**
   * Tests the mock API endpoint.
   */
  public function testMockApiEndpoint() {
    $this->drupalGet('/mock-reviews');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->responseHeaderContains('Content-Type', 'application/json');

    $response = $this->getSession()->getPage()->getContent();
    $data = json_decode($response, TRUE);

    $this->assertArrayHasKey('reviews', $data);
    $this->assertIsArray($data['reviews']);
    $this->assertNotEmpty($data['reviews']);

    // Check first review structure
    $first_review = $data['reviews'][0];
    $this->assertArrayHasKey('author', $first_review);
    $this->assertArrayHasKey('rating', $first_review);
    $this->assertArrayHasKey('title', $first_review);
    $this->assertArrayHasKey('content', $first_review);
    $this->assertArrayHasKey('date', $first_review);
  }

  /**
   * Tests the block placement and display.
   */
  public function testBlockPlacement() {
    $this->drupalLogin($this->adminUser);

    // Place the block
    $this->drupalGet('admin/structure/block');
    $this->clickLink('Place block');
    $this->clickLink('Trustpilot Reviews Carousel');

    // Configure the block
    $edit = [
      'region' => 'content',
      'settings[review_count]' => 3,
      'settings[show_title]' => TRUE,
      'settings[block_title]' => 'Customer Testimonials',
      'settings[autoplay]' => FALSE,
    ];
    $this->submitForm($edit, 'Save block');

    // Visit the front page to see the block
    $this->drupalGet('<front>');
    $this->assertSession()->pageTextContains('Customer Testimonials');
    $this->assertSession()->elementExists('css', '.trustpilot-reviews-carousel');
  }

  /**
   * Tests the admin settings page.
   */
  public function testAdminSettings() {
    $this->drupalLogin($this->adminUser);

    $this->drupalGet('admin/config/content/trustpilot-reviews');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->pageTextContains('Cache Settings');
    $this->assertSession()->buttonExists('Clear Reviews Cache');

    // Test cache clearing
    $this->submitForm([], 'Clear Reviews Cache');
    $this->assertSession()->pageTextContains('Reviews cache has been cleared.');
  }

  /**
   * Tests block configuration form validation.
   */
  public function testBlockConfigurationValidation() {
    $this->drupalLogin($this->adminUser);

    $this->drupalGet('admin/structure/block');
    $this->clickLink('Place block');
    $this->clickLink('Trustpilot Reviews Carousel');

    // Test with invalid review count
    $edit = [
      'region' => 'content',
      'settings[review_count]' => 0,
    ];
    $this->submitForm($edit, 'Save block');
    $this->assertSession()->pageTextContains('Number of reviews to display field is required.');

    // Test with valid configuration
    $edit = [
      'region' => 'content',
      'settings[review_count]' => 5,
      'settings[show_title]' => TRUE,
      'settings[block_title]' => 'Test Reviews',
      'settings[autoplay]' => TRUE,
      'settings[autoplay_delay]' => 3000,
    ];
    $this->submitForm($edit, 'Save block');
    $this->assertSession()->pageTextContains('The block configuration has been saved.');
  }

  /**
   * Tests anonymous user access to reviews.
   */
  public function testAnonymousAccess() {
    // Place a block as admin first
    $this->drupalLogin($this->adminUser);
    $this->drupalGet('admin/structure/block');
    $this->clickLink('Place block');
    $this->clickLink('Trustpilot Reviews Carousel');
    $this->submitForm(['region' => 'content'], 'Save block');
    $this->drupalLogout();

    // Test anonymous access
    $this->drupalGet('<front>');
    $this->assertSession()->elementExists('css', '.trustpilot-reviews-carousel');

    // Test API access for anonymous users
    $this->drupalGet('/mock-reviews');
    $this->assertSession()->statusCodeEquals(200);
  }

}