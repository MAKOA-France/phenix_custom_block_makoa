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

    // Get the current user's name.
    $current_user_name = $current_user->getAccountName();
    $service_block = \Drupal::service('phenix_custom_block.view_services');

    $data = [];

    return [
      '#theme' => 'home_ask_question',
      '#cache' => ['max-age' => 0],
      '#content' => [
        'data' => $data,
      ],
    ];
  }

}
