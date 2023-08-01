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
 * Provides a 'Mes réunions ' block.
 *
 * @Block(
 *  id = "home_my_meetings",
 *  admin_label = @Translation("Block home meetings"),
 *  category = @Translation("Block  home meetings"),
 * )
 */
class MyMeetingBlock  extends BlockBase  {



  /**
   * {@inheritdoc}
   */
  public function build() {

    // Get the current user.
    $current_user = \Drupal::currentUser();

    // Get the current user's name.
    $current_user_name = $current_user->getAccountName();
    $service_block = \Drupal::service('phenix_custom_block.view_services');

     \Drupal::service('civicrm')->initialize();


     $account = \Drupal\user\Entity\User::load($current_user->id());
     // Get the user's email address.
     $email = $account->getEmail();
     $cid = $service_block->getContactIdByEmail($email);
    $all_meetings = $service_block->getAllMeetings($cid);
    foreach ($all_meetings as $meet) {
      $formated_date = $service_block->formatDateWithMonthInLetterAndHours ($meet->event_start_date);
      $meet->formated_start_date = $formated_date;
      $linked_group = $service_block->getLinkedGroupWithEvent ($meet->event_id); 
      $meet->linked_group = $linked_group;
    }

  

    return [
      '#theme' => 'home_my_meetings',
      '#cache' => ['max-age' => 0],
      '#content' => [
        'data' => $all_meetings,
      ],
    ];
  }

}
