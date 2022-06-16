<?php

declare(strict_types=1);

namespace Drupal\omnipedia_menu\Service;

use Drupal\Core\Cache\RefinableCacheableDependencyInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\omnipedia_core\Service\WikiNodeResolverInterface;

/**
 * Service to alter wiki node local tasks.
 *
 * hook_event_dispatcher doesn't have an event for hook_menu_local_tasks_alter()
 * at the time of writing, so this is implemented as a generic service for now.
 */
class WikiNodeLocalTasksAlter {

  use StringTranslationTrait;

  /**
   * The Omnipedia wiki node resolver service.
   *
   * @var \Drupal\omnipedia_core\Service\WikiNodeResolverInterface
   */
  protected $wikiNodeResolver;

  /**
   * Service constructor; saves dependencies.
   *
   * @param \Drupal\omnipedia_core\Service\WikiNodeResolverInterface $wikiNodeResolver
   *   The Omnipedia wiki node resolver service.
   *
   * @param \Drupal\Core\StringTranslation\TranslationInterface $stringTranslation
   *   The Drupal string translation service.
   */
  public function __construct(
    WikiNodeResolverInterface $wikiNodeResolver,
    TranslationInterface      $stringTranslation
  ) {
    $this->stringTranslation  = $stringTranslation;
    $this->wikiNodeResolver   = $wikiNodeResolver;
  }

  /**
   * Alter local tasks.
   *
   * This replaces the "View" tab content to either the main page title (if the
   * tab points to a main page wiki node), or "Article" for all other wiki page
   * nodes. Non-wiki node local tasks are left as-is.
   *
   * @param array &$data
   *   Associative array of tabs.
   *
   * @param string $routeName
   *   The current route name.
   *
   * @param RefinableCacheableDependencyInterface &$cacheability
   *   The cacheability metadata for the current route's local tasks.
   *
   * @see \Drupal\omnipedia_core\Service\WikiNodeResolverInterface::getWikiNode()
   *   Used to load a wiki node and filter out any non-wiki nodes.
   *
   * @see \Drupal\omnipedia_core\Entity\NodeInterface::isMainPage()
   *   Used to determine if the route parameter is a main page wiki node.
   *
   * @see \hook_menu_local_tasks_alter()
   */
  public function alter(
    array &$data, string $routeName,
    RefinableCacheableDependencyInterface &$cacheability
  ): void {

    // Bail if no 'entity.node.canonical' route is found in the tabs, which is
    // the "View" page for nodes. Note that only the first level is checked as
    // the tab is not usually found in the second level of tabs.
    if (!isset($data['tabs'][0]['entity.node.canonical'])) {
      return;
    }

    /** @var array */
    $nodeLink = &$data['tabs'][0]['entity.node.canonical'];

    /** @var array */
    $routeParameters = $nodeLink['#link']['url']->getRouteParameters();

    /** @var \Drupal\omnipedia_core\Entity\NodeInterface|null */
    $node = $this->wikiNodeResolver->getWikiNode($routeParameters['node']);

    if (\is_null($node)) {
      return;
    }

    // If the wiki page is a main page, use its title as the tab title, to match
    // Wikipedia.
    if ($node->isMainPage()) {
      $nodeLink['#link']['title'] = $node->getTitle();

    // Otherwise, just set the tab title to "Article", matching Wikipedia for all
    // other pages.
    } else {
      $nodeLink['#link']['title'] = $this->t('Article');
    }

  }

}
