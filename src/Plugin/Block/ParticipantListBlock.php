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
 * Provides a 'Liste participants ' block.
 *
 * @Block(
 *  id = "list_participant",
 *  admin_label = @Translation("Block liste participant"),
 *  category = @Translation("Block  liste participants"),
 * )
 */
class ParticipantListBlock  extends BlockBase  {



  /**
   * {@inheritdoc}
   */
  public function build() {

    // Get the current user.
    $current_user = \Drupal::currentUser();

    $data = [];

    // dump('qmdkf');
    \Drupal::service('civicrm')->initialize();
    
    $all_participants = $this->getParticipants(984);
    
    foreach ($all_participants as $participant) {
      $contact_id = $participant->civicrm_contact_id;
      $contacts = \Civi\Api4\Contact::get(FALSE)
        ->addSelect('first_name', 'last_name', 'gender_id:label', 'employer_id.legal_name', 'addressee_display')
        ->addWhere('id', '=', $contact_id)
        ->execute()->getIterator();
      $contacts = iterator_to_array($contacts); 
      $data[] = ['full_name' => $contacts[0]['addressee_display'],  'society' => $contacts[0]['employer_id.legal_name']];       
    }

    return [
      '#theme' => 'list_participant',
      '#cache' => ['max-age' => 0],
      '#content' => [
        'data' => $data,
      ],
    ];
  }
  
  /**
   * Get all participants
   */
  private function getParticipants ($id) {
     $query = 'SELECT "civicrm_contact"."last_name" AS "civicrm_contact_last_name"
     , "civicrm_event"."id" AS "id", "event_id_civicrm_event"."id" AS "event_id_civicrm_event_id", "civicrm_contact"."id" AS "civicrm_contact_id", "civicrm_participant_status_type_civicrm_participant"."id" AS "civicrm_participant_status_type_civicrm_participant_id"
    FROM
    {civicrm_event} "civicrm_event"
    LEFT JOIN civicrm_participant "event_id_civicrm_event" ON civicrm_event.id = event_id_civicrm_event.event_id
    LEFT JOIN civicrm_contact "civicrm_contact" ON event_id_civicrm_event.contact_id = civicrm_contact.id
    LEFT JOIN civicrm_participant_status_type "civicrm_participant_status_type_civicrm_participant" ON event_id_civicrm_event.status_id = civicrm_participant_status_type_civicrm_participant.id
    WHERE ((civicrm_event.id = ' . $id . ')) AND ("civicrm_contact"."is_deleted" <> 1)
    ORDER BY "civicrm_contact_last_name" ASC'; 


     return \Drupal::database()->query($query)->fetchAll();
  }

}
