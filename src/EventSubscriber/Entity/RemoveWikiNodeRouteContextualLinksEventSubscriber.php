<?php

declare(strict_types=1);

namespace Drupal\omnipedia_menu\EventSubscriber\Entity;

use Drupal\Core\Routing\StackedRouteMatchInterface;
use Drupal\core_event_dispatcher\Event\Entity\EntityViewAlterEvent;
use Drupal\hook_event_dispatcher\HookEventDispatcherInterface;
use Drupal\omnipedia_core\Service\WikiNodeResolverInterface;
use Drupal\omnipedia_core\Service\WikiNodeRouteInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Event subscriber to remove contextual links on wiki node view routes.
 */
class RemoveWikiNodeRouteContextualLinksEventSubscriber implements EventSubscriberInterface {

  /**
   * The Drupal current route match service.
   *
   * @var \Drupal\Core\Routing\StackedRouteMatchInterface
   */
  protected $currentRouteMatch;

  /**
   * The Omnipedia wiki node resolver service.
   *
   * @var \Drupal\omnipedia_core\Service\WikiNodeResolverInterface
   */
  protected $wikiNodeResolver;

  /**
   * The Omnipedia wiki node route service.
   *
   * @var \Drupal\omnipedia_core\Service\WikiNodeRouteInterface
   */
  protected $wikiNodeRoute;

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
    StackedRouteMatchInterface  $currentRouteMatch,
    WikiNodeResolverInterface   $wikiNodeResolver,
    WikiNodeRouteInterface      $wikiNodeRoute
  ) {
    $this->currentRouteMatch  = $currentRouteMatch;
    $this->wikiNodeResolver   = $wikiNodeResolver;
    $this->wikiNodeRoute      = $wikiNodeRoute;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array {
    return [
      HookEventDispatcherInterface::ENTITY_VIEW_ALTER =>
        'removeWikiNodeContextualLinks',
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
  public function removeWikiNodeContextualLinks(EntityViewAlterEvent $event) {

    /** @var \Drupal\Core\Entity\EntityInterface */
    $entity = $event->getEntity();

    if (
      !$this->wikiNodeResolver->isWikiNode($entity) ||
      !$this->wikiNodeRoute->isWikiNodeViewRouteName(
        $this->currentRouteMatch->getRouteName()
      )
    ) {
      return;
    }

    $build = &$event->getBuild();

    unset($build['#contextual_links']['node']);

  }

}
