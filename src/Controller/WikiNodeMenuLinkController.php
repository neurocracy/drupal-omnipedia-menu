<?php

namespace Drupal\omnipedia_menu\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\system\MenuInterface;

/**
 * Route controller for a form for 'omnipedia_wiki_node_menu_link' entities.
 *
 * @see \Drupal\menu_link_content\Controller\MenuController
 *   Adapted from the Drupal core menu link content controller.
 */
class WikiNodeMenuLinkController extends ControllerBase {

  /**
   * Provides the wiki menu link creation form.
   *
   * @param \Drupal\system\MenuInterface $menu
   *   An entity representing a custom menu.
   *
   * @return array
   *   The wiki menu link creation form.
   */
  public function addLink(MenuInterface $menu) {

    $menuLink = $this->entityTypeManager()
      ->getStorage('omnipedia_wiki_node_menu_link')
      ->create([
        'menu_name' => $menu->id(),
      ]);

    return $this->entityFormBuilder()->getForm($menuLink);
  }

}
