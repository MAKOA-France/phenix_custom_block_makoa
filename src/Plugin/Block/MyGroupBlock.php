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
    // Load the user account entity to access the email field.
    $account = \Drupal\user\Entity\User::load($current_user->id());
    // Get the user's email address.
    $email = $account->getEmail();
    $cid = $service_block->getContactIdByEmail($email);
    $results = $service_block->getAllMyGroup($cid);
     
    $data = [];
    
    foreach ($results as $res) {
      $total_membre = $service_block->getTotalGroupMember($res->civicrm_group_civicrm_group_contact_id);
      $total_membre = $total_membre[0]['count'];
      $data[] = [
        'title' => $res->civicrm_group_civicrm_group_contact_title,
        'group_id' => $res->civicrm_group_civicrm_group_contact_id,
        'total' => $total_membre,
        'total_multiple' => $total_membre > 1 ? 'Personnes' : 'Personne',
      ];
    }

    // Create a new DrupalDateTime object with the current date and time.
    $current_date = new \Drupal\Core\Datetime\DrupalDateTime('now');

    // You can format the date as needed.
    $formatted_date = $current_date->format('Y-m-d H:i:s');
    $allGroupes = [];
    $newData = [];
    foreach ($data as $group) {
      $groupId = $group['group_id'];
      $events = \Civi\Api4\Event::get(FALSE)
        ->addSelect('title', 'start_date')
        ->addWhere('rsvpevent_cg_linked_groups.rsvpevent_cf_linked_groups', '=', $groupId)
        ->addWhere('start_date', '>=', $formatted_date)
        ->addOrderBy('start_date', 'DESC')
        ->execute();

        $groupContacts = \Civi\Api4\GroupContact::get(FALSE)
          ->addSelect('COUNT(contact_id) AS count', 'group_id.title')
          ->addWhere('group_id', '=', $groupId)
          ->execute()->first();
        $total = $groupContacts['count'];
        $total_multiple = $total > 1 ? 'Personnes' : 'Personne';
        $gtitle = $groupContacts['group_id.title'];
        $writable = &$events->first();
        $writable['group_id'] = ['data' => 
        [
              'title' => $gtitle,
              'total' => $total,
              'total_multiple' => $total_multiple,
              'group_id' => $groupId
            ]
        ];
        $allGroupes[] = $writable ;
      //   $allGroupes[] = [$events->first() => [$groupId, 'data' => [
      //     'title' => $gtitle,
      //     'total' => $total,
      //     'total_multiple' => $total_multiple,
      //     'group_id' => $groupId
      //   ]
      // ]];
        // $allGroupes[] = ['data' => $gtitle];
    }
    // Sort the array using the custom comparison function.
    usort($allGroupes, [$this, 'sortByStartDate'] );

    $result = [];
    foreach ($allGroupes as $item) {
      // dump($item, ' kkk');
        $data = $item['group_id']['data'];
        $result[] = $data;
    }
    

    return [
      '#theme' => 'my_group',
      '#cache' => ['max-age' => 0],
      '#content' => [
        'data' => $result,
      ],
    ];
  }

  // Define a custom comparison function for sorting by "start_date".
public function sortByStartDate($a, $b) {
  return strtotime($a['start_date']) - strtotime($b['start_date']);
}

}
