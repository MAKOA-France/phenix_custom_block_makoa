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
 * Provides a 'Mes Commissions ' block.
 *
 * @Block(
 *  id = "my_group",
 *  admin_label = @Translation("Block mes commissions"),
 *  category = @Translation("Block  mes commissions"),
 * )
 */
class MyGroupBlock  extends BlockBase  {



  /**
   * {@inheritdoc}
   */
  public function build() {

    // Get the current user.
    $current_user = \Drupal::currentUser();

    // Get the current user's name.
    $current_user_name = $current_user->getAccountName();
    $service_block = \Drupal::service('phenix_custom_block.view_services');

    $results = $service_block->getAllMyGroup();
     
    $data = [];
    foreach ($results as $res) {
      $total_membre = $service_block->getTotalGroupMember($res->civicrm_group_civicrm_group_contact_id);
      $total_membre = $total_membre[0]['count'];
      $data[] = [
        'title' => $res->civicrm_group_civicrm_group_contact_title,
        'total' => $total_membre,
        'total_multiple' => $total_membre > 1 ? 'Personnes' : 'Personne',
      ];
    }

    return [
      '#theme' => 'my_group',
      '#cache' => ['max-age' => 0],
      '#content' => [
        'data' => $data,
      ],
    ];
  }

}
