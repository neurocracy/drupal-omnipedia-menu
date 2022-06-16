<?php

declare(strict_types=1);

namespace Drupal\omnipedia_menu\Plugin\Menu;

use Drupal\Component\Plugin\Exception\PluginException;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Menu\MenuLinkDefault;
use Drupal\Core\Menu\StaticMenuLinkOverridesInterface;
use Drupal\omnipedia_core\Entity\NodeInterface;
use Drupal\omnipedia_core\Service\WikiNodeRevisionInterface;
use Drupal\omnipedia_date\Service\TimelineInterface;
use Drupal\omnipedia_menu\Entity\WikiNodeMenuLinkInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Represents a menu link for a wiki node.
 *
 * @see https://drupal.stackexchange.com/questions/235402/how-can-i-add-cache-context-to-custom-menu-link/249342
 *   Describes how to create a menu link that varies by cache context.
 */
class WikiNodeMenuLink extends MenuLinkDefault {

  /**
   * {@inheritdoc}
   */
  protected $overrideAllowed = [
    'menu_name'         => 1,
    'parent'            => 1,
    'weight'            => 1,
    'expanded'          => 1,
    'enabled'           => 1,
    'title'             => 1,
    'description'       => 1,
    'route_name'        => 1,
    'route_parameters'  => 1,
    'url'               => 1,
    'options'           => 1,
  ];

  /**
   * The wiki node menu link entity associated with this plug-in.
   *
   * @var \Drupal\omnipedia_menu\Entity\WikiNodeMenuLinkInterface|null
   */
  protected $entity;

  /**
   * The Drupal entity repository.
   *
   * @var \Drupal\Core\Entity\EntityRepositoryInterface
   */
  protected $entityRepository;

  /**
   * The Drupal language manager.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * The Omnipedia timeline service.
   *
   * @var \Drupal\omnipedia_date\Service\TimelineInterface
   */
  protected $timeline;

  /**
   * The wiki node associated with this menu link, if any.
   *
   * If the wiki node is found, this will contain the node object. If it cannot
   * be found, this will be null. Initially, this will be false to indicate that
   * no attempt has been made to load it yet.
   *
   * @var \Drupal\omnipedia_core\Entity\NodeInterface|null|false
   */
  protected $wikiNode = false;

  /**
   * The WikiNodeMenuLink entity storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $wikiNodeMenuLinkStorage;

  /**
   * The Omnipedia wiki node revision service.
   *
   * @var \Drupal\omnipedia_core\Service\WikiNodeRevisionInterface
   */
  protected $wikiNodeRevision;

