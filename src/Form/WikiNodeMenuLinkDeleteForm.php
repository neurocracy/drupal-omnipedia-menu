<?php

declare(strict_types=1);

namespace Drupal\omnipedia_menu\Form;

use Drupal\Core\Entity\ContentEntityDeleteForm;
use Drupal\Core\Url;

/**
 * Provides a delete form for wiki node menu links.
 *
 * @see \Drupal\menu_link_content\Form\MenuLinkContentDeleteForm
 *   Adapted from this Drupal core form. That form is marked as @internal, so
 *   it's safer to copy it in case it's changed or removed in a future Drupal
 *   core update.
 */
class WikiNodeMenuLinkDeleteForm extends ContentEntityDeleteForm {

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {

    if ($this->moduleHandler->moduleExists('menu_ui')) {
      return new Url(
        'entity.menu.edit_form', ['menu' => $this->entity->getMenuName()]
      );
    }

    return $this->entity->toUrl();

  }

  /**
   * {@inheritdoc}
   */
  protected function getRedirectUrl() {
    return $this->getCancelUrl();
  }

  /**
   * {@inheritdoc}
   */
  protected function getDeletionMessage() {
    return $this->t('The wiki menu link %title has been deleted.', [
      '%title' => $this->entity->label(),
    ]);
  }

}
