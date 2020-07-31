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
   * @see https://www.php.net/manual/en/function.shuffle.php
   *   Can we use the built-in PHP \shuffle() function to create a playlist of
   *   wiki nodes rather than this current method of randomization?
   *
   * @todo Check if this can leak the presence of nodes the user doesn't have
   *   access to. While they can't visit nodes they don't have access to, if
   *   there's ever an issue with permissions, this could leak URLs.
   */
  public function view(): RedirectResponse {
    /** @var string */
    $currentDate = $this->timeline->getDateFormatted('current', 'storage');

    /** @var array */
    $nodeData = $this->wikiNodeTracker->getTrackedWikiNodeData();

    /** @var \Drupal\omnipedia_core\Entity\NodeInterface */
    $currentDateMainPage = $this->wikiNodeMainPage->getMainPage($currentDate);

    /** @var string */
    $currentDateMainPageNid = $currentDateMainPage->nid->getString();

    // Array of all published nids for the current date, including the main page
    // and recently viewed nodes. Note that \array_values() is needed to ensure
    // the keys are integers and not strings.
    /** @var array */
    $currentDateNids = \array_values(\array_filter(
      $nodeData['dates'][$currentDate],
      function($nid) use ($nodeData) {
        // This filters out unpublished nodes.
        return $nodeData['nodes'][$nid]['published'];
      }
    ));

    // If there at least 3 nodes for the current date, remove the main page so
    // that it can't be picked. If there are two nodes, alternating between the
    // main page and the single wiki node is preferable so that a user sees a
    // change when choosing random.
    if (\count($currentDateNids) > 2) {
      \array_splice(
        $currentDateNids,
        \array_search($currentDateMainPageNid, $currentDateNids),
        1
      );
    }

    // Recent wiki nodes for the current date. Note that \array_values() is
    // needed to ensure the keys are integers and not strings.
    /** @var array */
    $currentDateRecentNids = \array_values(\array_filter(
      $this->wikiNodeViewed->getNodes(),
      function($nid) use ($currentDateNids) {
        // This filters out any nodes that aren't of the current date.
        return \in_array($nid, $currentDateNids);
      }
    ));

    // Reduce the recent nodes array to one less than the available nodes.
    $currentDateRecentNids = \array_reverse(\array_slice(
      \array_reverse($currentDateRecentNids),
      0,
      \count($currentDateNids) - 1
    ));

    /** @var array */
    $nids = \array_filter(
      $currentDateNids,
      function($nid) use ($currentDateRecentNids) {
        // This filters out recently viewed wiki nodes.
        return !\in_array($nid, $currentDateRecentNids);
      }
    );

    // If no nids are left at this point, fall back to the main page.
    if (empty($nids)) {
      $nids = [$currentDateMainPageNid];
    }

    return $this->redirect('entity.node.canonical', [
      // Return a random nid from the available nids.
      'node' => $nids[\array_rand($nids)]
    ]);
  }

}
