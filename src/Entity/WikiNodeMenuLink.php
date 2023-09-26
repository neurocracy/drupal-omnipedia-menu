<?php

declare(strict_types=1);

namespace Drupal\omnipedia_menu\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityPublishedTrait;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Menu\MenuLinkManagerInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\omnipedia_menu\Entity\WikiNodeMenuLinkInterface;
use Drupal\user\UserInterface;

/**
 * Defines the 'omnipedia_wiki_node_menu_link' entity.
 *
 * This entity represents a wiki node menu link based on a node title rather
 * than a direct link to the node. This loose coupling allows the menu link to
 * easily vary by date.
 *
 * @ContentEntityType(
 *   id           = "omnipedia_wiki_node_menu_link",
 *   label        = @Translation("Omnipedia: Wiki menu link"),
 *   label_collection = @Translation("Wiki menu links"),
 *   label_singular   = @Translation("wiki menu link"),
 *   label_plural     = @Translation("wiki menu links"),
 *   label_count      = @PluralTranslation(
 *     singular = "@count wiki menu link",
 *     plural   = "@count wiki menu links",
 *   ),
 *   admin_permission = "administer menu",
 *   base_table   = "omnipedia_wiki_node_menu_link",
 *   data_table   = "omnipedia_wiki_node_menu_link_data",
 *   translatable = true,
 *   entity_keys  = {
 *     "id"         = "id",
 *     "label"      = "title",
 *     "uuid"       = "uuid",
 *     "langcode"   = "langcode",
 *     "published"  = "enabled",
 *   },
 *   handlers = {
 *     "form"     = {
 *       "default"  = "Drupal\omnipedia_menu\Form\WikiNodeMenuLinkForm",
 *       "delete"   = "Drupal\omnipedia_menu\Form\WikiNodeMenuLinkDeleteForm",
 *     },
 *     "access"   = "Drupal\omnipedia_menu\Access\WikiNodeMenuLinkAccessControlHandler",
 *   },
 *   links = {
 *     "canonical"    = "/admin/structure/menu/wiki-item/{omnipedia_wiki_node_menu_link}/edit",
 *     "edit-form"    = "/admin/structure/menu/wiki-item/{omnipedia_wiki_node_menu_link}/edit",
 *     "delete-form"  = "/admin/structure/menu/wiki-item/{omnipedia_wiki_node_menu_link}/delete",
 *   },
 * )
 *
 * @todo Make revisionable.
 *
 * @todo Remove calls to \Drupal as much as possible and decouple this entity
 *   from services, either by using wrapped entities via the Typed Entity module
 *   or by other means.
 *
 * @todo Can we extend \Drupal\menu_link_content\Entity\MenuLinkContent rather
 *   than copying a lot of its code?
 *
 * @see \Drupal\menu_link_content\Entity\MenuLinkContent
 *   A lot of code has been adapted from the Drupal core menu link content
 *   entity class.
 */
class WikiNodeMenuLink extends ContentEntityBase implements WikiNodeMenuLinkInterface {

  use EntityChangedTrait;
  use EntityPublishedTrait;

  /**
   * A flag for whether this entity is wrapped in a plug-in instance.
   *
   * @var bool
   */
  protected bool $insidePlugin = false;

  /**
   * {@inheritdoc}
   *
   * @see \Drupal\menu_link_content\Entity\MenuLinkContent::baseFieldDefinitions()
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entityType) {

    /** @var \Drupal\Core\Field\BaseFieldDefinition[] $fields */
    $fields = parent::baseFieldDefinitions($entityType);

    // Add the publishing status field.
    $fields += static::publishedBaseFieldDefinitions($entityType);

    $fields['id']
      ->setLabel(new TranslatableMarkup('Entity ID'))
      ->setDescription(new TranslatableMarkup(
        'The entity ID for this wiki menu link.'
      ));

    $fields['uuid']->setDescription(new TranslatableMarkup(
      'The wiki menu link UUID.'
    ));

    $fields['langcode']->setDescription(new TranslatableMarkup(
      'The wiki menu link language code.'
    ));