  /**
   * {@inheritdoc}
   *
   * @param \Drupal\Core\Entity\EntityRepositoryInterface $entityRepository
   *   The Drupal entity repository.
   *
   * @param \Drupal\Core\Language\LanguageManagerInterface $languageManager
   *   The Drupal language manager.
   *
   * @param \Drupal\omnipedia_date\Service\TimelineInterface $timeline
   *   The Omnipedia timeline service.
   *
   * @param \Drupal\Core\Entity\EntityStorageInterface $wikiNodeMenuLinkStorage
   *   The WikiNodeMenuLink entity storage.
   *
   * @param \Drupal\omnipedia_core\Service\WikiNodeRevisionInterface $wikiNodeRevision
   *   The Omnipedia wiki node revision service.
   */
  public function __construct(
    array $configuration,
    $pluginID,
    $pluginDefinition,
    EntityRepositoryInterface         $entityRepository,
    LanguageManagerInterface          $languageManager,
    StaticMenuLinkOverridesInterface  $staticOverride,
    TimelineInterface                 $timeline,
    EntityStorageInterface            $wikiNodeMenuLinkStorage,
    WikiNodeRevisionInterface         $wikiNodeRevision
  ) {
    parent::__construct(
      $configuration,
      $pluginID,
      $pluginDefinition,
      $staticOverride
    );

    $this->entityRepository         = $entityRepository;
    $this->languageManager          = $languageManager;
    $this->timeline                 = $timeline;
    $this->wikiNodeMenuLinkStorage  = $wikiNodeMenuLinkStorage;
    $this->wikiNodeRevision         = $wikiNodeRevision;
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
      $container->get('entity.repository'),
      $container->get('language_manager'),
      $container->get('menu_link.static.overrides'),
      $container->get('omnipedia.timeline'),
      $container->get('entity_type.manager')
        ->getStorage('omnipedia_wiki_node_menu_link'),
      $container->get('omnipedia.wiki_node_revision')
    );
  }

  /**
   * Get the wiki node menu link entity associated with this plug-in.
   *
   * @return \Drupal\omnipedia_menu\Entity\WikiNodeMenuLinkInterface
   */
  protected function getEntity(): WikiNodeMenuLinkInterface {

    if (\is_object($this->entity)) {
      return $this->entity;
    }

    /** @var \Drupal\omnipedia_menu\Entity\WikiNodeMenuLinkInterface|null */
    $entity = $this->wikiNodeMenuLinkStorage->load(
      $this->getMetaData()['entity_id']
    );

    if (!\is_object($entity)) {
      throw new PluginException(
        'Could not load the wiki node menu link entity (' .
          $this->getMetaData()['entity_id'] .
        ') associated with this plug-in.'
      );
    }

    $this->entity = $this->entityRepository->getTranslationFromContext(
      // Clone the entity object to avoid tampering with the static cache.
      clone $entity
    );

    $this->entity->setInsidePlugin();

    return $this->entity;
  }

  /**
   * Get the wiki node associated with this menu link for the current date.
   *
   * @return \Drupal\omnipedia_core\Entity\NodeInterface|null
   *   A node object, or null if a wiki node is not available.
   */
  protected function getWikiNode(): ?NodeInterface {

    if ($this->wikiNode === false) {
      if (empty($this->getMetaData()['wiki_node_title'])) {
        $this->wikiNode = null;

        return $this->wikiNode;
      }

      $this->wikiNode = $this->wikiNodeRevision->getWikiNodeRevision(
        $this->getMetaData()['wiki_node_title'],
        $this->timeline->getDateFormatted('current', 'storage')
      );
    }

    return $this->wikiNode;

  }

  /**
   * {@inheritdoc}
   */
  public function getRouteName() {

    /** @var \Drupal\omnipedia_core\Entity\NodeInterface|null */
    $node = $this->getWikiNode();

    // Return the <nolink> route name if there isn't a wiki node available to
    // avoid an exception if the 'node' route parameter isn't returned in
    // $this->getRouteParameters().
    if (!\is_object($node)) {
      return '<nolink>';
    }

    return $this->pluginDefinition['route_name'];

  }

  /**
   * {@inheritdoc}
   */
  public function getRouteParameters() {

    /** @var \Drupal\omnipedia_core\Entity\NodeInterface|null */
    $node = $this->getWikiNode();

    // If we can't find a wiki node, return empty parameters.
    if (!\is_object($node)) {
      return [];
    }

    return ['node' => $node->nid->getString()];

  }

  /**
   * {@inheritdoc}
   */
  public function getTitle() {

    // We only need to get the title from the actual entity if it may be a
    // translation based on the current language context. This can only happen
    // if the site is configured to be multilingual.
    if ($this->languageManager->isMultilingual()) {
      return $this->getEntity()->getTitle();
    }

    return $this->pluginDefinition['title'];

  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {

    // We only need to get the description from the actual entity if it may be a
    // translation based on the current language context. This can only happen
    // if the site is configured to be multilingual.
    if ($this->languageManager->isMultilingual()) {
      return $this->getEntity()->getDescription();
    }

    return $this->pluginDefinition['description'];

  }

  /**
   * {@inheritdoc}
   */
  public function getDeleteRoute() {
    return $this->getEntity()->toUrl('delete-form');
  }

  /**
   * {@inheritdoc}
   */
  public function getEditRoute() {
    return $this->getEntity()->toUrl();
  }

  /**
   * {@inheritdoc}
   */
  public function getTranslateRoute() {
    return $this->getEntity()->toUrl('drupal:content-translation-overview');
  }

  /**
   * {@inheritdoc}
   */
  public function isDeletable() {
    return true;
  }

  /**
   * {@inheritdoc}
   */
  public function isResettable() {
    return false;
  }

  /**
   * {@inheritdoc}
   */
  public function isTranslatable() {
    return $this->getEntity()->isTranslatable();
  }

  /**
   * {@inheritdoc}
   */
  public function updateLink(array $newDefinitionValues, $persist) {

    // Filter the list of updates to only those that are allowed.
    $overrides = \array_intersect_key(
      $newDefinitionValues, $this->overrideAllowed
    );

    // Update the definition.
    $this->pluginDefinition = $overrides + $this->getPluginDefinition();

    if ($persist) {

      $entity = $this->getEntity();

      foreach ($overrides as $key => $value) {
        $entity->{$key}->value = $value;
      }

      $entity->save();

    }

    return $this->pluginDefinition;

  }

  /**
   * {@inheritdoc}
   */
  public function deleteLink() {
    $this->getEntity()->delete();
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheContexts() {
    /** @var \Drupal\omnipedia_core\Entity\NodeInterface|null */
    $node = $this->getWikiNode();

    if (\is_object($node)) {
      $nodeCacheContexts = $node->getCacheContexts();

    } else {
      $nodeCacheContexts = [];
    }

    return Cache::mergeContexts(
      $nodeCacheContexts,
      Cache::mergeContexts(
        parent::getCacheContexts(),
        // This menu link varies by the Omnipedia date.
        ['omnipedia_dates']
      )
    );

  }

  /**
   * {@inheritdoc}
   */
  public function getCacheTags() {

    /** @var \Drupal\omnipedia_core\Entity\NodeInterface|null */
    $node = $this->getWikiNode();

    if (\is_object($node)) {
      $nodeCacheTags = $node->getCacheTags();

    } else {
      $nodeCacheTags = [];
    }

    return Cache::mergeTags(
      $nodeCacheTags,
      Cache::mergeTags(
        parent::getCacheTags(),
        [
          // Add the current date as a tag, so that this menu link is rebuilt if/
          // when the given date tag is invalidated.
          'omnipedia_dates:' .
            $this->timeline->getDateFormatted('current', 'storage')
        ]
      )
    );

  }

}
