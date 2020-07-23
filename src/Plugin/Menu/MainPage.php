<?php

namespace Drupal\omnipedia_menu\Plugin\Menu;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Menu\MenuLinkDefault;
use Drupal\Core\Menu\StaticMenuLinkOverridesInterface;
use Drupal\node\NodeInterface;
use Drupal\omnipedia_core\Service\TimelineInterface;
use Drupal\omnipedia_core\Service\WikiNodeMainPageInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Represents a menu link for the current day's main page.
 *
 * @see https://drupal.stackexchange.com/questions/235402/how-can-i-add-cache-context-to-custom-menu-link/249342
 *   Describes how to create a menu link that varies by cache context.
 */
class MainPage extends MenuLinkDefault {

  /**
   * The Omnipedia timeline service.
   *
   * @var \Drupal\omnipedia_core\Service\TimelineInterface
   */
  protected $timeline;

  /**
   * The Omnipedia wiki node main page service.
   *
   * @var \Drupal\omnipedia_core\Service\WikiNodeMainPageInterface
   */
  protected $wikiNodeMainPage;

  /**
   * {@inheritdoc}
   *
   * @param \Drupal\omnipedia_core\Service\TimelineInterface $timeline
   *   The Omnipedia timeline service.
   *
   * @param \Drupal\omnipedia_core\Service\WikiNodeMainPageInterface $wikiNodeMainPage
   *   The Omnipedia wiki node main page service.
   */
  public function __construct(
    array $configuration,
    $pluginID,
    $pluginDefinition,
    StaticMenuLinkOverridesInterface $staticOverride,
    TimelineInterface $timeline,
    WikiNodeMainPageInterface $wikiNodeMainPage
  ) {
    parent::__construct(
      $configuration,
      $pluginID,
      $pluginDefinition,
      $staticOverride
    );

    $this->timeline         = $timeline;
    $this->wikiNodeMainPage = $wikiNodeMainPage;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(
    ContainerInterface $container,
    array $configuration,
    $pluginID,
    $pluginDefinition
  ) {
    return new static(
      $configuration,
      $pluginID,
      $pluginDefinition,
      $container->get('menu_link.static.overrides'),
      $container->get('omnipedia.timeline'),
      $container->get('omnipedia.wiki_node_main_page')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getRouteName() {
    return $this->wikiNodeMainPage->getMainPageRouteName();
  }

  /**
   * {@inheritdoc}
   */
  public function getRouteParameters() {
    return $this->wikiNodeMainPage->getMainPageRouteParameters(
      $this->timeline->getDateFormatted('current', 'storage')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheContexts() {
    return Cache::mergeContexts(
      parent::getCacheContexts(),
      // This menu link varies by the Omnipedia date.
      ['omnipedia_dates']
    );
  }

  /**
   * {@inheritdoc}
   *
   * @todo Should this also add node:N tags for the main page node(s) for the
   *   default front page config?
   */
  public function getCacheTags() {
    return Cache::mergeTags(
      parent::getCacheTags(),
      [
        // Add the current date as a tag, so that this menu link is rebuilt if/
        // when the given date tag is invalidated.
        'omnipedia_dates:' .
          $this->timeline->getDateFormatted('current', 'storage')
      ]
    );
  }

}