    $fields['title'] = BaseFieldDefinition::create('string')
      ->setLabel(new TranslatableMarkup('Menu link title'))
      ->setDescription(new TranslatableMarkup(
        'The text to be used for this link in the menu.'
      ))
      ->setRequired(true)
      ->setTranslatable(true)
      ->setRevisionable(true)
      ->setSetting('max_length', 255)
      ->setDisplayOptions('view', [
        'label'   => 'hidden',
        'type'    => 'string',
        'weight'  => -5,
      ])
      ->setDisplayOptions('form', [
        'type'    => 'string_textfield',
        'weight'  => -5,
      ])
      ->setDisplayConfigurable('form', true);

    $fields['description'] = BaseFieldDefinition::create('string')
      ->setLabel(new TranslatableMarkup('Description'))
      ->setDescription(new TranslatableMarkup(
        'Shown when hovering over the menu link.'
      ))
      ->setTranslatable(true)
      ->setRevisionable(true)
      ->setSetting('max_length', 255)
      ->setDisplayOptions('view', [
        'label'   => 'hidden',
        'type'    => 'string',
        'weight'  => 0,
      ])
      ->setDisplayOptions('form', [
        'type'    => 'string_textfield',
        'weight'  => 0,
      ]);

    $fields['menu_name'] = BaseFieldDefinition::create('string')
      ->setLabel(new TranslatableMarkup('Menu name'))
      ->setDescription(new TranslatableMarkup(
        'The menu name. All links with the same menu name (such as "tools") are part of the same menu.'
      ))
      ->setDefaultValue('tools')
      ->setSetting('is_ascii', true);

    $fields['wiki_node_title'] = BaseFieldDefinition::create('string')
      ->setLabel(new TranslatableMarkup('Wiki page title'))
      ->setDescription(new TranslatableMarkup(
        'The title of the wiki page to link to.'
      ))
      ->setRequired(true)
      ->setTranslatable(true)
      ->setRevisionable(true)
      ->addConstraint('WikiNodeMenuLinkNodeTitle')
      ->setSetting('max_length', 255)
      ->setDisplayOptions('view', [
        'label'   => 'hidden',
        'type'    => 'string',
        'weight'  => -5,
      ])
      ->setDisplayOptions('form', [
        'type'    => 'string_textfield',
        'weight'  => -5,
        // @see \Drupal\omnipedia_menu\Form\WikiNodeMenuLinkForm::form()
        //   Sets autocomplete, because it doesn't seem to be possible here in
        //   base field definitions.
      ])
      ->setDisplayConfigurable('form', true);

    // This is currently unused but may be implemented in the future.
    $fields['fallback_behaviour'] = BaseFieldDefinition::create('list_string')
      ->setLabel(new TranslatableMarkup('Fallback behaviour'))
      ->setDescription(new TranslatableMarkup(
        'What to do when a wiki page is not available for the current date, either because it does not exist or the current user does not have access to it.'
      ))
      ->setSetting('max_length', 255)
      ->setSetting('allowed_values', [
        'hide'    => new TranslatableMarkup('Hide link'),
        'last'    => new TranslatableMarkup('Last available date'),
        'nolink'  => new TranslatableMarkup('Display as unlinked text'),
      ])
      ->setDefaultValue('hide')
      ->setRequired(true);
      // ->setDisplayOptions('form', [
      //   'type'    => 'options_buttons',
      //   'weight'  => -4,
      // ])
      // ->setDisplayOptions('view', [
      //   'label'   => 'above',
      //   'weight'  => -4,
      // ]);

    $fields['weight'] = BaseFieldDefinition::create('integer')
      ->setLabel(new TranslatableMarkup('Weight'))
      ->setDescription(new TranslatableMarkup(
        'Link weight among links in the same menu at the same depth. In the menu, the links with high weight will sink and links with a low weight will be positioned nearer the top.'
      ))
      ->setDefaultValue(0)
      ->setDisplayOptions('view', [
        'label'   => 'hidden',
        'type'    => 'number_integer',
        'weight'  => 0,
      ])
      ->setDisplayOptions('form', [
        'type'    => 'number',
        'weight'  => 20,
      ]);

