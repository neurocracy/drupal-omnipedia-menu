<?php

namespace Drupal\omnipedia_menu\Form;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Form\FormStateInterface;
// use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Menu\MenuParentFormSelectorInterface;
use Drupal\Core\Path\PathValidatorInterface;
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
   * The wiki node menu link entity.
   *
   * @var \Drupal\omnipedia_menu\Entity\WikiNodeMenuLinkInterface
   */
  protected $entity;

  /**
   * The Drupal parent form selector service.
   *
   * @var \Drupal\Core\Menu\MenuParentFormSelectorInterface
   */
  protected $menuParentSelector;

  /**
   * The Drupal path validator.
   *
   * @var \Drupal\Core\Path\PathValidatorInterface
   */
  protected $pathValidator;

  /**
   * Constructs this form; saves dependencies.
   *
   * @param \Drupal\Core\Entity\EntityRepositoryInterface $entityRepository
   *   The Drupal entity repository.
   *
   * @param \Drupal\Core\Menu\MenuParentFormSelectorInterface $menuParentSelector
   *   The Drupal menu parent form selector service.
   *
   * @param \Drupal\Core\Language\LanguageManagerInterface $languageManager
   *   The Drupal language manager.
   *
   * @param \Drupal\Core\Path\PathValidatorInterface $pathValidator
   *   The Drupal path validator.
   *
   * @param \Drupal\Core\Entity\EntityTypeBundleInfoInterface $entityTypeBundleInfo
   *   The Drupal entity type bundle info service.
   *
   * @param \Drupal\Component\Datetime\TimeInterface $time
   *   The Drupal time service.
   */
  public function __construct(
    EntityRepositoryInterface       $entityRepository,
    MenuParentFormSelectorInterface $menuParentSelector,
    // LanguageManagerInterface        $languageManager,
    PathValidatorInterface          $pathValidator,
    EntityTypeBundleInfoInterface   $entityTypeBundleInfo = null,
    TimeInterface                   $time = null
  ) {
    parent::__construct($entityRepository, $entityTypeBundleInfo, $time);

    $this->menuParentSelector = $menuParentSelector;
    $this->pathValidator = $pathValidator;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity.repository'),
      $container->get('menu.parent_form_selector'),
      // $container->get('language_manager'),
      $container->get('path.validator'),
      $container->get('entity_type.bundle.info'),
      $container->get('datetime.time')
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
      $default, $id
    );
    $form['menu_parent']['#weight'] = 10;
    $form['menu_parent']['#title'] = $this->t('Parent link');
    $form['menu_parent']['#description'] = $this->t(
      'The maximum depth for a link and all its children is fixed. Some menu links may not be available as parents if selecting them would exceed this limit.'
    );
    $form['menu_parent']['#attributes']['class'][] = 'menu-title-select';

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