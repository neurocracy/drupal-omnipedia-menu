<?php

declare(strict_types=1);

namespace Drupal\omnipedia_menu\Hooks;

use Drupal\Component\Render\MarkupInterface;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Link;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Url;
use Drupal\hux\Attribute\Hook;
use Drupal\omnipedia_core\Entity\Node;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Help hook implementations.
 */
class Help implements ContainerInjectionInterface {

  use StringTranslationTrait;

  /**
   * Hook constructor; saves dependencies.
   *
   * @param \Drupal\Core\StringTranslation\TranslationInterface $stringTranslation
   *   The Drupal string translation service.
   */
  public function __construct(protected $stringTranslation) {}

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('string_translation'),
    );
  }

  #[Hook('help')]
  /**
   * Implements \hook_help().
   *
   * @param string $routeName
   *   The current route name.
   *
   * @param \Drupal\Core\Routing\RouteMatchInterface $routeMatch
   *   The current route match.
   *
   * @return \Drupal\Component\Render\MarkupInterface|array|string
   */
  public function help(
    string $routeName, RouteMatchInterface $routeMatch,
  ): MarkupInterface|array|string {

    if (\in_array($routeName, [
      'entity.omnipedia_wiki_node_menu_link.add_link_form',
      'entity.omnipedia_wiki_node_menu_link.canonical',
      'entity.omnipedia_wiki_node_menu_link.edit_form',
    ])) {
      return $this->getWikiNodeMenuLinkFormHelp();
    }

    return [];

  }

  /**
   * Get help content for the wiki node menu link add/edit form route.
   *
   * @return array
   *   A render array.
   */
  protected function getWikiNodeMenuLinkFormHelp(): array {

    /** @var \Drupal\Core\Url */
    $contentAdminUrl = Url::fromRoute(
      'system.admin_content', ['type' => Node::getWikiNodeType()]
    );

    /** @var array */
    $renderArray = [
      '#type' => 'html_tag',
      '#tag'  => 'p',
    ];

    if ($contentAdminUrl->access() === true) {

      /** @var \Drupal\Core\Link */
      $contentAdminLink = new Link(
        $this->t('a wiki page'), $contentAdminUrl
      );

      $renderArray['#value'] = $this->t(
        'This menu link type will always point to the current date\'s revision of @contentAdminLink.',
        [
          // Unfortunately, this needs to be rendered here or it'll cause a
          // fatal error when Drupal tries to pass it to \htmlspecialchars().
          '@contentAdminLink' => $contentAdminLink->toString(),
        ]
      );

    } else {

      $renderArray['#value'] = $this->t(
        'This menu link type will always point to the current date\'s revision of a wiki page.'
      );

    }

    return $renderArray;

  }

}