    $fields['expanded'] = BaseFieldDefinition::create('boolean')
      ->setLabel(new TranslatableMarkup('Show as expanded'))
      ->setDescription(new TranslatableMarkup(
        'If selected and this menu link has children, the menu will always appear expanded. This option may be overridden for the entire menu tree when placing a menu block.'
      ))
      ->setDefaultValue(false)
      ->setDisplayOptions('view', [
        'label'   => 'hidden',
        'type'    => 'boolean',
        'weight'  => 0,
      ])
      ->setDisplayOptions('form', [
        'settings'  => ['display_label' => true],
        'weight'    => 0,
      ]);

    // Override some properties of the published field added by
    // \Drupal\Core\Entity\EntityPublishedTrait::publishedBaseFieldDefinitions().
    $fields['enabled']->setLabel(new TranslatableMarkup('Enabled'));
    $fields['enabled']->setDescription(new TranslatableMarkup(
      'A flag for whether the link should be enabled in menus or hidden.'
    ));
    $fields['enabled']->setTranslatable(false);
    $fields['enabled']->setDisplayOptions('view', [
      'label'   => 'hidden',
      'type'    => 'boolean',
      'weight'  => 0,
    ]);
    $fields['enabled']->setDisplayOptions('form', [
      'settings'  => ['display_label' => true],
      'weight'    => -1,
    ]);

    $fields['parent'] = BaseFieldDefinition::create('string')
      ->setLabel(new TranslatableMarkup('Parent plugin ID'))
      ->setDescription(new TranslatableMarkup(
        'The ID of the parent menu link plugin, or empty string when at the top level of the hierarchy.'
      ));

    $fields['uid'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(new TranslatableMarkup('Author'))
      ->setDescription(new TranslatableMarkup(
        'The user who authored this wiki menu link.'
      ))
      ->setSetting('target_type', 'user')
      ->setSetting('handler', 'default');

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(new TranslatableMarkup('Created'))
      ->setDescription(new TranslatableMarkup(
        'The time that the wiki menu link was created.'
      ));

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(new TranslatableMarkup('Changed'))
      ->setDescription(new TranslatableMarkup(
        'The time that this wiki menu link was last edited.'
      ));

    return $fields;

  }

  /**
   * Get the Drupal menu link manager service.
   *
   * @return \Drupal\Core\Menu\MenuLinkManagerInterface
   */
  protected static function getMenuLinkManager(): MenuLinkManagerInterface {
    return \Drupal::service('plugin.manager.menu.link');
  }

  /**
   * {@inheritdoc}
   */
  public function setInsidePlugin(): void {
    $this->insidePlugin = true;
  }

