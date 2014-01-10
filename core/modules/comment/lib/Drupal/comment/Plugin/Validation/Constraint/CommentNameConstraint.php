<?php

/**
 * @file
 * Contains \Drupal\comment\Plugin\Validation\Constraint\CommentNameConstraint.
 */

namespace Drupal\comment\Plugin\Validation\Constraint;

use Drupal\Component\Annotation\Plugin;
use Drupal\Core\Annotation\Translation;
use Symfony\Component\Validator\Constraint;

/**
 * Supports validating comment author names.
 *
 * @Plugin(
 *   id = "CommentName",
 *   label = @Translation("Comment author name", context = "Validation")
 * )
 */
class CommentNameConstraint extends Constraint {

  public $message = 'The name you used belongs to a registered user.';
}
