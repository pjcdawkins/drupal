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
    if ($format === NULL) {
      // Even if the request has no Content-type header we initialize it here
      // with a default so that we can filter out routes that require a
      // different one later.
      $format = 'html';
    }
    foreach ($collection as $name => $route) {
      $supported_formats = array_filter(explode('|', $route->getRequirement('_content_type_format')));
      if (empty($supported_formats)) {
        // The route has not specified any Content-Type restrictions, so we
        // assume default restrictions.
        $supported_formats = array('html', 'drupal_ajax', 'drupal_modal', 'drupal_dialog');
      }
      if (!in_array($format, $supported_formats)) {
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