  /**
   * {@inheritdoc}
   */
  public function getWikiNodeTitle(): string {
    return $this->get('wiki_node_title')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function getFallbackBehaviour(): string {
    return $this->get('fallback_behaviour')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function getTitle(): string {
    return $this->get('title')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function getMenuName(): string {
    return $this->get('menu_name')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription(): ?string {
    return $this->get('description')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function getPluginId(): string {
    return 'omnipedia_wiki_node_menu_link:' . $this->uuid();
  }

  /**
   * {@inheritdoc}
   */
  public function isEnabled(): bool {
    return (bool) $this->get('enabled')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function isExpanded(): bool {
    return (bool) $this->get('expanded')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function getParentId(): string {
    // Cast the parent ID to a string, only an empty string means no parent,
    // null keeps the existing parent.
    return (string) $this->get('parent')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function getWeight(): int {
    return (int) $this->get('weight')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function getPluginDefinition(): array {

    return [
      // @todo Can we read the classes from the omnipedia_menu.links.menu.yml
      //   values rather than hard-coding them?
      'class'       => '\Drupal\omnipedia_menu\Plugin\Menu\WikiNodeMenuLink',
      'form_class'  => '\Drupal\omnipedia_menu\Form\WikiNodeMenuLinkForm',
      'provider'    => 'omnipedia_menu',
      'menu_name'   => $this->getMenuName(),
      'title'       => $this->getTitle(),
      'description' => $this->getDescription(),
      'weight'      => $this->getWeight(),
      'id'          => $this->getPluginId(),
      // This always points to the canonical node route.
      'route_name'  => 'entity.node.canonical',
      'enabled'     => $this->isEnabled() ? 1 : 0,
      'expanded'    => $this->isExpanded() ? 1 : 0,
      'discovered'  => 0,
      'parent'      => $this->getParentId(),
      'metadata'    => [
        'entity_id'           => $this->id(),
        'wiki_node_title'     => $this->getWikiNodeTitle(),
        'fallback_behaviour'  => $this->getFallbackBehaviour(),
      ],
    ];

  }

  /**
   * {@inheritdoc}
   */
  public function getOwner() {
    return $this->get('uid')->entity;
  }

  /**
   * {@inheritdoc}
   */
  public function getOwnerId() {
    return $this->get('uid')->target_id;
  }

  /**
   * {@inheritdoc}
   */
  public function setOwnerId($uid) {
    $this->set('uid', $uid);

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setOwner(UserInterface $account) {
    $this->set('uid', $account->id());

    return $this;
  }

  /**
   * {@inheritdoc}
   *
   * When a new entity instance is added, set the uid entity reference to the
   * current user as the creator of the instance.
   */
  public static function preCreate(
    EntityStorageInterface $storage, array &$values
  ) {
    parent::preCreate($storage, $values);

    $values += [
      'uid' => \Drupal::currentUser()->id(),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function postSave(EntityStorageInterface $storage, $update = true) {

    parent::postSave($storage, $update);

    // // Don't update the menu tree if a pending revision was saved.
    // if (!$this->isDefaultRevision()) {
    //   return;
    // }

    /** @var \Drupal\Core\Menu\MenuLinkManagerInterface $menuLinkManager */
    $menuLinkManager = static::getMenuLinkManager();

    // The menu link can just be updated if there is already an menu link entry
    // on both entity and menu link plug-in level.
    $definition = $this->getPluginDefinition();

    // Even when $update is false, for top level links it is possible the link
    // is already in the storage because of the getPluginDefinition() call
    // above. Because of this the $update flag is ignored and only the existence
    // of the definition (equals to being in the tree storage) is checked.
    //
    // @see https://www.drupal.org/node/2605684#comment-10515450
    //   For the call chain.
    if ($menuLinkManager->getDefinition($this->getPluginId(), false)) {

      // When the entity is saved via a plug-in instance, we should not call
      // the menu tree manager to update the definition a second time.
      if (!$this->insidePlugin) {
        $menuLinkManager->updateDefinition(
          $this->getPluginId(), $definition, false
        );
      }

    } else {
      $menuLinkManager->addDefinition($this->getPluginId(), $definition);
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function preDelete(
    EntityStorageInterface $storage, array $entities
  ) {

    parent::preDelete($storage, $entities);

    /** @var \Drupal\Core\Menu\MenuLinkManagerInterface $menuLinkManager */
    $menuLinkManager = static::getMenuLinkManager();

    foreach ($entities as $menuLink) {
      /** @var \Drupal\omnipedia_menu\Entity\WikiNodeMenuLinkInterface $menuLink */
      $menuLinkManager->removeDefinition($menuLink->getPluginId(), false);

      // Children get re-attached to the menu link's parent.
      $parentPluginId = $menuLink->getParentId();

      /** @var string[] Zero or more menu link entity IDs, keyed by their most recent revision ID. */
      $queryResult = ($storage->getQuery())
        ->condition('parent', $menuLink->getPluginId())
        ->accessCheck(false)
        ->execute();

      $children = $storage->loadMultiple($queryResult);

      foreach ($children as $child) {
        /** @var \Drupal\omnipedia_menu\Entity\WikiNodeMenuLinkInterface $child */
        $child->set('parent', $parentPluginId)->save();
      }
    }
  }

}
