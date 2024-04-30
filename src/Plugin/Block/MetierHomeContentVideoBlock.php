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
 * Provides a 'Custom Block' block.
 *
 * @Block(
 *   id = "metier_home_content_video",
 *   admin_label = @Translation("Home metier content video Block"),
 * )
 */
class MetierHomeContentVideoBlock extends BlockBase implements ContainerFactoryPluginInterface {

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
    $config = $this->configFactory->get('custom_block.settings');
    $block_content = $config->get('block_content');
    $service_block = \Drupal::service('phenix_custom_block.view_services');
    //Recuperer tous les metiers avec la video
    // Load the parent taxonomy term.
    $parent_tid = 6328; // Replace 123 with the ID of the parent term.
    $parent_term = \Drupal\taxonomy\Entity\Term::load($parent_tid);

    if ($parent_term) {
      // Load all child taxonomy terms of the parent term.
      $child_terms = \Drupal::entityTypeManager()
        ->getStorage('taxonomy_term')
        ->loadChildren($parent_term->id());
     // Sort the paragraphs by weight.
      usort($child_terms, function($a, $b) {
        return $a->get('weight')->value - $b->get('weight')->value;
      });

        $video_render_array = [];
      // Iterate over each child term.
      foreach ($child_terms as $child_term) {
        $childId = $child_term->id();
        $metierObject = \Drupal\taxonomy\Entity\Term::load($childId);
        $metierName = $metierObject->getName();
        $allDossierId = $metierObject->get('field_dossier_')->getValue();
        $allDossierId = array_column($allDossierId, 'target_id');
        $dossierObj = \Drupal\paragraphs\Entity\Paragraph::loadMultiple($allDossierId);

        //generer un url 
        $url = Url::fromRoute('entity.taxonomy_term.canonical', ['taxonomy_term' => $childId]);
        // Generate the link using the URL object.
        $link = Link::fromTextAndUrl(t('En savoir plus...'), $url)->toRenderable();

        // Get the alias of the term link.
        // $alias = \Drupal::service('path_alias.manager')->getAliasByPath($url->getInternalPath());
          
        // Render the link and its alias.
        $output = [
          '#theme' => 'item_list',
          '#items' => [
            ['#markup' => render($link)],
          ],
          '#attributes' => [
            'class' => ['metier-home-title-video']
            ]
        ];

        // $rendered_output = \Drupal::service('renderer')->renderRoot($output);

        if ($dossierObj) {
          //check if dossier has video
          foreach ($dossierObj as $paragraphObj) {
            if ($paragraphObj->hasField('field_video')) {

              // $videoId = $service_block->getNodeFieldValue($paragraphObj, 'field_video');
              $video_id = $service_block->getNodeFieldValue($paragraphObj, 'field_video');
              $video = $service_block->load_video_by_id($video_id);
              
              // Use the 'full' view mode to render the media entity.
              $view_builder = $this->entityTypeManager->getViewBuilder('media');

              $outputHtml = \Drupal::service('renderer')->render($output)->__toString();
              $rendered_media['#suffix'] = '<div class="cust-output">' . $outputHtml . '</div>';
              
              $htmlVideo = '<div class="metier-text-img-video"> <div class="video-block-cus">' . render($view_builder->view($video, 'full'))->__toString() . '</div><div class="title-and-link"> ' . $outputHtml . '</div></div>';
              $video_render_array[] = $htmlVideo;
              // $data .= '<div class="text-img-video"> ' . render($video_render_array)->__toString() . '</div>';
              // $mediaObject = \Drupal\media\Entity\Media::load($videoId);
            }

            if ($paragraphObj->hasField('field_image_media')) {
              $imageId = $service_block->getNodeFieldValue($paragraphObj, 'field_image_media');
              $mediaImage = Media::load($imageId);
              
              // Get the view builder service.
              $entity_view_builder = \Drupal::entityTypeManager()->getViewBuilder($mediaImage->getEntityTypeId());

              // Render the media entity.
              $rendered_media = $entity_view_builder->view($mediaImage);

              // Output the rendered media.
              $outputHtml = \Drupal::service('renderer')->render($output)->__toString();
              // $rendered_media['#suffix'] = '<div class="cust-output">' . $outputHtml . '</div>';
              $outputHtmlmedia = \Drupal::service('renderer')->render($rendered_media)->__toString();
              $video_render_array[] = '<div class="cust-output">' . $outputHtmlmedia. $outputHtml . '</div>';
              // $video_render_array[] = ['link' => $output];

              
            }
          }
        }
      }
    }

    return [
        '#theme' => 'metier_home_content_video',
        '#cache' => ['max-age' => 0],
        '#content' => [
          'data' => $allVideo,
          'video' => $video_render_array
        ],
      ];

  }

}
