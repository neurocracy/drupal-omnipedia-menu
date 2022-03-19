<?php declare(strict_types=1);

namespace Drupal\omnipedia_menu\Service;

use Drupal\Component\Render\MarkupInterface;
use Drupal\Core\Link;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\Core\Url;
use Drupal\omnipedia_core\Entity\Node;
use Drupal\omnipedia_core\Service\HelpInterface;

/**
 * The Omnipedia media help service.
 */
class Help implements HelpInterface {

  use StringTranslationTrait;

  /**
   * Service constructor; saves dependencies.
   *
   * @param \Drupal\Core\StringTranslation\TranslationInterface $stringTranslation
   *   The Drupal string translation service.
   */
  public function __construct(TranslationInterface $stringTranslation) {
    $this->stringTranslation = $stringTranslation;
  }

  /**
   * {@inheritdoc}
   */
  public function help(
    string $routeName, RouteMatchInterface $routeMatch
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
