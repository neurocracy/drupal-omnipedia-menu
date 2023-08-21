<?php

declare(strict_types=1);

namespace Drupal\omnipedia_menu\EventSubscriber\Menu;

use Drupal\core_event_dispatcher\Event\Menu\MenuLocalTasksAlterEvent;
use Drupal\core_event_dispatcher\MenuHookEvents;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\omnipedia_core\Service\WikiNodeResolverInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Event subscriber to alter wiki node local tasks.
s */
class WikiNodeLocalTaskEventSubscriber implements EventSubscriberInterface {

  use StringTranslationTrait;

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
    protected readonly WikiNodeResolverInterface $wikiNodeResolver,
    protected $stringTranslation,
  ) {}

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array {
    return [
      MenuHookEvents::MENU_LOCAL_TASKS_ALTER => 'onMenuLocalTaskAlter',
    ];
  }

  /**
   * Alter local tasks.
   *
   * This replaces the "View" tab content to either the main page title (if the
   * tab points to a main page wiki node), or "Article" for all other wiki page
   * nodes. Non-wiki node local tasks are left as-is.
   *
   * @param \Drupal\core_event_dispatcher\Event\Menu\MenuLocalTasksAlterEvent $event
   *   Event object.
   *
   * @see \Drupal\omnipedia_core\Service\WikiNodeResolverInterface::resolveWikiNode()
   *   Used to load a wiki node and filter out any non-wiki nodes.
   *
   * @see \Drupal\omnipedia_core\Entity\NodeInterface::isMainPage()
   *   Used to determine if the route parameter is a main page wiki node.
   */
  public function onMenuLocalTaskAlter(MenuLocalTasksAlterEvent $event): void {

    /** @var array Menu local tasks data. */
    $data = &$event->getData();

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
    $node = $this->wikiNodeResolver->resolveWikiNode($routeParameters['node']);

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
