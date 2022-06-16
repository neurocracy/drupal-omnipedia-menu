<?php

declare(strict_types=1);

namespace Drupal\omnipedia_menu\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Access\AccessManagerInterface;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityHandlerInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Session\AccountInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines the access control handler for wiki node menu link entity type.
 *
 * @see \Drupal\menu_link_content\MenuLinkContentAccessControlHandler
 *   Adapted from the Drupal core 'menu_link_content' entity access control
 *   handler.
 */
class WikiNodeMenuLinkAccessControlHandler extends EntityAccessControlHandler implements EntityHandlerInterface {

  /**
   * The Drupal access manager.
   *
   * @var \Drupal\Core\Access\AccessManagerInterface
   */
  protected AccessManagerInterface $accessManager;

  /**
   * Creates a new WikiNodeMenuLinkAccessControlHandler.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entityType
   *   The entity type definition.
   * @param \Drupal\Core\Access\AccessManagerInterface $accessManager
   *   The access manager to check routes by name.
   */
  public function __construct(
    EntityTypeInterface     $entityType,
    AccessManagerInterface  $accessManager
  ) {
    parent::__construct($entityType);

    $this->accessManager = $accessManager;
  }

  /**
   * {@inheritdoc}
   */
  public static function createInstance(
    ContainerInterface $container, EntityTypeInterface $entityType
  ) {
    return new static($entityType, $container->get('access_manager'));
  }

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(
    EntityInterface $entity, $operation, AccountInterface $account
  ) {

    switch ($operation) {
      case 'view':

        // There is no direct viewing of a menu link, but still for purposes of
        // content_translation we need a generic way to check access.
        return AccessResult::allowedIfHasPermission(
          $account, 'administer menu'
        );

      case 'update':

        if (!$account->hasPermission('administer menu')) {

          return AccessResult::neutral(
            "The 'administer menu' permission is required."
          )->cachePerPermissions();

        } else {
          // Assume that access is allowed.
          $access = AccessResult::allowed()->cachePerPermissions()
            ->addCacheableDependency($entity);

          return $access;
        }

      case 'delete':
        return AccessResult::allowedIfHasPermission($account, 'administer menu')
          ->andIf(
            AccessResult::allowedIf(!$entity->isNew())
            ->addCacheableDependency($entity)
          );

      default:
        return parent::checkAccess($entity, $operation, $account);

    }

  }

}
