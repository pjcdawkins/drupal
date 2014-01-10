<?php

/**
 * @file
 * Contains \Drupal\comment\Plugin\Validation\Constraint\CommentNameConstraintValidator.
 */

namespace Drupal\comment\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

/**
 * Validates the CommentName constraint.
 */
class CommentNameConstraintValidator extends ConstraintValidator {

  /**
   * {@inheritdoc}
   */
  public function validate($field_item, Constraint $constraint) {
    $author_name = $field_item->value;
    if (isset($author_name) && ($author_name !== '')) {
      // @todo Improve DX of this after https://drupal.org/node/2078387.
      $author_is_unauthenticated = ($field_item->getEntity()->uid->value === 0);

      // Do not allow unauthenticated comment authors to use a name that is
      // taken by a registered user.
      if ($author_is_unauthenticated) {
        $users = \Drupal::entityManager()->getStorageController('user')->loadByProperties(array('name' => $author_name));
        if (!empty($users)) {
          $this->context->addViolation($constraint->message);
        }
      }
    }
  }
}
