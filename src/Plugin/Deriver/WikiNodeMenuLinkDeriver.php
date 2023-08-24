<?php

declare(strict_types=1);

namespace Drupal\omnipedia_menu\Plugin\Deriver;

use Drupal\Component\Plugin\Derivative\DeriverBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\Discovery\ContainerDeriverInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Omnipedia wiki node menu link plug-in deriver.
 */
class WikiNodeMenuLinkDeriver extends DeriverBase implements ContainerDeriverInterface {

  /**
   * Constructs this deriver; saves dependencies.
   *
   * @param string $basePluginId
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The Drupal entity type manager.
   */
  public function __construct(
    string $basePluginId,
    protected readonly EntityTypeManagerInterface $entityTypeManager,
  ) {}

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, $basePluginId) {
    return new static(
      $basePluginId,
      $container->get('entity_type.manager'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getDerivativeDefinitions($basePluginDefinition) {

    /** @var \Drupal\Core\Entity\EntityStorageInterface */
    $storage = $this->entityTypeManager->getStorage(
      'omnipedia_wiki_node_menu_link',
    );

    /** @var array */
    $entityIds = $storage->getQuery()
      ->accessCheck(true)
      ->execute();

    /** @var array[] */
    $defininitions = [];

    /** @var \Drupal\omnipedia_menu\Entity\WikiNodeMenuLinkInterface[] */
    $entities = $storage->loadMultiple($entityIds);

    /** @var \Drupal\omnipedia_menu\Entity\WikiNodeMenuLinkInterface $entity */
    foreach ($entities as $entity) {
      $defininitions[$entity->uuid()] = $entity->getPluginDefinition();
    }

    return $defininitions;

  }

}
