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
 * Provides a 'Custom detail metier document Block' block.
 *
 * @Block(
 *   id = "metier_detail_metier_document",
 *   admin_label = @Translation("Page detal metier document Block"),
 * )
 */
class DetailMetierDocumentBlock extends BlockBase implements ContainerFactoryPluginInterface {

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
    $custom_service = \Drupal::service('phenix_custom_block.view_services');


    // Get the current route match.
    $route_match = \Drupal::service('current_route_match');

    // Get the taxonomy term ID from the route parameters.
    $taxonomy_term_id = $route_match->getParameter('taxonomy_term');

    $request = \Drupal::request();
    $termObj = \Drupal::request()->get('taxonomy_term');
    //Verifier si c'est un terme de taxo
    if (!$termObj) {
      return;
    } 
    
    //Vérifier si le terme est de type site metier
    if ($custom_service->getNodeFieldValue($termObj, 'vid') != 'metiers_viande_com') {
      // dump(' haaa', $custom_service->getNodeFieldValue($termObj, 'vid'));
      return ;
    }

    $termId = $termObj->id();
    $getDossierId = \Drupal::database()->query('select field_dossier__target_id from taxonomy_term__field_dossier_ where entity_id = ' . $termId)->fetchAll();
    if ($getDossierId) {
      $getDossierId = array_column($getDossierId, 'field_dossier__target_id');
      $getDossierId = implode(',', $getDossierId);
      $dossierId = \Drupal::database()->query('select field_fichier_target_id from paragraph__field_fichier where entity_id in (' . $getDossierId . ')')->fetchAll();
      $allDoc = [];
      if ($dossierId) {
        $fileIds = array_column($dossierId, 'field_fichier_target_id');
        $fileObjs = File::loadMultiple($fileIds);
        foreach ($fileObjs as $fileObj) {
          $file_created_at = getNodeFieldValue ($fileObj, 'created');
          if ($fileObj) {
            // Get the URL of the file.
            $file_url = Url::fromUri(file_create_url($fileObj->getFileUri()));
            $first_file_extension = getNodeFieldValue($fileObj, 'filemime');
          
            // Generate the link.
            $file_link = Link::fromTextAndUrl(t('<i class="fas fa-file-download"></i>Télécharger le document'), $file_url)->toString();
            $image_link = Link::fromTextAndUrl(t('<img class="detail-metier-doc-img" src="/files/assets/' . $custom_service->getFileTypeExtension($first_file_extension) . '">'), $file_url)->toString();
          }
          $allDoc[] = [
            'file_type' => $custom_service->getFileTypeExtension($first_file_extension),
            'filename' => $custom_service->getNodeFieldValue($fileObj, 'filename'),
            'created_at' => date('d m Y', $file_created_at),
            'size' => $custom_service->getFileSize($fileObj),
            'link' => $file_link,
            'img_link' => $image_link
          ];
        }
      }
    }
    // dump($getDossierId,$request, \Drupal::request()->request,   ' zzzz');
    foreach ($allDoc as $do) {
      // dump($do);
    }

    $current_host = \Drupal::request()->getHost();
    return [
        '#theme' => 'metier_detail_document',
        '#cache' => ['max-age' => 0],
        '#content' => [
          'data' => $allDoc,
        ],
      ];

  }

}
