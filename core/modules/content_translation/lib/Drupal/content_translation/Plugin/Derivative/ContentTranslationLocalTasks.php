<?php

/**
 * @file
 * Contains \Drupal\content_translation\Plugin\Derivative\ContentTranslationLocalTasks.
 */

namespace Drupal\content_translation\Plugin\Derivative;

use Drupal\content_translation\ContentTranslationManagerInterface;
use Drupal\Component\Plugin\Derivative\DerivativeBase;
use Drupal\Core\Plugin\Discovery\ContainerDerivativeInterface;
use Drupal\Core\Routing\RouteProviderInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides dynamic local tasks for content translation.
 */
class ContentTranslationLocalTasks extends DerivativeBase implements ContainerDerivativeInterface {

  /**
   * The base plugin ID
   *
   * @var string
   */
  protected $basePluginId;

  /**
   * The content translation manager.
   *
   * @var \Drupal\content_translation\ContentTranslationManagerInterface
   */
  protected $contentTranslationManager;

  /**
   * The route provider.
   *
   * @var \Drupal\Core\Routing\RouteProviderInterface
   */
  protected $routeProvider;

  /**
   * Constructs a new ContentTranslationLocalTasks.
   *
   * @param string $base_plugin_id
   *   The base plugin ID.
   * @param \Drupal\content_translation\ContentTranslationManagerInterface $content_translation_manager
   *   The content translation manager.
   * @param \Drupal\Core\Routing\RouteProviderInterface $route_provider
   *   The route provider.
   */
  public function __construct($base_plugin_id, ContentTranslationManagerInterface $content_translation_manager, RouteProviderInterface $route_provider) {
    $this->basePluginId = $base_plugin_id;
    $this->contentTranslationManager = $content_translation_manager;
    $this->routeProvider = $route_provider;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, $base_plugin_id) {
    return new static(
      $base_plugin_id,
      $container->get('content_translation.manager'),
      $container->get('router.route_provider')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getDerivativeDefinitions(array $base_plugin_definition) {
    // Create tabs for all possible entity types.
    foreach ($this->contentTranslationManager->getSupportedEntityTypes() as $entity_type => $entity_info) {
      // Find the route name for the translation overview.
      $translation_route_name = "content_translation.translation_overview_$entity_type";
      $translation_tab = $translation_route_name;

      $this->derivatives[$translation_tab] = $base_plugin_definition + array(
        'entity_type' => $entity_type,
        'title' => 'Translate',
        'route_name' => $translation_route_name,
        // @todo this base route is wrong, how can we get the canical route of
        // this entity type?
        'base_route' => $translation_route_name,
      ) + $base_plugin_definition;
    }
    return parent::getDerivativeDefinitions($base_plugin_definition);
  }

}
