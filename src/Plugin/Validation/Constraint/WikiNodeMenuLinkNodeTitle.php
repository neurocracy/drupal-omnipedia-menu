<?php

declare(strict_types=1);

namespace Drupal\omnipedia_menu\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraint;

/**
 * Checks that an 'omnipedia_wiki_node_menu_link' points to existing node(s).
 *
 * This specifically checks that attached data with the same type and target
 * cannot overlap their date ranges.
 *
 * @Constraint(
 *   id     = "WikiNodeMenuLinkNodeTitle",
 *   label  = @Translation("Omnipedia wiki node menu link title", context = "Validation"),
 *   type   = "string"
 * )
 */
class WikiNodeMenuLinkNodeTitle extends Constraint {

  /**
   * Violation message for non-existent node title, if content admin access.
   *
   * @var string
   */
  public $noNodeWithTitleHasContentAdmin = 'No <a href=":contentAdminUrl">wiki page</a> exists with the title "%title".';

  /**
   * Violation message for non-existent node title, if no content admin access.
   *
   * @var string
   */
  public $noNodeWithTitleHasNotContentAdmin = 'No wiki page exists with the title "%title".';

}
