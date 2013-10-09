<?php

/**
 * @file
 * Contains Drupal\Tests\Core\Routing\AcceptHeaderMatcherTest.
 */

namespace Drupal\Tests\Core\Routing;

use Drupal\Core\ContentNegotiation;
use Drupal\Core\Routing\AcceptHeaderMatcher;
use Drupal\system\Tests\Routing\RoutingFixtures;
use Drupal\Tests\UnitTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotAcceptableHttpException;

/**
 * Basic tests for the AcceptHeaderMatcher class.
 */
class AcceptHeaderMatcherTest extends UnitTestCase {

  /**
   * A collection of shared fixture data for tests.
   *
   * @var RoutingFixtures
   */
  protected $fixtures;

  public static function getInfo() {
    return array(
      'name' => 'Partial matcher MIME types tests',
      'description' => 'Confirm that the mime types partial matcher is functioning properly.',
      'group' => 'Routing',
    );
  }

  public function setUp() {
    parent::setUp();

    $this->fixtures = new RoutingFixtures();
  }

  /**
   * Confirms that the MimeType matcher matches properly.
   */
  public function testFilterRoutes() {

    $matcher = new AcceptHeaderMatcher(new ContentNegotiation());
    $collection = $this->fixtures->sampleRouteCollection();

    // Tests basic JSON request.
    $request = Request::create('path/two', 'GET');
    $request->headers->set('Accept', 'application/json, text/xml;q=0.9');
    $routes = $matcher->filter($collection, $request);
    $this->assertEquals(count($routes), 4, 'The correct number of routes was found.');
    $this->assertNotNull($routes->get('route_c'), 'The json route was found.');
    $this->assertNull($routes->get('route_e'), 'The html route was not found.');
    foreach ($routes as $name => $route) {
      $this->assertEquals($name, 'route_c', 'The json route is the first one in the collection.');
      break;
    }

    // Tests JSON request with alternative JSON MIME type Accept header.
    $request = Request::create('path/two', 'GET');
    $request->headers->set('Accept', 'application/x-json, text/xml;q=0.9');
    $routes = $matcher->filter($collection, $request);
    $this->assertEquals(count($routes), 4, 'The correct number of routes was found.');
    $this->assertNotNull($routes->get('route_c'), 'The json route was found.');
    $this->assertNull($routes->get('route_e'), 'The html route was not found.');
    foreach ($routes as $name => $route) {
      $this->assertEquals($name, 'route_c', 'The json route is the first one in the collection.');
      break;
    }

    // Tests basic HTML request.
    $request = Request::create('path/two', 'GET');
    $request->headers->set('Accept', 'text/html, text/xml;q=0.9');
    $routes = $matcher->filter($collection, $request);
    $this->assertEquals(count($routes), 4, 'The correct number of routes was found.');
    $this->assertNull($routes->get('route_c'), 'The json route was not found.');
    $this->assertNotNull($routes->get('route_e'), 'The html route was found.');
    foreach ($routes as $name => $route) {
      $this->assertEquals($name, 'route_e', 'The html route is the first one in the collection.');
      break;
    }
  }

  /**
   * Confirms that the AcceptHeaderMatcher matcher throws an exception for no-route.
   */
  public function testNoRouteFound() {
    $matcher = new AcceptHeaderMatcher(new ContentNegotiation());

    // Remove the sample routes that would match any method.
    $routes = $this->fixtures->sampleRouteCollection();
    $routes->remove('route_a');
    $routes->remove('route_b');
    $routes->remove('route_c');
    $routes->remove('route_d');

    try {
      $request = Request::create('path/two', 'GET');
      $request->headers->set('Accept', 'application/json, text/xml;q=0.9');
      $matcher->filter($routes, $request);
      $this->fail('No exception was thrown.');
    }
    catch (NotAcceptableHttpException $e) {
      $this->assertEquals($e->getMessage(), 'No route found for the specified formats application/json text/xml.');
    }
  }

}
