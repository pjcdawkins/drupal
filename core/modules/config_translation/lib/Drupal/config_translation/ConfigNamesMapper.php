<?php

/**
 * @file
 * Contains \Drupal\config_translation\ConfigNamesMapper.
 */

namespace Drupal\config_translation;

use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\Language\Language;
use Drupal\Core\Plugin\PluginBase;
use Drupal\Core\Routing\RouteProviderInterface;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\locale\LocaleConfigManager;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Route;

/**
 * Configuration mapper base implementation.
 */
class ConfigNamesMapper extends PluginBase implements ConfigMapperInterface, ContainerFactoryPluginInterface {

  /**
   * The configuration factory.
   *
   * @var \Drupal\Core\Config\ConfigFactory
   */
  protected $configFactory;

  /**
   * The typed configuration manager.
   *
   * @var \Drupal\locale\LocaleConfigManager
   */
  protected $localeConfigManager;

  /**
   * The mapper plugin discovery service.
   *
   * @var \Drupal\config_translation\ConfigMapperManagerInterface
   */
  protected $configMapperManager;

  /**
   * The base route object that the mapper is attached to.
   *
   * @return \Symfony\Component\Routing\Route
   */
  protected $baseRoute;

  /**
   * The language code of the language this mapper, if any.
   *
   * @var string|null
   */
  protected $langcode = NULL;

