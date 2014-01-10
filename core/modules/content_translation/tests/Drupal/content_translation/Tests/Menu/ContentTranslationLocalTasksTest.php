<?php

/**
 * @file
 * Contains \Drupal\block\Tests\Menu\BlockLocalTasksTest.
 */

namespace Drupal\content_translation\Tests\Menu;

use Drupal\Tests\Core\Menu\LocalTaskIntegrationTest;

/**
 * Tests existence of block local tasks.
 *
 * @group Drupal
 * @group Block
 */
class ContentTranslationLocalTasksTest extends LocalTaskIntegrationTest {

  public static function getInfo() {
    return array(
      'name' => 'Content translation local tasks test',
      'description' => 'Test content translation local tasks.',
      'group' => 'Content Translation',
    );
  }

  public function setUp() {
    $this->directoryList = array(
      'content_translation' => 'core/modules/content_translation',
      'node' => 'core/modules/node',
    );
    parent::setUp();

    $entity_type = $this->getMock('Drupal\Core\Entity\EntityTypeInterface');
    $entity_type->expects($this->any())
      ->method('getLinkTemplate')
      ->will($this->returnValueMap(array(
        array('canonical', '/node/{node}'),
        array('drupal:content-translation-overview', '/node/{node}/translations'),
      )));
    $content_translation_manager = $this->getMock('Drupal\content_translation\ContentTranslationManagerInterface');
    $content_translation_manager->expects($this->any())
      ->method('getSupportedEntityTypes')
      ->will($this->returnValue(array(
        'node' => $entity_type,
      )));
    \Drupal::getContainer()->set('content_translation.manager', $content_translation_manager);

    // Route provider for injecting node.view into derivative lookup.
    $collection = $this->getMockBuilder('Symfony\Component\Routing\RouteCollection')
      ->disableOriginalConstructor()
      ->setMethods(array('all'))
      ->getMock();
    $collection->expects($this->any())
      ->method('all')
      ->will($this->returnValue(array('node.view' => array())));
    $route_provider = $this->getMock('Drupal\Core\Routing\RouteProviderInterface');
    $route_provider->expects($this->any())
      ->method('getRoutesByPattern')
      ->will($this->returnValue($collection));
    \Drupal::getContainer()->set('router.route_provider', $route_provider);

    // Stub for t().
    $string_translation = $this->getMock('Drupal\Core\StringTranslation\TranslationInterface');
    $string_translation->expects($this->any())
      ->method('translate')
      ->will($this->returnCallback(function($string) {return $string;}));
    \Drupal::getContainer()->set('string_translation', $string_translation);

    // Load the content_translation.module file in order to run the alter hook.
    require_once DRUPAL_ROOT . '/core/modules/content_translation/content_translation.module';
  }

  /**
   * Tests the block admin display local tasks.
   *
   * @dataProvider providerTestBlockAdminDisplay
   */
  public function testBlockAdminDisplay($route, $expected) {
    $this->assertLocalTasks($route, $expected);
  }

  /**
   * Provides a list of routes to test.
   */
  public function providerTestBlockAdminDisplay() {
    return array(
      array('node.view', array(array(
        'content_translation.local_tasks:content_translation.translation_overview_node',
        'node.view',
        'node.page_edit',
        'node.delete_confirm',
        'node.revision_overview',
      ))),
      array('content_translation.translation_overview_node', array(array(
        'content_translation.local_tasks:content_translation.translation_overview_node',
        'node.view',
        'node.page_edit',
        'node.delete_confirm',
        'node.revision_overview',
      ))),
    );
  }

}
