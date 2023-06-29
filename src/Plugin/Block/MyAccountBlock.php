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
 * Provides a 'Mon compte ' block.
 *
 * @Block(
 *  id = "my_account_block",
 *  admin_label = @Translation("Block mon compte"),
 *  category = @Translation("Block mon compte"),
 * )
 */
class MyAccountBlock  extends BlockBase  {



  /**
   * {@inheritdoc}
   */
  public function build() {

    // Get the current user.
    $current_user = \Drupal::currentUser();

    // Get the current user's name.
    $current_user_name = $current_user->getAccountName();
    
    $menu_name = 'account';
    $depth = 3;
  
    /** @var \Drupal\Core\Menu\MenuLinkTreeInterface $menuLinkTree */
    $menuLinkTree = \Drupal::service('menu.link_tree');
    $parameters = new MenuTreeParameters();
    $parameters->setMaxDepth($depth);
    $tree = $menuLinkTree->load($menu_name, $parameters);
    $manipulators = [
      ['callable' => 'menu.default_tree_manipulators:checkAccess'],
      ['callable' => 'menu.default_tree_manipulators:generateIndexAndSort'],
    ];
    $tree = $menuLinkTree->transform($tree, $manipulators);
    $menu_tree = $menuLinkTree->build($tree);
  
    $menu_items = [];
  
    // Get the current path.
    $currentPath = \Drupal::service('path.current')->getPath();
    
    // if ($currentPath == '/node/96') {
      
      foreach ($menu_tree as $item) {
        if (isset($item['user.page'])) {
          // dump($item);
    
        }
        // $menu_items[] = $item->link->getTitle();
      }
      $menu_items[] = 'Communication';
      $menu_items[] = 'Demande d\'accès';
      $menu_items[] = 'Deconnecter test';
      
    // }

    return [
      '#theme' => 'my_account_block',
      '#cache' => ['max-age' => 0],
      '#content' => [
        'username' => $current_user_name,
        'menu_item' => $menu_items,
      ],
    ];
  }

}
