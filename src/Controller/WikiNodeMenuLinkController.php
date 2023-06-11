<?php

declare(strict_types=1);

namespace Drupal\omnipedia_menu\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Component\Utility\Xss;
use Drupal\node\NodeStorageInterface;
use Drupal\omnipedia_core\Entity\Node;
use Drupal\omnipedia_core\Service\WikiNodeTrackerInterface;
use Drupal\system\MenuInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Route controller for 'omnipedia_wiki_node_menu_link' entities.
 */
class WikiNodeMenuLinkController extends ControllerBase {

  /**
   * The Drupal node entity storage.
   *
   * @var \Drupal\node\NodeStorageInterface
   */
  protected NodeStorageInterface $nodeStorage;

  /**
   * The Omnipedia wiki node tracker service.
   *
   * @var \Drupal\omnipedia_core\Service\WikiNodeTrackerInterface
   */
  protected WikiNodeTrackerInterface $wikiNodeTracker;

  /**
   * Constructs this controller; saves dependencies.
   *
   * @param \Drupal\node\NodeStorageInterface $nodeStorage
   *   The Drupal node entity storage.
   *
   * @param \Drupal\omnipedia_core\Service\WikiNodeTrackerInterface $wikiNodeTracker
   *   The Omnipedia wiki node tracker service.
   */
  public function __construct(
    NodeStorageInterface      $nodeStorage,
    WikiNodeTrackerInterface  $wikiNodeTracker
  ) {
    $this->nodeStorage      = $nodeStorage;
    $this->wikiNodeTracker  = $wikiNodeTracker;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager')->getStorage('node'),
      $container->get('omnipedia.wiki_node_tracker')
    );
  }

  /**
   * Provides the wiki menu link creation form.
   *
   * @param \Drupal\system\MenuInterface $menu
   *   An entity representing a custom menu.
   *
   * @return array
   *   The wiki menu link creation form.
   *
   * @see \Drupal\menu_link_content\Controller\MenuController
   *   Adapted from the Drupal core menu link content controller method.
   */
  public function addLink(MenuInterface $menu) {

    $menuLink = $this->entityTypeManager()
      ->getStorage('omnipedia_wiki_node_menu_link')
      ->create([
        'menu_name' => $menu->id(),
      ]);

    return $this->entityFormBuilder()->getForm($menuLink);

  }

  /**
   * Process autocomplete input for the 'wiki_node_title' field.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   A Symfony request object containing an autocomplete query.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   A Symfony JSON response containing any matching wiki node titles.
   */
  public function wikiNodeTitleAutocomplete(Request $request): JsonResponse {

    /** @var string */
    $input = Xss::filter($request->query->get('q'));

    /** @var array[] */
    $nodeData = $this->wikiNodeTracker->getTrackedWikiNodeData();

    /** @var string[] */
    $nids = ($this->nodeStorage->getQuery())
      ->condition('type', Node::getWikiNodeType())
      ->condition('title', $input, 'CONTAINS')
      ->accessCheck(true)
      ->execute();

    /** @var string[] */
    $titles = [];

    foreach ($nids as $revisionId => $nid) {

      // Skip any node IDs (nids) not present in the node data and any titles
      // that we already have.
      if (
        !isset($nodeData['titles'][$nid]) ||
        \in_array($nodeData['titles'][$nid], $titles)
      ) {
        continue;
      }

      $titles[] = $nodeData['titles'][$nid];

    }

    /** @var string[] */
    $matches = [];

    foreach ($titles as $title) {
      $matches[] = ['value' => $title, 'label' => $title];
    }

    return new JsonResponse($matches);

  }

}
