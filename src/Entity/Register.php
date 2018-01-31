<?php

namespace Drupal\commerce_pos\Entity;

use Drupal\commerce_price\Price;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;

/**
 * Defines the Register entity.
 *
 * @ContentEntityType(
 *   id = "commerce_pos_register",
 *   label = @Translation("Register"),
 *   label_singular = @Translation("register"),
 *   label_plural = @Translation("registers"),
 *   label_count = @PluralTranslation(
 *     singular = "@count register",
 *     plural = "@count registers",
 *   ),
 *   handlers = {
 *     "storage" = "Drupal\commerce\CommerceContentEntityStorage",
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\commerce_pos\RegisterListBuilder",
 *     "form" = {
 *       "add" = "Drupal\commerce_pos\Form\RegisterForm",
 *       "edit" = "Drupal\commerce_pos\Form\RegisterForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm"
 *     },
 *     "route_provider" = {
 *       "default" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "commerce_pos_register",
 *   data_table = "commerce_pos_register_field_data",
 *   admin_permission = "access commerce pos administration pages",
 *   fieldable = TRUE,
 *   translatable = FALSE,
 *   entity_keys = {
 *     "id" = "register_id",
 *     "label" = "name",
 *     "uuid" = "uuid"
 *   },
 *   links = {
 *     "canonical" = "/admin/commerce/config/pos/register/{commerce_pos_register}",
 *     "add-form" = "/admin/commerce/config/pos/register/add",
 *     "edit-form" = "/admin/commerce/config/pos/register/{commerce_pos_register}/edit",
 *     "delete-form" = "/admin/commerce/config/pos/register/{commerce_pos_register}/delete",
 *     "collection" = "/admin/commerce/config/pos/registers"
 *   }
 * )
 */
class Register extends ContentEntityBase implements RegisterInterface {

  /**
   * {@inheritdoc}
   */
  public function getName() {
    return $this->get('name')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setName($name) {
    $this->set('name', $name);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getStore() {
    return $this->get('store_id')->entity;
  }

  /**
   * {@inheritdoc}
   */
  public function setStore(StoreInterface $store) {
    $this->set('store_id', $store->id());
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getStoreId() {
    return $this->get('store_id')->target_id;
  }

  /**
   * {@inheritdoc}
   */
  public function setStoreId($store_id) {
    $this->set('store_id', $store_id);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getOpeningFloat() {
    return $this->get('opening_float')->first()->toPrice();
  }

  /**
   * {@inheritdoc}
   */
  public function setOpeningFloat(Price $opening_float) {
    $this->set('opening_float', $opening_float);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getDefaultFloat() {
    return $this->get('default_float')->first()->toPrice();
  }

  /**
   * {@inheritdoc}
   */
  public function setDefaultFloat(Price $default_float) {
    $this->set('default_float', $default_float);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function open() {
    $this->set('open', TRUE);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function close() {
    $this->set('open', FALSE);

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function isOpen() {
    return $this->get('open')->value;
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['name'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Name'))
      ->setDescription(t('The store name.'))
      ->setRequired(TRUE)
      ->setTranslatable(TRUE)
      ->setSettings([
        'default_value' => '',
        'max_length' => 255,
      ])
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => 0,
      ])
      ->setDisplayConfigurable('view', TRUE)
      ->setDisplayConfigurable('form', TRUE);

    $fields['store_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Store'))
      ->setDescription(t('The store where the register is located.'))
      ->setCardinality(1)
      ->setRequired(TRUE)
      ->setSetting('target_type', 'commerce_store')
      ->setSetting('handler', 'default')
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'commerce_entity_select',
        'weight' => 1,
      ])
      ->setDisplayOptions('form', [
        'type' => 'commerce_entity_select',
        'weight' => 1,
      ])
      ->setTranslatable(TRUE)
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['open'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Open'))
      ->setDescription(t('If this register is open or closed.'))
      ->setDefaultValue(0)
      ->setDisplayOptions('view', [
        'type' => 'boolean_checkbox',
        'weight' => 2,
        'disabled' => TRUE,
      ])
      ->setDisplayConfigurable('view', TRUE)
      ->setDisplayConfigurable('form', FALSE);

    $fields['opening_float'] = BaseFieldDefinition::create('commerce_price')
      ->setLabel(t('Opening Float'))
      ->setDescription(t('The float amount when this register was opened.'))
      ->setRequired(FALSE)
      ->setDefaultValue(0)
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'commerce_price_default',
        'weight' => 3,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', FALSE);

    $fields['default_float'] = BaseFieldDefinition::create('commerce_price')
      ->setLabel(t('Default Float'))
      ->setDescription(t('The float to recommend when opening this register.'))
      ->setRequired(TRUE)
      ->setDefaultValue(0)
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'commerce_price_default',
        'weight' => 4,
      ])
      ->setDisplayOptions('form', [
        'type' => 'commerce_price_default',
        'weight' => 4,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    return $fields;
  }

}
