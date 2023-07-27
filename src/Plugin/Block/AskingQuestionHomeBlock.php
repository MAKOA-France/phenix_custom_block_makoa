<?php

namespace Drupal\phenix_custom_block\Plugin\Block;

use Drupal\node\Entity\Node;
use \Drupal\node\NodeInterface;
use Drupal\Core\Block\BlockBase;
use Drupal\media\Entity\Media;


use Drupal\Core\Menu\MenuLinkTreeInterface;
use Drupal\Core\Menu\MenuTreeParameters;
use Symfony\Component\DependencyInjection\ContainerInterface;
/**
 * Provides a 'Poser question ' block.
 *
 * @Block(
 *  id = "home_ask_question",
 *  admin_label = @Translation("Block home ask a question"),
 *  category = @Translation("Block home ask a question"),
 * )
 */
class AskingQuestionHomeBlock  extends BlockBase  {



  /**
   * {@inheritdoc}
   */
  public function build() {

    // Get the current user.
    $current_user = \Drupal::currentUser();
    $user_id = $current_user->id();
    $link_ask_question = '/form/poser-une-question?cid2=' . $user_id;
    // Get the current user's name.
    $current_user_name = $current_user->getAccountName();
    $service_block = \Drupal::service('phenix_custom_block.view_services');

    $entityTypeManager = \Drupal::entityTypeManager();

    // Load the webform entity by its ID.
    $webform = $entityTypeManager->getStorage('webform')->load('poser_une_question');
    // Get the webform's elements, which include the fields.
    $elements = $webform->getElementsInitialized();
    $options = $elements['civicrm_1_activity_1_fieldset_fieldset']['civicrm_1_activity_1_cg30_custom_166']['#options'];

    $res_option = [];
    $is_int = 0;
    foreach ($options as $index => $option) {

      if (is_string($index)) {
        $is_int = $index;
      }
      
      if (is_int($index) && $is_int == 'crm_optgroup_social') {
        $res_option['Droit du travail - Convention Collective - Emploi/Formation'][$index] = $option; 
      }
      
      if (is_int($index) && $is_int == 'crm_optgroup_autres') {
        $res_option['Autres sujets'][$index] = $option; 
      }
    }
    

    $build['category'] = [
      '#type' => 'select',
      '#title' => t('Catégorie'),
      '#options' => $res_option
    ];

    $data = [];

    return [
      '#theme' => 'home_ask_question',
      '#cache' => ['max-age' => 0],
      '#content' => [
        'data' => $data,
        'option' => $build,
        'link_ask_question' => $link_ask_question,
      ],
    ];
  }

}
