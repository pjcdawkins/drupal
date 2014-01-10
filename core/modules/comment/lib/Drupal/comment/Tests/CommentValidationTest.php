<?php

/**
 * @file
 * Contains \Drupal\comment\Tests\CommentValidationTest.
 */

namespace Drupal\comment\Tests;

use Drupal\system\Tests\Entity\EntityUnitTestBase;

/**
 * Tests comment validation constraints.
 */
class CommentValidationTest extends EntityUnitTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('comment', 'node');

  /**
   * {@inheritdoc}
   */
  public static function getInfo() {
    return array(
      'name' => 'Comment Validation',
      'description' => 'Tests the comment validation constraints.',
      'group' => 'Comment',
    );
  }

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();
    $this->installSchema('node', array('node', 'node_field_data', 'node_field_revision', 'node_revision'));
  }

  /**
   * Tests the comment validation constraints.
   */
  public function testValidation() {
    $node = entity_create('node', array(
      'type' => 'page',
      'title' => 'test',
    ));
    $node->save();

    $comment = entity_create('comment', array(
      'entity_id' => $node->id(),
      // Just use some non-existing dummy field ID here, we are not testing
      // that.
      'field_id' => 'test',
    ));
    $violations = $comment->validate();
    $this->assertEqual(count($violations), 0, 'No violations when validating a default comment.');

    $comment->set('subject', $this->randomString(65));
    $this->assertLengthViolation($comment, 'subject', 64);
    // Make the subject valid.
    $comment->set('subject', $this->randomString());

    $comment->set('name', $this->randomString(61));
    $this->assertLengthViolation($comment, 'name', 60);
    // Validate a name collision between an anonymous comment author name and an
    // existing user account name.
    $user = entity_create('user', array('name' => 'test'));
    $user->save();
    $comment->set('name', 'test');
    $violations = $comment->validate();
    $this->assertEqual(count($violations), 1, "Violation found on author name collision");
    $this->assertEqual($violations[0]->getPropertyPath(), "name");
    $this->assertEqual($violations[0]->getMessage(), t('The name you used belongs to a registered user.'));

    // Make the name valid.
    $comment->set('name', $this->randomString());

    $comment->set('mail', 'invalid');
    $violations = $comment->validate();
    $this->assertEqual(count($violations), 1, 'Violation found when email is invalid');
    $this->assertEqual($violations[0]->getPropertyPath(), 'mail.0.value');
    $this->assertEqual($violations[0]->getMessage(), t('This value is not a valid email address.'));
    $comment->set('mail', NULL);

    $comment->set('homepage', 'http://example.com/' . $this->randomName(237));
    $this->assertLengthViolation($comment, 'homepage', 255);

    $comment->set('homepage', 'invalid');
    $violations = $comment->validate();
    $this->assertEqual(count($violations), 1, 'Violation found when homepage is invalid');
    $this->assertEqual($violations[0]->getPropertyPath(), 'homepage.0.value');
    $this->assertEqual($violations[0]->getMessage(), t('This value should be of the correct primitive type.'));
    $comment->set('homepage', NULL);

    $comment->set('hostname', $this->randomString(256));
    $this->assertLengthViolation($comment, 'hostname', 255);
    $comment->set('hostname', NULL);

    $comment->set('thread', $this->randomString(256));
    $this->assertLengthViolation($comment, 'thread', 255);
  }

  /**
   * Verifies that a length violation exists for the given field.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface\CommentInterface $comment
   *   The comment object to validate.
   * @param string $field_name
   *   The field that violates the maximum length.
   * @param int $length
   *   Number of characters that was exceeded.
   */
  protected function assertLengthViolation($comment, $field_name, $length) {
    $violations = $comment->validate();
    $this->assertEqual(count($violations), 1, "Violation found when $field_name is too long.");
    $this->assertEqual($violations[0]->getPropertyPath(), "$field_name.0.value");
    $this->assertEqual($violations[0]->getMessage(), t('This value is too long. It should have %limit characters or less.', array('%limit' => $length)));
  }
}
