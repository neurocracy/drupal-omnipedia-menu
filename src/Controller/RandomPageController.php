<?php

namespace Drupal\omnipedia_menu\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Menu\MenuLinkTreeInterface;
use Drupal\omnipedia_core\Service\TimelineInterface;
use Drupal\omnipedia_core\Service\WikiNodeMainPageInterface;
use Drupal\omnipedia_core\Service\WikiNodeResolverInterface;
use Drupal\omnipedia_core\Service\WikiNodeTrackerInterface;
use Drupal\omnipedia_core\Service\WikiNodeViewedInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Controller for the 'omnipedia_menu.random_page' route.
 */
class RandomPageController extends ControllerBase {

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
   * The Omnipedia wiki node resolver service.
   *
   * @var \Drupal\omnipedia_core\Service\WikiNodeResolverInterface
   */
  protected $wikiNodeResolver;

  /**
   * The Omnipedia wiki node tracker service.
   *
   * @var \Drupal\omnipedia_core\Service\WikiNodeTrackerInterface
   */
  protected $wikiNodeTracker;

  /**
   * The Omnipedia wiki node viewed service.
   *
   * @var \Drupal\omnipedia_core\Service\WikiNodeViewedInterface
   */
  protected $wikiNodeViewed;

  /**
   * Controller constructor; saves dependencies.
   *
   * @param \Drupal\omnipedia_core\Service\TimelineInterface $timeline
   *   The Omnipedia timeline service.
   *
   * @param \Drupal\omnipedia_core\Service\WikiNodeMainPageInterface $wikiNodeMainPage
   *   The Omnipedia wiki node main page service.
   *
   * @param \Drupal\omnipedia_core\Service\WikiNodeResolverInterface $wikiNodeResolver
   *   The Omnipedia wiki node resolver service.
   *
   * @param \Drupal\omnipedia_core\Service\WikiNodeTrackerInterface $wikiNodeTracker
   *   The Omnipedia wiki node tracker service.
   *
   * @param \Drupal\omnipedia_core\Service\WikiNodeViewedInterface $wikiNodeViewed
   *   The Omnipedia wiki node viewed service.
   */
  public function __construct(
    TimelineInterface         $timeline,
    WikiNodeMainPageInterface $wikiNodeMainPage,
    WikiNodeResolverInterface $wikiNodeResolver,
    WikiNodeTrackerInterface  $wikiNodeTracker,
    WikiNodeViewedInterface   $wikiNodeViewed
  ) {
    $this->timeline         = $timeline;
    $this->wikiNodeMainPage = $wikiNodeMainPage;
    $this->wikiNodeResolver = $wikiNodeResolver;
    $this->wikiNodeTracker  = $wikiNodeTracker;
    $this->wikiNodeViewed   = $wikiNodeViewed;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('omnipedia.timeline'),
      $container->get('omnipedia.wiki_node_main_page'),
      $container->get('omnipedia.wiki_node_resolver'),
      $container->get('omnipedia.wiki_node_tracker'),
      $container->get('omnipedia.wiki_node_viewed')
    );
  }

  /**
   * Redirect to a random wiki node with the current date.
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   *   A redirect response object.
   *
   * @see \Drupal\Core\Controller\ControllerBase::redirect()
   *   Handles the redirect for us.
   *
   * @todo Check if this can leak the presence of nodes the user doesn't have
   *   access to. While they can't visit nodes they don't have access to, if
   *   there's ever an issue with permissions, this could leak URLs.
   *
   * @todo This should handle cases where no nodes are found as recent.
   */
  public function view(): RedirectResponse {
    /** @var string */
    $currentDate = $this->timeline->getDateFormatted('current', 'storage');

    /** @var array */
    $nodeData = $this->wikiNodeTracker->getTrackedWikiNodeData();

    /** @var array */
    $mainPageNids = $this->wikiNodeResolver
      ->nodeOrTitleToNids($this->wikiNodeMainPage->getMainPage('default'));

    /** @var array */
    $viewedNids = $this->wikiNodeViewed->getNodes();

    /** @var array */
    $nids = \array_filter(
      $nodeData['dates'][$currentDate],
      function($nid) use ($nodeData, $mainPageNids, $viewedNids) {
        // This filters out unpublished nodes, main page nodes, and recently
        // viewed wiki nodes.
        return !(
          !$nodeData['nodes'][$nid]['published'] ||
          \in_array($nid, $mainPageNids) ||
          \in_array($nid, $viewedNids)
        );
      }
    );

    return $this->redirect('entity.node.canonical', [
      // Return a random nid from the available nids.
      'node' => $nids[\array_rand($nids)]
    ]);
  }

}
