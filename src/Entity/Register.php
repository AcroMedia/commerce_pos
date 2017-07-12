<?php

namespace Drupal\commerce_pos\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;

/**
 * Defines the Register entity.
 *
 * @ConfigEntityType(
 *   id = "register",
 *   label = @Translation("Register"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\commerce_pos\RegisterListBuilder",
 *     "form" = {
 *       "add" = "Drupal\commerce_pos\Form\RegisterForm",
 *       "edit" = "Drupal\commerce_pos\Form\RegisterForm",
 *       "delete" = "Drupal\commerce_pos\Form\RegisterDeleteForm"
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\commerce_pos\RegisterHtmlRouteProvider",
 *     },
 *   },
 *   config_prefix = "register",
 *   admin_permission = "administer site configuration",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid"
 *   },
 *   links = {
 *     "canonical" = "/admin/structure/pos/register/register/{register}",
 *     "add-form" = "/admin/structure/pos/register/register/add",
 *     "edit-form" = "/admin/structure/pos/register/register/{register}/edit",
 *     "delete-form" = "/admin/structure/pos/register/register/{register}/delete",
 *     "collection" = "/admin/structure/pos/register/register"
 *   }
 * )
 */
class Register extends ConfigEntityBase implements RegisterInterface {

  /**
   * The Register ID.
   *
   * @var string
   */
  protected $id;

  /**
   * The Register label.
   *
   * @var string
   */
  protected $label;

}
