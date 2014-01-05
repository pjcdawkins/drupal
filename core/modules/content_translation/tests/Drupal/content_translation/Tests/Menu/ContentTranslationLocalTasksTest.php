<?php

/**
 * @file
 * Contains \Drupal\block\Tests\Menu\BlockLocalTasksTest.
 */

namespace Drupal\content_translation\Tests\Menu;

use Drupal\Tests\Core\Menu\LocalTaskIntegrationTest;
use Drupal\content_translation\Plugin\Derivative\ContentTranslationLocalTasks;;

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

    // Entity manager stub for derivative building.
    $entity_manager = $this->getMock('Drupal\Core\Entity\EntityManagerInterface');
    $entity_manager->expects($this->any())
      ->method('getDefinitions')
      ->will($this->returnValue(array(
        'node' => array(
          'translatable' => TRUE,
          'links' => array(
            'canonical' => '/node/{node}',
          ),
        ),
      )));
    \Drupal::getContainer()->set('entity.manager', $entity_manager);

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
   * {@inheritdoc}
   */
  protected function getLocalTaskManager($modules, $route_name, $route_params) {
    $manager = parent::getLocalTaskManager($modules, $route_name, $route_params);

    // Duplicate content_translation_local_tasks_alter()'s code here to avoid
    // having to load the .module file.
    $this->moduleHandler->expects($this->once())
      ->method('alter')
      ->will($this->returnCallback(function ($hook, &$local_tasks) {
          // Alters in tab_root_id onto the content translation local task.
          $derivative = ContentTranslationLocalTasks::create(\Drupal::getContainer(), 'content_translation.local_tasks');
          $derivative->alterLocalTasks($local_tasks);
      }));
    return $manager;
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
