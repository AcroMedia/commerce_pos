<?php

namespace Drupal\commerce_pos_label;

use Drupal\commerce_pos_label\Plugin\LabelFormat\LabelFormat;
use Drupal\Component\Plugin\Exception\PluginException;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\Core\Plugin\Discovery\ContainerDerivativeDiscoveryDecorator;
use Drupal\Core\Plugin\Discovery\YamlDiscovery;

/**
 * Manages discovery and instantiation of label_format plugins.
 *
 * @see \Drupal\commerce_pos_label\Plugin\LabelFormat\LabelFormatInterface
 * @see plugin_api
 */
class LabelFormatManager extends DefaultPluginManager {

  /**
   * Default values for each plugin.
   *
   * @var array
   */
  protected $defaults = [
    'id' => '',
    'title' => '',
    'css' => FALSE,
    'dimensions' => [
      'width' => 0,
      'height' => 0,
    ],
    'class' => LabelFormat::class,
  ];

  /**
   * Constructs a new LabelFormatManager object.
   *
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_backend
   *   The cache backend.
   */
  public function __construct(ModuleHandlerInterface $module_handler, CacheBackendInterface $cache_backend) {
    $this->moduleHandler = $module_handler;
    $this->setCacheBackend($cache_backend, 'label_format', ['label_format']);
  }

  /**
   * {@inheritdoc}
   */
  protected function getDiscovery() {
    if (!isset($this->discovery)) {
      $this->discovery = new YamlDiscovery('label_formats', $this->moduleHandler->getModuleDirectories());
      $this->discovery->addTranslatableProperty('title', 'title_context');
      $this->discovery = new ContainerDerivativeDiscoveryDecorator($this->discovery);
    }
    return $this->discovery;
  }

  /**
   * {@inheritdoc}
   */
  public function processDefinition(&$definition, $plugin_id) {
    parent::processDefinition($definition, $plugin_id);
    $definition['id'] = $plugin_id;
    if (empty($definition['title'])) {
      throw new PluginException(sprintf('The label format %s must define the title property.', $plugin_id));
    }
    if (empty($definition['dimensions']['width'])) {
      throw new PluginException(sprintf('The label format %s must define the dimension width property.', $plugin_id));
    }
    if (empty($definition['dimensions']['height'])) {
      throw new PluginException(sprintf('The label format %s must define the dimension height property.', $plugin_id));
    }
  }

}
