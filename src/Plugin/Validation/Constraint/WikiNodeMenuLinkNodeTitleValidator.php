<?php

declare(strict_types=1);

namespace Drupal\omnipedia_menu\Plugin\Validation\Constraint;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Url;
use Drupal\omnipedia_core\Entity\Node;
use Drupal\omnipedia_core\Service\WikiNodeTrackerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

/**
 * Validates the WikiNodeMenuLinkNodeTitle constraint.
 */
class WikiNodeMenuLinkNodeTitleValidator extends ConstraintValidator implements ContainerInjectionInterface {

  /**
   * Constructs this validator; saves dependencies.
   *
   * @param \Drupal\omnipedia_core\Service\WikiNodeTrackerInterface $wikiNodeTracker
   *   The Omnipedia wiki node tracker service.
   */
  public function __construct(
    protected readonly WikiNodeTrackerInterface $wikiNodeTracker,
  ) {}

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('omnipedia.wiki_node_tracker'),
    );
  }

  /**
   * {@inheritdoc}
   *
   * @todo Can this use a fuzzy search-style matching for potential near
   *   matches, and suggest them, rather than \in_array()?
   *
   * @see \Drupal\omnipedia_menu\Controller\WikiNodeMenuLinkController::wikiNodeTitleAutocomplete()
   *   Example of using partial text match in a node entity query.
   */
  public function validate($items, Constraint $constraint) {

    /** @var array[] */
    $nodeData = $this->wikiNodeTracker->getTrackedWikiNodeData();

    /** @var \Drupal\Core\Url */
    $contentAdminUrl = Url::fromRoute(
      'system.admin_content', ['type' => Node::getWikiNodeType()],
    );

    /** @var bool */
    $contentAdminUrlAccess = $contentAdminUrl->access();

    /** @var \Drupal\Core\Field\Plugin\Field\FieldType\StringItem $item */
    foreach ($items as $item) {
      if (\in_array($item->value, $nodeData['titles'])) {
        continue;
      }

      // If the user has access to the content overview, include a link to it.
      if ($contentAdminUrlAccess) {
        $this->context->addViolation(
          $constraint->noNodeWithTitleHasContentAdmin, [
            ':contentAdminUrl'  => $contentAdminUrl->toString(),
            '%title'            => $item->value,
          ],
        );

      // If the user does not have access to the content overview, only include
      // the title they searched for.
      } else {
        $this->context->addViolation(
          $constraint->noNodeWithTitleHasNotContentAdmin, [
            '%title' => $item->value,
          ],
        );
      }

    }

  }

}
