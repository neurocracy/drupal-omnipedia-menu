<?php

declare(strict_types=1);

namespace Drupal\omnipedia_menu\EventSubscriber\Entity;

use Drupal\Core\Routing\StackedRouteMatchInterface;
use Drupal\core_event_dispatcher\EntityHookEvents;
use Drupal\core_event_dispatcher\Event\Entity\EntityViewAlterEvent;
use Drupal\omnipedia_core\Service\WikiNodeResolverInterface;
use Drupal\omnipedia_core\Service\WikiNodeRouteInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Event subscriber to remove contextual links on wiki node view routes.
 */
class RemoveWikiNodeRouteContextualLinksEventSubscriber implements EventSubscriberInterface {

  /**
   * Event subscriber constructor; saves dependencies.
   *
   * @param \Drupal\Core\Routing\StackedRouteMatchInterface $currentRouteMatch
   *   The Drupal current route match service.
   *
   * @param \Drupal\omnipedia_core\Service\WikiNodeResolverInterface $wikiNodeResolver
   *   The Omnipedia wiki node resolver service.
   *
   * @param \Drupal\omnipedia_core\Service\WikiNodeRouteInterface $wikiNodeRoute
   *   The Omnipedia wiki node route service.
   */
  public function __construct(
    protected readonly StackedRouteMatchInterface $currentRouteMatch,
    protected readonly WikiNodeResolverInterface  $wikiNodeResolver,
    protected readonly WikiNodeRouteInterface     $wikiNodeRoute,
  ) {}

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array {
    return [
      EntityHookEvents::ENTITY_VIEW_ALTER => 'removeWikiNodeContextualLinks',
    ];
  }

  /**
   * Remove wiki node contextual links on their view routes.
   *
   * Since we have the local tasks block visible on node view routes, these
   * contextual links are redundant and can get in the way with regards to
   * embedded media, which have their own contextual links.
   *
   * @param \Drupal\core_event_dispatcher\Event\Entity\EntityViewAlterEvent $event
   *   The event object.
   */
  public function removeWikiNodeContextualLinks(
    EntityViewAlterEvent $event,
  ): void {

    /** @var \Drupal\Core\Entity\EntityInterface */
    $entity = $event->getEntity();

    if (
      !$this->wikiNodeResolver->isWikiNode($entity) ||
      !$this->wikiNodeRoute->isWikiNodeViewRouteName(
        $this->currentRouteMatch->getRouteName(),
      )
    ) {
      return;
    }

    $build = &$event->getBuild();

    unset($build['#contextual_links']['node']);

  }

}
