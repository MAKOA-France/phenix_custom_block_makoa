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
    if ($current_user->id()) {


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
      
      $all_menu = [];
      foreach ($menu_tree as $key => $item) {
        if (isset($item['user.page'])) {
          $keys = array_keys($item);
          for ($i=0; $i<count($keys); $i++) {
            $title = is_string($item[$keys[$i]]['title']) ? $item[$keys[$i]]['title'] :  $item[$keys[$i]]['title']->__toString();
            $all_menu[] = ['url' => $item[$keys[$i]]['url']->toString(), 'title' => $title];
          }
        }
      }

      // $all_menu[0]['icon'] = "fas fa-comment";
      $all_menu[0]['icon'] = "fas fa-home";
      $all_menu[1]['icon'] = 'icon-custom-user-account';
      $all_menu[2]['icon'] = "icon-custom-logout-accout";
      $all_menu[3]['icon'] = 'icon-custom-refresh';
  

      return [
        '#theme' => 'my_account_block',
        '#cache' => ['max-age' => 0],
        '#content' => [
          'username' => $current_user_name,
          'menu_item' => $all_menu,
        ],
      ];
    }
  }

}
