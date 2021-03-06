<?php

/**
 * @file
 * Contains \Drupal\views\Tests\ViewsHooksTest.
 */

namespace Drupal\views\Tests;

/**
 * Tests that views hooks are registered when defined in $module.views.inc.
 *
 * @see views_hook_info().
 * @see field_hook_info().
 */
class ViewsHooksTest extends ViewUnitTestBase {

  /**
   * Views used by this test.
   *
   * @var array
   */
  public static $testViews = array('test_view');

  /**
   * An array of available views hooks to test.
   *
   * @var array
   */
  protected static $hooks = array (
    'views_data' => 'all',
    'views_data_alter' => 'alter',
    'views_query_substitutions' => 'view',
    'views_form_substitutions' => 'view',
    'views_analyze' => 'view',
    'views_pre_view' => 'view',
    'views_pre_build' => 'view',
    'views_post_build' => 'view',
    'views_pre_execute' => 'view',
    'views_post_execute' => 'view',
    'views_pre_render' => 'view',
    'views_post_render' => 'view',
    'views_query_alter'  => 'view',
    'views_invalidate_cache' => 'all',
  );

  /**
   * The module handler to use for invoking hooks.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  public static function getInfo() {
    return array(
      'name' => 'Views hooks',
      'description' => 'Tests that views hooks are registered when defined in $module.views.inc.',
      'group' => 'Views',
    );
  }

  protected function setUp() {
    parent::setUp();

    $this->moduleHandler = $this->container->get('module_handler');
  }

  /**
   * Tests the hooks.
   */
  public function testHooks() {
    $view = views_get_view('test_view');

    // Test each hook is found in the implementations array and is invoked.
    foreach (static::$hooks as $hook => $type) {
      $this->assertTrue($this->moduleHandler->implementsHook('views_test_data', $hook), format_string('The hook @hook was registered.', array('@hook' => $hook)));

      switch ($type) {
        case 'view':
          $this->moduleHandler->invoke('views_test_data', $hook, array($view));
          break;

        case 'alter':
          $data = array();
          $this->moduleHandler->invoke('views_test_data', $hook, array($data));
          break;

        default:
          $this->moduleHandler->invoke('views_test_data', $hook);
      }

      $this->assertTrue($this->container->get('state')->get('views_hook_test_' . $hook), format_string('The %hook hook was invoked.', array('%hook' => $hook)));
      // Reset the module implementations cache, so we ensure that the
      // .views.inc file is loaded actively.
      $this->moduleHandler->resetImplementations();
    }
  }

}
