<?php

namespace Drupal\omnipedia_menu\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Menu\MenuLinkTreeInterface;
use Drupal\omnipedia_core\Service\TimelineInterface;
use Drupal\omnipedia_core\Service\WikiInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Controller for the 'omnipedia_menu.random_page' route.
 */
class RandomPageController extends ControllerBase {

  /**
   * The Omnipedia timeline service.
   *
   * @var \Drupal\omnipedia_core\Service\TimelineInterface
   */
  protected $timeline;

  /**
   * The Omnipedia wiki service.
   *
   * @var \Drupal\omnipedia_core\Service\WikiInterface
   */
  protected $wiki;

  /**
   * Controller constructor; saves dependencies.
   *
   * @param \Drupal\omnipedia_core\Service\TimelineInterface $timeline
   *   The Omnipedia timeline service.
   *
   * @param \Drupal\omnipedia_core\Service\WikiInterface $wiki
   *   The Omnipedia wiki service.
   */
  public function __construct(
    TimelineInterface $timeline,
    WikiInterface     $wiki
  ) {
    $this->timeline = $timeline;
    $this->wiki     = $wiki;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('omnipedia.timeline'),
      $container->get('omnipedia.wiki')
    );
  }

  /**
   * Redirect to a random wiki node with the current date.
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   *   A redirect response object.
   *
   * @see \Drupal\Core\Controller\ControllerBase::redirect()
   *   Handles the redirect for us.
   *
   * @see \Drupal\omnipedia_core\Service\WikiInterface::getRandomWikiNodeRouteParameters()
   *   Determines the wiki node to view.
   */
  public function view(): RedirectResponse {
    /** @var string */
    $currentDate = $this->timeline->getDateFormatted('current', 'storage');

    return $this->redirect(
      'entity.node.canonical',
      $this->wiki->getRandomWikiNodeRouteParameters($currentDate)
    );
  }

}
