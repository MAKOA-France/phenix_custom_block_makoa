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
 *   id = "metier_actu",
 *   admin_label = @Translation("Metier actualité Block"),
 * )
 */
class MetierActuBlock extends BlockBase implements ContainerFactoryPluginInterface {

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

    // Load the EntityTypeManager service.
    $entityTypeManager = \Drupal::service('entity_type.manager');

    // Specify the node type you want to load.
    $node_type = 'actualites'; // Replace 'your_node_type' with the machine name of your node type.

    // Load up to three nodes of the specified node type.
    $nodes = $entityTypeManager->getStorage('node')
      ->loadByProperties(['type' => $node_type], ['limit' => 3]);

    // You can do further processing here with the loaded nodes.
    $data = [];
    foreach ($nodes as $node) {
      //image of actu if exist
      $imgId = $service_block->getNodeFieldValue($node, 'field_image_media');
      if ($imgId) {

        $mediaImage = Media::load($imgId);
        // Get the view builder service.
        $entity_view_builder = \Drupal::entityTypeManager()->getViewBuilder($mediaImage->getEntityTypeId());
        
        // Render the media entity.
        $rendered_media = $entity_view_builder->view($mediaImage);

        //image en html
        $outputImageHtml = \Drupal::service('renderer')->render($rendered_media)->__toString();
        
        //titre de l'actu
        $actuTitle = $service_block->getNodeFieldValue($node, 'title');
        
        //description 
        $actuBody = $service_block->getNodeFieldValue($node, 'body');

        $data[$node->id()][] = ['img' => $outputImageHtml, 'title' => $actuTitle, 'body' => $actuBody];

      }

      
    }

    return; //TODO NE RIEN AFFICHER POU RLE MOMENT
    return [
        '#theme' => 'metier_actu',
        '#cache' => ['max-age' => 0],
        '#content' => [
          'data' => $data,
        ],
      ];

  }

}
