<?php

/**
 * @file
 * Contains Drupal\Core\Routing\MimeTypeMatcher.
 */

namespace Drupal\Core\Routing;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotAcceptableHttpException;
use Symfony\Component\Routing\RouteCollection;
use Symfony\Cmf\Component\Routing\NestedMatcher\RouteFilterInterface;

/**
 * This class filters routes based on the media type in HTTP Accept headers.
 */
class MimeTypeMatcher implements RouteFilterInterface {


  /**
   * Implements \Symfony\Cmf\Component\Routing\NestedMatcher\RouteFilterInterface::filter()
   */
  public function filter(RouteCollection $collection, Request $request) {
    // Generates a list of Symfony formats matching the acceptable MIME types.
    // @todo replace by proper content negotiation library.
    $acceptable_mime_types = $request->getAcceptableContentTypes();
    $acceptable_formats = array_map(array($request, 'getFormat'), $acceptable_mime_types);
    $negotiation = new \Drupal\Core\ContentNegotiation();
    $primary_format = $negotiation->getContentType($request);

    // @todo this does not work because the routing system only hands us 3 REST
    // routes for /node/{node}. Where is the usual node route for the HTML page?

    // Collect a list of routes that match the primary request content type.
    $primary_matches = new RouteCollection();
    // List of routes that match any of multiple specified content types in the
    // request.
    $somehow_matches = new RouteCollection();

    foreach ($collection as $name => $route) {
      // _format could be a |-delimited list of supported formats.
      $supported_formats = array_filter(explode('|', $route->getRequirement('_format')));

      // HTML is the default format if the route does not specify it.
      if (empty($supported_formats)) {
        $supported_formats = array('html');
      }

      if (in_array($primary_format, $supported_formats)) {
        $primary_matches->add($name, $route);
      }
      // The route partially matches if it doesn't care about format, if it
      // explicitly allows any format, or if one of its allowed formats is
      // in the request's list of acceptable formats.
      elseif (in_array('*/*', $acceptable_mime_types) || array_intersect($acceptable_formats, $supported_formats)) {
        $somehow_matches->add($name, $route);
      }
    }

    if (count($primary_matches)) {
      return $primary_matches;
    }

    if (count($somehow_matches)) {
      return $somehow_matches;
    }

    throw new NotAcceptableHttpException();
  }

}
