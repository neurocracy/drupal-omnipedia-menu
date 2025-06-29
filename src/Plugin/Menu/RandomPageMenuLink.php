<?php

declare(strict_types=1);

namespace Drupal\omnipedia_menu\Plugin\Menu;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Menu\MenuLinkDefault;
use Drupal\Core\Menu\StaticMenuLinkOverridesInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\omnipedia_date\Plugin\Omnipedia\Date\OmnipediaDateInterface;
use Drupal\omnipedia_date\Service\CurrentDateInterface;
use Drupal\omnipedia_date\Service\DateResolverInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Represents a menu link for the random page route.
 *
 * @see https://drupal.stackexchange.com/questions/235402/how-can-i-add-cache-context-to-custom-menu-link/249342
 *   Describes how to create a menu link that varies by cache context.
 */
class RandomPageMenuLink extends MenuLinkDefault {

  /**
   * {@inheritdoc}
   */
  protected $overrideAllowed = [
    'menu_name' => 1,
    'parent'    => 1,
    'weight'    => 1,
    'expanded'  => 1,
    'enabled'   => 1,
  ];

  /**
   * {@inheritdoc}
   */
  public function __construct(
    array $configuration, string $pluginId, array $pluginDefinition,
    StaticMenuLinkOverridesInterface $staticOverride,
    protected readonly CurrentDateInterface $currentDate,
    protected readonly DateResolverInterface $dateResolver,
  ) {

    parent::__construct(
      $configuration,
      $pluginId,
      $pluginDefinition,
      $staticOverride,
    );

  }

  /**
   * {@inheritdoc}
   */
  public static function create(
    ContainerInterface $container,
    array $configuration, $pluginId, $pluginDefinition,
  ) {

    return new static(
      $configuration,
      $pluginId,
      $pluginDefinition,
      $container->get('menu_link.static.overrides'),
      $container->get('omnipedia_date.current_date'),
      $container->get('omnipedia_date.date_resolver'),
    );

  }

  /**
   * Get the date plug-in instance for this menu item.
   *
   * @return \Drupal\omnipedia_date\Plugin\Omnipedia\Date\OmnipediaDateInterface
   *   A date plug-in instance.
   */
  protected function getDate(): OmnipediaDateInterface {

    return $this->dateResolver->resolve(
      $this->currentDate->get(),
    );

  }

  /**
   * {@inheritdoc}
   */
  public function getRouteParameters() {

    $date = $this->getDate();

    return [
      'year'  => $date->getYear(),
      'month' => $date->getMonth(),
      'day'   => $date->getDay(),
    ];

  }

  /**
   * {@inheritdoc}
   */
  public function isDeletable() {
    return false;
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheContexts() {

    return Cache::mergeContexts(
      parent::getCacheContexts(),
      // This menu link varies by the Omnipedia date.
      ['omnipedia_dates'],
    );

  }

  /**
   * {@inheritdoc}
   */
  public function getCacheTags() {

    $date = $this->getDate();

    return Cache::mergeTags(
      parent::getCacheTags(),
      [
        // Add the current date as a tag, so that this menu link is rebuilt
        // if/ when the given date tag is invalidated.
        'omnipedia_dates:' . $date->format('storage'),
      ],
    );

  }

}
