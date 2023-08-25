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
 * Provides a 'Détail réunions ' block.
 *
 * @Block(
 *  id = "detail_meetings",
 *  admin_label = @Translation("Meetings detail"),
 *  category = @Translation("Meetings detail"),
 * )
 */
class MeetingDetailBlock  extends BlockBase  {



  /**
   * {@inheritdoc}
   */
  public function build() {

    // Get the current user.
    $current_user = \Drupal::currentUser();

    // Get the current user's name.
    $current_user_name = $current_user->getAccountName();
    $service_block = \Drupal::service('phenix_custom_block.view_services');
    $request = \Drupal::request()->attributes->get('civicrm_event');
    if ($request->get('id')->getValue()) {
      $meetingId = $request->get('id')->getValue()[0]['value'];
      $allInfo = $this->getAllinfosByMeetingId($meetingId);
    }

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
      '#theme' => 'meeting_detail',
      '#cache' => ['max-age' => 0],
      '#content' => [
        'data' => $allInfo,
      ],
    ];
  }
  
  private function getAllinfosByMeetingId ($meetingId) {
    $allInfos = [];
    $events = \Civi\Api4\Event::get()
        ->addSelect('start_date', 'end_date', 'description', 'rsvpevent_cg_linked_groups.rsvpevent_cf_linked_groups', 'title')
        ->addWhere('id', '=', $meetingId)
        ->execute()->first();
    $startDate = $events['start_date'];
    $meetingTitle = $events['title'];
    $endDate = $events['end_date'];
    $description = $events['description'];
    $groupId = $events['rsvpevent_cg_linked_groups.rsvpevent_cf_linked_groups'][0];
    $groupName = \Civi\Api4\Group::get()
      ->addSelect('title')
      ->addWhere('id', '=', $groupId)
      ->execute()->first()['title'];

    // Create a DateTime object from the string.
    $startDateDatetime = new \DateTime($startDate);
    $endDateDatetime = new \DateTime($endDate);
    $atTheSameDay = $this->atTheSameDay($startDate, $endDate);

    setlocale(LC_TIME, 'fr_FR.utf8');
    // Format the DateTime object as desired.
    $startDate = strftime('%A %d %B %Y  %H:%M', $startDateDatetime->getTimestamp());
    $endDate = strftime('%A %d %B %Y  %H:%M', $endDateDatetime->getTimestamp());
    
    $meetingDate = '';
    
    if ($atTheSameDay) {
      $endDate = strftime('%H:%M', $endDateDatetime->getTimestamp());
    }

    $meetingDate = $startDate . ' - ' . $endDate;

    $allInfo = [
      'event_name' => $meetingTitle,
      'description'=> $description,
      'group_name' => $groupName,
      'meeting_date' => $meetingDate];

    return $allInfo;
  }

  private function atTheSameDay($start, $end) {
    $start = new \DateTime($start);
    $end = new \DateTime($end);
    return ($start->format('Y-m-d') === $end->format('Y-m-d'));
  }

}
