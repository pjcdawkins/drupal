<?php

/**
 * @file
 * Contains Drupal\Core\Routing\AcceptHeaderMatcher.
 */

namespace Drupal\Core\Routing;

use Drupal\Component\Utility\String;
use Drupal\Core\ContentNegotiation;
use Symfony\Cmf\Component\Routing\NestedMatcher\RouteFilterInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotAcceptableHttpException;
use Symfony\Component\Routing\RouteCollection;

/**
 * Filters routes based on the media type specified in the HTTP Accept headers.
 */
class AcceptHeaderMatcher implements RouteFilterInterface {

  /**
   * The content negotiation library.
   *
   * @var \Drupal\Core\ContentNegotiation
   */
  protected $contentNegotiation;

  /**
   * Constructs a new AcceptHeaderMatcher.
   *
   * @param \Drupal\Core\ContentNegotiation $cotent_negotiation
   *   The content negotiation library.
   */
  public function __construct(ContentNegotiation $content_negotiation) {
    $this->contentNegotiation = $content_negotiation;
  }

  /**
   * {@inheritdoc}
   */
  public function filter(RouteCollection $collection, Request $request) {
    // Generates a list of Symfony formats matching the acceptable MIME types.
    // @todo replace by proper content negotiation library.
    $acceptable_mime_types = $request->getAcceptableContentTypes();
    $acceptable_formats = array_filter(array_map(array($request, 'getFormat'), $acceptable_mime_types));
    $primary_format = $this->contentNegotiation->getContentType($request);

    // Collect a list of routes that match the primary request content type.
    $primary_matches = new RouteCollection();
    // List of routes that match any of multiple specified content types in the
    // request, which should get a lower priority.
    $somehow_matches = new RouteCollection();

    foreach ($collection as $name => $route) {
      // _format could be a |-delimited list of supported formats.
      $supported_formats = array_filter(explode('|', $route->getRequirement('_format')));

      if (empty($supported_formats)) {
        // No format restriction on the route, so it always matches.
        $somehow_matches->add($name, $route);
      }
      elseif (in_array($primary_format, $supported_formats)) {
        // Perfect match, which will get a higher priority.
        $primary_matches->add($name, $route);
      }
      // The route partially matches if it doesn't care about format, if it
      // explicitly allows any format, or if one of its allowed formats is
      // in the request's list of acceptable formats.
      elseif (in_array('*/*', $acceptable_mime_types) || array_intersect($acceptable_formats, $supported_formats)) {
        $somehow_matches->add($name, $route);
      }
    }
    // Append the generic routes to the end, which will give them a lower
    // priority.
    $primary_matches->addCollection($somehow_matches);

    if (count($primary_matches)) {
      return $primary_matches;
    }

    // We do not throw a
    // \Symfony\Component\Routing\Exception\ResourceNotFoundException here
    // because we don't want to return a 404 status code, but rather a 406.
    throw new NotAcceptableHttpException(String::format('No route found for the specified formats @formats.', array('@formats' => implode(' ', $acceptable_mime_types))));
  }

}
