<?php

/**
 * @file
 * Contains Drupal\Tests\Core\Routing\AcceptHeaderMatcherTest.
 */

namespace Drupal\Tests\Core\Routing;

use Drupal\Core\ContentNegotiation;
use Drupal\Core\Routing\AcceptHeaderMatcher;
use Drupal\Tests\Core\Routing\RoutingFixtures;
use Drupal\Tests\UnitTestCase;
use Symfony\Component\HttpFoundation\Request;

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

  /**
   * The matcher object that is going to be tested.
   *
   * @var \Drupal\Core\Routing\AcceptHeaderMatcher
   */
  protected $matcher;

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
    $this->matcher = new AcceptHeaderMatcher(new ContentNegotiation());
  }

  /**
   * Check that JSON routes get filtered and prioritized correctly.
   */
  public function testJsonFilterRoutes() {
    $collection = $this->fixtures->sampleRouteCollection();

    // Tests basic JSON request.
    $request = Request::create('path/two', 'GET');
    $request->headers->set('Accept', 'application/json, text/xml;q=0.9');
    $routes = $this->matcher->filter($collection, $request);
    $this->assertEquals(count($routes), 4, 'The correct number of routes was found.');
    $this->assertNotNull($routes->get('route_c'), 'The json route was found.');
    $this->assertNull($routes->get('route_e'), 'The html route was not found.');
    foreach ($routes as $name => $route) {
      $this->assertEquals($name, 'route_c', 'The json route is the first one in the collection.');
      break;
    }
  }

  /**
   * Tests a JSON request with alternative JSON MIME type Accept header.
   */
  public function testAlternativeJson() {
    $collection = $this->fixtures->sampleRouteCollection();

    $request = Request::create('path/two', 'GET');
    $request->headers->set('Accept', 'application/x-json, text/xml;q=0.9');
    $routes = $this->matcher->filter($collection, $request);
    $this->assertEquals(count($routes), 4, 'The correct number of routes was found.');
    $this->assertNotNull($routes->get('route_c'), 'The json route was found.');
    $this->assertNull($routes->get('route_e'), 'The html route was not found.');
    foreach ($routes as $name => $route) {
      $this->assertEquals($name, 'route_c', 'The json route is the first one in the collection.');
      break;
    }
  }

  /**
   * Tests a standard HTML request.
   */
  public function teststandardHtml() {
    $collection = $this->fixtures->sampleRouteCollection();

    $request = Request::create('path/two', 'GET');
    $request->headers->set('Accept', 'text/html, text/xml;q=0.9');
    $routes = $this->matcher->filter($collection, $request);
    $this->assertEquals(count($routes), 4, 'The correct number of routes was found.');
    $this->assertNull($routes->get('route_c'), 'The json route was not found.');
    $this->assertNotNull($routes->get('route_e'), 'The html route was found.');
    foreach ($routes as $name => $route) {
      $this->assertEquals($name, 'route_e', 'The html route is the first one in the collection.');
      break;
    }
  }

  /**
   * Confirms that the AcceptHeaderMatcher throws an exception for no-route.
   *
   * @expectedException \Symfony\Component\HttpKernel\Exception\NotAcceptableHttpException
   * @expectedExceptionMessage No route found for the specified formats application/json text/xml.
   */
  public function testNoRouteFound() {
    // Remove the sample routes that would match any method.
    $routes = $this->fixtures->sampleRouteCollection();
    $routes->remove('route_a');
    $routes->remove('route_b');
    $routes->remove('route_c');
    $routes->remove('route_d');

    $request = Request::create('path/two', 'GET');
    $request->headers->set('Accept', 'application/json, text/xml;q=0.9');
    $this->matcher->filter($routes, $request);
    $this->fail('No exception was thrown.');
  }

}
