<?php

// src/Plugin/Block/CustomBlock.php

namespace Drupal\phenix_custom_block\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\file\Entity\File;
use Drupal\media\Entity\Media;
use Drupal\Core\Url;
use Drupal\Core\Link;
/**
 * Provides a 'Custom newsletter Block' block.
 *
 * @Block(
 *   id = "metier_newsletter",
 *   admin_label = @Translation("Metier newsletter Block"),
 * )
 */
class MetierNewsletterBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * The configuration factory service.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Constructs a new MetierHomeContentVideoBlock instance.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The configuration factory service.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, ConfigFactoryInterface $config_factory , EntityTypeManagerInterface $entity_type_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->configFactory = $config_factory;
    $this->entityTypeManager = $entity_type_manager;

  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('config.factory'),
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    // Fetch configuration.
    $service_block = \Drupal::service('phenix_custom_block.view_services');
    $config = $this->configFactory->get('custom_block.settings');
   
    // Load the custom form.
    $form = \Drupal::formBuilder()->getForm('Drupal\phenix_custom_block\Form\MetierNewlsetterForm');
    $current_host = \Drupal::request()->getHost();

    return;//TODO NE RIEN AFFICHER POUR LE MOMENT
    return [
        '#theme' => 'metier_newsletter',
        '#cache' => ['max-age' => 0],
        '#content' => [
          'form' => $form,
          'host' => $current_host,
        ],
      ];

  }

}
