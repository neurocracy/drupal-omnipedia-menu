<?php

declare(strict_types=1);

namespace Drupal\omnipedia_menu\Plugin\Deriver;

use Drupal\Component\Plugin\Derivative\DeriverBase;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Plugin\Discovery\ContainerDeriverInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Omnipedia wiki node menu link plug-in deriver.
 */
class WikiNodeMenuLinkDeriver extends DeriverBase implements ContainerDeriverInterface {

  /**
   * The WikiNodeMenuLink entity storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected EntityStorageInterface $wikiNodeMenuLinkStorage;

  /**
   * Constructs this deriver; saves dependencies.
   *
   * @param string $basePluginId
   *
   * @param \Drupal\Core\Entity\EntityStorageInterface $wikiNodeMenuLinkStorage
   *   The WikiNodeMenuLink entity storage.
   */
  public function __construct(
    string $basePluginId,
    EntityStorageInterface $wikiNodeMenuLinkStorage
  ) {
    $this->wikiNodeMenuLinkStorage = $wikiNodeMenuLinkStorage;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, $basePluginId) {
    return new static(
      $basePluginId,
      $container->get('entity_type.manager')
        ->getStorage('omnipedia_wiki_node_menu_link')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getDerivativeDefinitions($basePluginDefinition) {

    /** @var array */
    $entityIds = $this->wikiNodeMenuLinkStorage->getQuery()->execute();

    /** @var array[] */
    $defininitions = [];

    /** @var \Drupal\omnipedia_menu\Entity\WikiNodeMenuLinkInterface[] */
    $entities = $this->wikiNodeMenuLinkStorage->loadMultiple($entityIds);

    /** @var \Drupal\omnipedia_menu\Entity\WikiNodeMenuLinkInterface $entity */
    foreach ($entities as $entity) {
      $defininitions[$entity->uuid()] = $entity->getPluginDefinition();
    }

    return $defininitions;

  }

}
