<?php

/**
 * @file
 * Contains \Drupal\config\Tests\ConfigLanguageOverride.
 */

namespace Drupal\config\Tests;

use Drupal\Core\Language\Language;
use Drupal\simpletest\DrupalUnitTestBase;

/**
 * Tests language config override.
 */
class ConfigLanguageOverride extends DrupalUnitTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('config_test', 'user', 'language', 'system', 'field');

  public static function getInfo() {
    return array(
      'name' => 'Language override',
      'description' => 'Confirm that language overrides work',
      'group' => 'Configuration',
    );
  }

  public function setUp() {
    parent::setUp();
    $this->installConfig(array('config_test'));
    \Drupal::configFactory()->setLanguage(language_default());
  }

  /**
   * Tests locale override based on language.
   */
  function testConfigLanguageOverride() {
    $config = \Drupal::config('config_test.system');
    $this->assertIdentical($config->get('foo'), 'en bar');

    language_save(new Language(array(
      'name' => 'French',
      'id' => 'fr',
    )));
    language_save(new Language(array(
      'name' => 'German',
      'id' => 'de',
    )));

    \Drupal::configFactory()->setLanguage(language_load('fr'));
    $config = \Drupal::config('config_test.system');
    $this->assertIdentical($config->get('foo'), 'fr bar');

    \Drupal::configFactory()->setLanguage(language_load('de'));
    $config = \Drupal::config('config_test.system');
    $this->assertIdentical($config->get('foo'), 'de bar');

    // Test overrides of completely new configuration objects. In normal runtime
    // this should only happen for configuration entities as we should not be
    // creating simple configuration objects on the fly.
    $language_config_name = \Drupal::configFactory()->getLanguageConfigName('de', 'config_test.new');
    \Drupal::config($language_config_name)->set('language', 'override')->save();
    \Drupal::config('config_test.new');
    $config = \Drupal::config('config_test.new');
    $this->assertTrue($config->isNew(), 'The configuration object config_test.new is new');
    $this->assertIdentical($config->get('language'), 'override');
    \Drupal::configFactory()->disableOverrides();
    $config = \Drupal::config('config_test.new');
    $this->assertIdentical($config->get('language'), NULL);
    \Drupal::configFactory()->enableOverrides();

    // Ensure that language configuration overrides can not be overridden.
    global $conf;
    $conf[$language_config_name]['language'] = 'conf cannot override';
    $this->assertIdentical(\Drupal::config($language_config_name)->get('language'), 'override');
  }
}

