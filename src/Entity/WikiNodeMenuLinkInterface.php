<?php

declare(strict_types=1);

namespace Drupal\omnipedia_menu\Entity;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\Core\Entity\EntityPublishedInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Provides an interface defining the 'omnipedia_wiki_node_menu_link' entity.
 *
 * @see \Drupal\menu_link_content\MenuLinkContentInterface
 *   A lot of this has been adapted from the Drupal core menu link content
 *   entity class.
 */
interface WikiNodeMenuLinkInterface extends ContentEntityInterface, EntityChangedInterface, EntityOwnerInterface, EntityPublishedInterface {

  /**
   * Flags this instance as being wrapped in a menu link plugin instance.
   */
  public function setInsidePlugin(): void;

  /**
   * Gets the wiki node title associated with this menu link.
   *
   * @return string
   *   The title of the wiki node.
   */
  public function getWikiNodeTitle(): string;

  /**
   * Get the fallback behaviour for this menu link.
   *
   * This indicates how the menu link should behave if a wiki node is not found
   * for the user's current date.
   *
   * @return string
   *   Can be one of:
   *
   *   - 'hide': indicates that this menu link should be hidden.
   *
   *   - 'last': indicates that this menu link should link to the last available
   *     date.
   *
   *   - 'nolink': indicates that the link title should be output as unlinked
   *     text.
   */
  public function getFallbackBehaviour(): string;

  /**
   * Gets the title of the menu link.
   *
   * @return string
   *   The title of the link.
   */
  public function getTitle(): string;

  /**
   * Gets the menu name of the custom menu link.
   *
   * @return string
   *   The menu ID.
   */
  public function getMenuName(): string;

  /**
   * Gets the description of the menu link for the UI.
   *
   * @return string|null
   *   The description to use on admin pages or as a title attribute, or null if
   *   one wasn't set.
   */
  public function getDescription(): ?string;

  /**
   * Gets the menu plug-in ID associated with this entity.
   *
   * @return string
   *   The plug-in ID.
   */
  public function getPluginId(): string;

  /**
   * Returns whether the menu link is marked as enabled.
   *
   * @return bool
   *   True if is enabled, false otherwise.
   */
  public function isEnabled(): bool;

  /**
   * Returns whether the menu link is marked as always expanded.
   *
   * @return bool
   *   True for expanded, false otherwise.
   */
  public function isExpanded(): bool;

  /**
   * Gets the plug-in ID of the parent menu link.
   *
   * @return string
   *   A plug-in ID, or empty string if this link is at the top level.
   */
  public function getParentId(): string;

  /**
   * Returns the weight of the menu link content entity.
   *
   * @return int
   *   A weight for use when ordering links.
   */
  public function getWeight(): int;

  /**
   * Builds up the menu link plug-in definition for this entity.
   *
   * @return array
   *   The plug-in definition corresponding to this entity.
   *
   * @see \Drupal\Core\Menu\MenuLinkTree::$defaults
   */
  public function getPluginDefinition(): array;

}
