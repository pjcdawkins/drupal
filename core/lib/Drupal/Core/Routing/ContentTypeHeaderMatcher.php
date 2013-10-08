<?php

/**
 * @file
 * Contains Drupal\Core\Routing\ContentTypeHeaderMatcher.
 */

namespace Drupal\Core\Routing;

use Drupal\Core\ContentNegotiation;
use Symfony\Cmf\Component\Routing\NestedMatcher\RouteFilterInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Routing\RouteCollection;

/**
 * Filters routes based on the HTTP Content-type header.
 */
class ContentTypeHeaderMatcher implements RouteFilterInterface {

  /**
   * {@inheritdoc}
   */
  public function filter(RouteCollection $collection, Request $request) {
    // The Content-type header does not make sense on GET requests, so nothing
    // to filter in this case.
    if ($request->getMethod() == 'GET') {
      return $collection;
    }

    $format = $request->getContentType();

    foreach ($collection as $name => $route) {
      $supported_formats = array_filter(explode('|', $route->getRequirement('_content_type_format')));
      if (empty($supported_formats)) {
        // No restriction on the route, so we move the route to the end of the
        // collection by re-adding it. That way generic routes sink down in the
        // list and exact matching routes stay on top.
        $collection->add($name, $route);
      }
      elseif (!in_array($format, $supported_formats)) {
        $collection->remove($name);
      }
    }
    if (count($collection)) {
      return $collection;
    }
    // We do not throw a
    // \Symfony\Component\Routing\Exception\ResourceNotFoundException here
    // because we don't want to return a 404 status code, but rather a 400.
    throw new BadRequestHttpException('No route found that matches the Content-Type header.');
  }

}
