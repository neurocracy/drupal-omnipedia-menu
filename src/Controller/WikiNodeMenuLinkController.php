<?php

declare(strict_types=1);

namespace Drupal\omnipedia_menu\Controller;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityFormBuilderInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\omnipedia_core\Entity\Node;
use Drupal\system\MenuInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Route controller for 'omnipedia_wiki_node_menu_link' entities.
 */
class WikiNodeMenuLinkController implements ContainerInjectionInterface {

  /**
   * The Drupal entity form builder.
   *
   * @var \Drupal\Core\Entity\EntityFormBuilderInterface
   */
  protected EntityFormBuilderInterface $entityFormBuilder;

  /**
   * The Drupal entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected EntityTypeManagerInterface $entityTypeManager;

  /**
   * Constructs this controller; saves dependencies.
   *
   * @param \Drupal\Core\Entity\EntityFormBuilderInterface $entityFormBuilder
   *   The Drupal entity form builder.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The Drupal entity type manager.
   */
  public function __construct(
    EntityFormBuilderInterface  $entityFormBuilder,
    EntityTypeManagerInterface  $entityTypeManager,
  ) {
    $this->entityFormBuilder  = $entityFormBuilder;
    $this->entityTypeManager  = $entityTypeManager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity.form_builder'),
      $container->get('entity_type.manager'),
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

    $menuLink = $this->entityTypeManager->getStorage(
      'omnipedia_wiki_node_menu_link'
    )->create([
      'menu_name' => $menu->id(),
    ]);

    return $this->entityFormBuilder->getForm($menuLink);

  }

}