  /**
   * Constructs a ConfigNamesMapper.
   *
   * @param $plugin_id
   *   The config mapper plugin ID.
   * @param array $plugin_definition
   *   An array of plugin information with the following keys:
   *   - title: The title of the mapper, used for generating page titles.
   *   - base_route_name: The route name of the base route this mapper is
   *     attached to.
   *   - names: (optional) An array of configuration names.
   *   - weight: (optional) The weight of this mapper, used in mapper listings.
   *     Defaults to 20.
   *   - list_controller: (optional) Class name for list controller used to
   *     generate lists of this type of configuration.
   * @param \Drupal\Core\Config\ConfigFactory $config_factory
   *   The configuration factory.
   * @param \Drupal\locale\LocaleConfigManager $locale_config_manager
   *   The locale configuration manager.
   * @param \Drupal\config_translation\ConfigMapperManagerInterface $config_mapper_manager
   *   The mapper plugin discovery service.
   * @param \Drupal\Core\Routing\RouteProviderInterface
   *   The route provider.
   * @param \Drupal\Core\StringTranslation\TranslationInterface $translation_manager
   *   The string translation manager.
   *
   * @throws \Symfony\Component\Routing\Exception\RouteNotFoundException
   *   Throws an exception if the route specified by the 'base_route_name' in
   *   the plugin definition could not be found by the route provider.
   */
  public function __construct($plugin_id, array $plugin_definition, ConfigFactory $config_factory, LocaleConfigManager $locale_config_manager, ConfigMapperManagerInterface $config_mapper_manager, RouteProviderInterface $route_provider, TranslationInterface $translation_manager) {
    $this->pluginId = $plugin_id;
    $this->pluginDefinition = $plugin_definition;

    $this->configFactory = $config_factory;
    $this->localeConfigManager = $locale_config_manager;
    $this->configMapperManager = $config_mapper_manager;

    $this->setTranslationManager($translation_manager);

    $this->baseRoute = $route_provider->getRouteByName($this->getBaseRouteName());
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, array $plugin_definition) {
    // Note that we ignore the plugin $configuration because mappers have
    // nothing to configure in themselves.
    return new static (
      $plugin_id,
      $plugin_definition,
      $container->get('config.factory'),
      $container->get('locale.config.typed'),
      $container->get('plugin.manager.config_translation.mapper'),
      $container->get('router.route_provider'),
      $container->get('string_translation')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getTitle() {
    // A title from a *.config_translation.yml. Should be translated for
    // display in the current page language.
    return $this->t($this->pluginDefinition['title']);
  }

  /**
   * {@inheritdoc}
   */
  public function getBaseRouteName() {
    return $this->pluginDefinition['base_route_name'];
  }

  /**
   * {@inheritdoc}
   */
  public function getBaseRouteParameters() {
    return array();
  }

  /**
   * {@inheritdoc}
   */
  public function getBaseRoute() {
    return $this->baseRoute;
  }

  /**
   * {@inheritdoc}
   */
  public function getBasePath() {
    return $this->getPathFromRoute($this->getBaseRoute(), $this->getBaseRouteParameters());
  }

  /**
   * {@inheritdoc}
   */
  public function getOverviewRouteName() {
    return 'config_translation.item.overview.' . $this->getBaseRouteName();
  }

  /**
   * {@inheritdoc}
   */
  public function getOverviewRouteParameters() {
    return $this->getBaseRouteParameters();
  }

  /**
   * {@inheritdoc}
   */
  public function getOverviewRoute() {
    return new Route(
      $this->getBaseRoute()->getPath() . '/translate',
      array(
        '_controller' => '\Drupal\config_translation\Controller\ConfigTranslationController::itemPage',
        'plugin_id' => $this->getPluginId(),
      ),
      array('_config_translation_overview_access' => 'TRUE')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getOverviewPath() {
    return $this->getPathFromRoute($this->getOverviewRoute(), $this->getOverviewRouteParameters());
  }

  /**
   * {@inheritdoc}
   */
  public function getAddRouteName() {
    return 'config_translation.item.add.' . $this->getBaseRouteName();
  }

  /**
   * {@inheritdoc}
   */
  public function getAddRouteParameters() {
    // If sub-classes provide route parameters in getBaseRouteParameters(), they
    // probably also want to provide those for the add, edit, and delete forms.
    $parameters = $this->getBaseRouteParameters();
    $parameters['langcode'] = $this->langcode;
    return $parameters;
  }

  /**
   * {@inheritdoc}
   */
  public function getAddRoute() {
    return new Route(
      $this->getBaseRoute()->getPath() . '/translate/{langcode}/add',
      array(
        '_form' => '\Drupal\config_translation\Form\ConfigTranslationAddForm',
        'plugin_id' => $this->getPluginId(),
      ),
      array('_config_translation_form_access' => 'TRUE')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getEditRouteName() {
    return 'config_translation.item.edit.' . $this->getBaseRouteName();
  }

  /**
   * {@inheritdoc}
   */
  public function getEditRouteParameters() {
    return $this->getAddRouteParameters();
  }

  /**
   * {@inheritdoc}
   */
  public function getEditRoute() {
    return new Route(
      $this->getBaseRoute()->getPath() . '/translate/{langcode}/edit',
      array(
        '_form' => '\Drupal\config_translation\Form\ConfigTranslationEditForm',
        'plugin_id' => $this->getPluginId(),
      ),
      array('_config_translation_form_access' => 'TRUE')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getDeleteRouteName() {
    return 'config_translation.item.delete.' . $this->getBaseRouteName();
  }

  /**
   * {@inheritdoc}
   */
  public function getDeleteRouteParameters() {
    return $this->getAddRouteParameters();
  }

  /**
   * {@inheritdoc}
   */
  public function getDeleteRoute() {
    return new Route(
      $this->getBaseRoute()->getPath() . '/translate/{langcode}/delete',
      array(
        '_form' => '\Drupal\config_translation\Form\ConfigTranslationDeleteForm',
        'plugin_id' => $this->getPluginId(),
      ),
      array('_config_translation_form_access' => 'TRUE')
    );
  }

  /**
   * Gets the path for a certain route, given a set of route parameters.
   *
   * @param \Symfony\Component\Routing\Route $route
   *   The route object.
   * @param array $parameters
   *   An array of route parameters.
   *
   * @return string
   *   Processed path with placeholders replaced.
   */
  public function getPathFromRoute(Route $route, array $parameters) {
    $path = $route->getPath();
    foreach ($parameters as $key => $value) {
      $path = str_replace('{' . $key . '}', $value, $path);
    }
    return $path;
  }

  /**
   * {@inheritdoc}
   */
  public function getConfigNames() {
    return $this->pluginDefinition['names'];
  }

  /**
   * {@inheritdoc}
   */
  public function addConfigName($name) {
    $this->pluginDefinition['names'][] = $name;
  }

  /**
   * {@inheritdoc}
   */
  public function getWeight() {
    return $this->pluginDefinition['weight'];
  }

  /**
   * {@inheritdoc}
   */
  public function populateFromRequest(Request $request) {
    if ($request->attributes->has('langcode')) {
      $this->langcode = $request->attributes->get('langcode');
    }
    else {
      $this->langcode = NULL;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getTypeLabel() {
    return $this->getTitle();
  }

  /**
   * {@inheritdoc}
   */
  public function getLangcode() {
    $config_factory = $this->configFactory;
    $langcodes = array_map(function($name) use ($config_factory) {
      // Default to English if no language code was provided in the file.
      // Although it is a best practice to include a language code, if the
      // developer did not think about a multilingual use-case, we fall back
      // on assuming the file is English.
      return $config_factory->get($name)->get('langcode') ?: 'en';
    }, $this->getConfigNames());

    if (count(array_unique($langcodes)) > 1) {
      throw new \RuntimeException('A config mapper can only contain configuration for a single language.');
    }

    return reset($langcodes);
  }

  /**
   * {@inheritdoc}
   */
  public function getLanguageWithFallback() {
    $langcode = $this->getLangcode();
    $language = language_load($langcode);
    // If the language of the file is English but English is not a configured
    // language on the site, create a mock language object to represent this
    // language run-time. In this case, the title of the language is
    // 'Built-in English' because we assume such configuration is shipped with
    // core and the modules and not custom created. (In the later case an
    // English language configured on the site is assumed.)
    if (empty($language) && $langcode == 'en') {
      $language = new Language(array('id' => 'en', 'name' => $this->t('Built-in English')));
    }
    return $language;
  }

  /**
   * {@inheritdoc}
   */
  public function getConfigData() {
    $config_data = array();
    foreach ($this->getConfigNames() as $name) {
      $config_data[$name] = $this->configFactory->get($name)->get();
    }
    return $config_data;
  }

  /**
   * {@inheritdoc}
   */
  public function hasSchema() {
    foreach ($this->getConfigNames() as $name) {
      if (!$this->localeConfigManager->hasConfigSchema($name)) {
        return FALSE;
      }
    }
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function hasTranslatable() {
    foreach ($this->getConfigNames() as $name) {
      if (!$this->configMapperManager->hasTranslatable($name)) {
        return FALSE;
      }
    }
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function hasTranslation(Language $language) {
    foreach ($this->getConfigNames() as $name) {
      if ($this->localeConfigManager->hasTranslation($name, $language)) {
        return TRUE;
      }
    }
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function getTypeName() {
    return $this->t('Settings');
  }

  /**
   * {@inheritdoc}
   */
  public function getOperations() {
    return array(
      'translate' => array(
        'title' => $this->t('Translate'),
        'href' => $this->getOverviewPath(),
      ),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getContextualLinkGroup() {
    return NULL;
  }

}
