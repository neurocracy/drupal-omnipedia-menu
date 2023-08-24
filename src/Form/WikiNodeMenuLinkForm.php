<?php

declare(strict_types=1);

namespace Drupal\omnipedia_menu\Form;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Menu\MenuParentFormSelectorInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a form to add/update wiki node menu links.
 *
 * @see \Drupal\menu_link_content\Form\MenuLinkContentForm
 *   Adapted from this Drupal core form. That form is marked as @internal, so
 *   it's safer to copy it in case it's changed or removed in a future Drupal
 *   core update.
 */
class WikiNodeMenuLinkForm extends ContentEntityForm {

  /**
   * Constructs this form; saves dependencies.
   *
   * @param \Drupal\Core\Entity\EntityRepositoryInterface $entityRepository
   *   The Drupal entity repository.
   *
   * @param \Drupal\Core\Menu\MenuParentFormSelectorInterface $menuParentSelector
   *   The Drupal menu parent form selector service.
   *
   * @param \Drupal\Core\Entity\EntityTypeBundleInfoInterface $entityTypeBundleInfo
   *   The Drupal entity type bundle info service.
   *
   * @param \Drupal\Component\Datetime\TimeInterface $time
   *   The Drupal time service.
   */
  public function __construct(
    EntityRepositoryInterface $entityRepository,
    protected readonly MenuParentFormSelectorInterface $menuParentSelector,
    EntityTypeBundleInfoInterface $entityTypeBundleInfo,
    TimeInterface $time,
  ) {

    parent::__construct($entityRepository, $entityTypeBundleInfo, $time);

  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity.repository'),
      $container->get('menu.parent_form_selector'),
      $container->get('entity_type.bundle.info'),
      $container->get('datetime.time'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $formState) {

    $form = parent::form($form, $formState);

    /** @var string */
    $default = $this->entity->getMenuName() . ':' .
      $this->entity->getParentId();

    /** @var string */
    $id = $this->entity->isNew() ? '' : $this->entity->getPluginId();

    /** @var array */
    $form['menu_parent'] = $this->menuParentSelector->parentSelectElement(
      $default, $id,
    );
    $form['menu_parent']['#weight'] = 10;
    $form['menu_parent']['#title'] = $this->t('Parent link');
    $form['menu_parent']['#description'] = $this->t(
      'The maximum depth for a link and all its children is fixed. Some menu links may not be available as parents if selecting them would exceed this limit.'
    );
    $form['menu_parent']['#attributes']['class'][] = 'menu-title-select';

    // This adds the autocomplete route to the wiki node title. Ideally, this
    // would be done in WikiNodeMenuLink::baseFieldDefinitions(), but that
    // doesn't seem possible.
    if (isset($form['wiki_node_title']['widget'])) {

      for (
        $i = 0; $i < $form['wiki_node_title']['widget']['#cardinality']; $i++
      ) {

        $form['wiki_node_title']['widget'][$i]['value'][
          '#autocomplete_route_name'
        ] = 'omnipedia_core.wiki_node_title_autocomplete';

      }

    }

    return $form;

  }

  /**
   * {@inheritdoc}
   */
  protected function actions(array $form, FormStateInterface $formState) {

    $element = parent::actions($form, $formState);

    $element['submit']['#button_type'] = 'primary';
    $element['delete']['#access'] = $this->entity->access('delete');

    return $element;

  }

  /**
   * {@inheritdoc}
   */
  public function buildEntity(array $form, FormStateInterface $formState) {

    /** @var \Drupal\omnipedia_menu\Entity\WikiNodeMenuLinkInterface $entity */
    $entity = parent::buildEntity($form, $formState);

    list($menu_name, $parent) = explode(
      ':', $formState->getValue('menu_parent'), 2
    );

    $entity->parent->value = $parent;
    $entity->menu_name->value = $menu_name;
    $entity->enabled->value = (!$formState->isValueEmpty(['enabled', 'value']));
    $entity->expanded->value = (
      !$formState->isValueEmpty(['expanded', 'value'])
    );

    return $entity;

  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $formState) {

    // The entity is rebuilt in parent::submit().
    $menuLink = $this->entity;
    $menuLink->save();

    $this->messenger()->addStatus($this->t(
      'The wiki menu link has been saved.'
    ));

    $formState->setRedirectUrl($menuLink->toUrl('canonical'));

  }

}
