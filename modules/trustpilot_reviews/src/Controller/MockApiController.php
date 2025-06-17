<?php

namespace Drupal\trustpilot_reviews\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Controller for mock Trustpilot API endpoint.
 */
class MockApiController extends ControllerBase {

  /**
   * Returns mock review data.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   JSON response with mock review data.
   */
  public function getReviews() {
    $reviews = [
      [
        'author' => 'John Doe',
        'rating' => 5,
        'title' => 'Excellent service!',
        'content' => 'Great experience, highly recommended.',
        'date' => '2024-03-18',
      ],
      [
        'author' => 'Jane Smith',
        'rating' => 4,
        'title' => 'Good service!',
        'content' => 'Nice support, but delivery was slow.',
        'date' => '2024-03-16',
      ],
      [
        'author' => 'Michael Johnson',
        'rating' => 5,
        'title' => 'Outstanding quality',
        'content' => 'Exceeded my expectations in every way.',
        'date' => '2024-03-15',
      ],
      [
        'author' => 'Sarah Wilson',
        'rating' => 3,
        'title' => 'Average experience',
        'content' => 'The service was okay, but nothing special.',
        'date' => '2024-03-14',
      ],
      [
        'author' => 'David Brown',
        'rating' => 5,
        'title' => 'Highly recommended',
        'content' => 'Fantastic service from start to finish.',
        'date' => '2024-03-12',
      ],
      [
        'author' => 'Emily Davis',
        'rating' => 4,
        'title' => 'Very satisfied',
        'content' => 'Great work quality and timely delivery.',
        'date' => '2024-03-10',
      ],
      [
        'author' => 'Robert Miller',
        'rating' => 5,
        'title' => 'Perfect solution',
        'content' => 'Perfectly and delivered an excellent solution.',
        'date' => '2024-03-08',
      ],
      [
        'author' => 'Lisa Anderson',
        'rating' => 4,
        'title' => 'Good value for money',
        'content' => 'Reasonable pricing and good quality work.',
        'date' => '2024-03-06',
      ],
    ];

    $data = ['reviews' => $reviews];
    
    return new JsonResponse($data);
  }

}