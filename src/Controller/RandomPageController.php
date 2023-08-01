<?php

declare(strict_types=1);

namespace Drupal\omnipedia_menu\Controller;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Access\AccessResultInterface;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Url;
use Drupal\omnipedia_core\Service\WikiNodeAccessInterface;
use Drupal\omnipedia_core\Service\WikiNodeMainPageInterface;
use Drupal\omnipedia_core\Service\WikiNodeResolverInterface;
use Drupal\omnipedia_core\Service\WikiNodeTrackerInterface;
use Drupal\omnipedia_core\Service\WikiNodeViewedInterface;
use Drupal\omnipedia_date\Service\TimelineInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Controller for the 'omnipedia_menu.random_page' route.
 */
class RandomPageController implements ContainerInjectionInterface {

  /**
   * The Drupal entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected EntityTypeManagerInterface $entityTypeManager;

  /**
   * The Omnipedia timeline service.
   *
   * @var \Drupal\omnipedia_date\Service\TimelineInterface
   */
  protected TimelineInterface $timeline;

  /**
   * The Omnipedia wiki node main page service.
   *
   * @var \Drupal\omnipedia_core\Service\WikiNodeMainPageInterface
   */
  protected WikiNodeMainPageInterface $wikiNodeMainPage;

  /**
   * The Omnipedia wiki node access service.
   *
   * @var \Drupal\omnipedia_core\Service\WikiNodeAccessInterface
   */
  protected WikiNodeAccessInterface $wikiNodeAccess;

  /**
   * The Omnipedia wiki node resolver service.
   *
   * @var \Drupal\omnipedia_core\Service\WikiNodeResolverInterface
   */
  protected WikiNodeResolverInterface $wikiNodeResolver;

  /**
   * The Omnipedia wiki node tracker service.
   *
   * @var \Drupal\omnipedia_core\Service\WikiNodeTrackerInterface
   */
  protected WikiNodeTrackerInterface $wikiNodeTracker;

  /**
   * The Omnipedia wiki node viewed service.
   *
   * @var \Drupal\omnipedia_core\Service\WikiNodeViewedInterface
   */
  protected WikiNodeViewedInterface $wikiNodeViewed;

  /**
   * Controller constructor; saves dependencies.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The Drupal entity type manager.
   *
   * @param \Drupal\omnipedia_date\Service\TimelineInterface $timeline
   *   The Omnipedia timeline service.
   *
   * @param \Drupal\omnipedia_core\Service\WikiNodeAccessInterface $wikiNodeAccess
   *   The Omnipedia wiki node access service.
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
    EntityTypeManagerInterface  $entityTypeManager,
    TimelineInterface           $timeline,
    WikiNodeAccessInterface     $wikiNodeAccess,
    WikiNodeMainPageInterface   $wikiNodeMainPage,
    WikiNodeResolverInterface   $wikiNodeResolver,
    WikiNodeTrackerInterface    $wikiNodeTracker,
    WikiNodeViewedInterface     $wikiNodeViewed
  ) {
    $this->entityTypeManager  = $entityTypeManager;
    $this->timeline           = $timeline;
    $this->wikiNodeAccess     = $wikiNodeAccess;
    $this->wikiNodeMainPage   = $wikiNodeMainPage;
    $this->wikiNodeResolver   = $wikiNodeResolver;
    $this->wikiNodeTracker    = $wikiNodeTracker;
    $this->wikiNodeViewed     = $wikiNodeViewed;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('omnipedia.timeline'),
      $container->get('omnipedia.wiki_node_access'),
      $container->get('omnipedia.wiki_node_main_page'),
      $container->get('omnipedia.wiki_node_resolver'),
      $container->get('omnipedia.wiki_node_tracker'),
      $container->get('omnipedia.wiki_node_viewed')
    );
  }

  /**
   * Checks access for the route.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   Run access checks for this account.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The access result. Access is granted if $account can access at least one
   *   wiki node.
   *
   * @todo Can/should we vary this per wiki date?
   */
  public function access(AccountInterface $account): AccessResultInterface {

    return AccessResult::allowedIf(
      $this->wikiNodeAccess->canUserAccessAnyWikiNode($account)
    );

  }

  /**
   * Redirect to a random wiki node with the current date.
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   *   A redirect response object.
   *
   * @see https://www.php.net/manual/en/function.shuffle.php
   *   Can we use the built-in PHP \shuffle() function to create a playlist of
   *   wiki nodes ahead of time rather than the current method of randomization
   *   at time of invoking this controller?
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

    // Array of all node IDs (nids) for the current date that the current user
    // has access to, including the main page and recently viewed nodes; the
    // node entity query applies access checks by default for us. Note that
    // \array_values() is needed to ensure the keys are integers and not
    // strings.
    /** @var array */
    $currentDateNids = \array_values(($this->entityTypeManager->getStorage(
      'node'
    )->getQuery())
      ->condition('nid', $nodeData['dates'][$currentDate], 'IN')
      ->accessCheck(true)
      ->execute()
    );

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

    // We have to make a copy of the array as \shuffle() modifies the array you
    // pass it rather than returning a shuffled copy.
    $shuffled = $nids;

    \shuffle($shuffled);

    return new RedirectResponse(Url::fromRoute('entity.node.canonical', [
      // Return the first element from the shuffled list of available node IDs
      // (nids).
      'node' => $shuffled[0],
    ])->toString(), 302);

  }

}
