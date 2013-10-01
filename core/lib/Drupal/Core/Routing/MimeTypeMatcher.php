<?php

/**
 * @file
 * Contains Drupal\Core\Routing\MimeTypeMatcher.
 */

namespace Drupal\Core\Routing;

use Drupal\Core\ContentNegotiation;
use Symfony\Cmf\Component\Routing\NestedMatcher\RouteFilterInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotAcceptableHttpException;
use Symfony\Component\Routing\RouteCollection;

/**
 * This class filters routes based on the media type in HTTP Accept headers.
 */
class MimeTypeMatcher implements RouteFilterInterface {

  /**
   * The content negotiation library.
   *
   * @var \Drupal\Core\ContentNegotiation
   */
  protected $contentNegotiation;

  /**
   * Constructs a new MimeTypeMatcher.
   *
   * @param \Drupal\Core\ContentNegotiation $cotent_negotiation
   *   The content negotiation library.
   */
  public function __construct(ContentNegotiation $content_negotiation) {
    $this->contentNegotiation = $content_negotiation;
  }

  /**
   * Implements \Symfony\Cmf\Component\Routing\NestedMatcher\RouteFilterInterface::filter()
   */
  public function filter(RouteCollection $collection, Request $request) {
    // Generates a list of Symfony formats matching the acceptable MIME types.
    // @todo replace by proper content negotiation library.
    $acceptable_mime_types = $request->getAcceptableContentTypes();
    $acceptable_formats = array_map(array($request, 'getFormat'), $acceptable_mime_types);
    $primary_format = $this->contentNegotiation->getContentType($request);

    // Collect a list of routes that match the primary request content type.
    $primary_matches = new RouteCollection();
    // List of routes that match any of multiple specified content types in the
    // request.
    $somehow_matches = new RouteCollection();

    foreach ($collection as $name => $route) {
      // _format could be a |-delimited list of supported formats.
      $supported_formats = array_filter(explode('|', $route->getRequirement('_format')));

      // HTML is the default format if the route does not specify it. We also
      // need to add those other weird Drupal AJAX formats here, otherwise we
      // would exclude the AJAX routes.
      // @todo Figure out why adding "_format: drupal_ajax" to AJAX routes does
      // not work.
      if (empty($supported_formats)) {
        $supported_formats = array('html', 'drupal_ajax', 'drupal_modal', 'drupal_dialog');
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

    throw new NotAcceptableHttpException(format_string('No route found for the specified formats @formats.', array('@formats' => implode(' ', $acceptable_mime_types))));
  }

}
