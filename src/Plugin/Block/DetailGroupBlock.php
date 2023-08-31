<?php

namespace Drupal\phenix_custom_block\Plugin\Block;

use Drupal\node\Entity\Node;
use \Drupal\node\NodeInterface;
use Drupal\Core\Block\BlockBase;
use Drupal\media\Entity\Media;
use Drupal\file\Entity\File;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Menu\MenuLinkTreeInterface;
use Drupal\Core\Menu\MenuTreeParameters;
use Symfony\Component\DependencyInjection\ContainerInterface;
/**
 * Provides a 'Block detail commission' block.
 *
 * @Block(
 *  id = "detail_group",
 *  admin_label = @Translation("Block detail commission"),
 *  category = @Translation("Block detail commission"),
 * )
 */
class DetailGroupBlock  extends BlockBase  {



  /**
   * {@inheritdoc}
   */
  public function build() {

    // Get the current user.
    $current_user = \Drupal::currentUser();
    $custom_service = \Drupal::service('phenix_custom_block.view_services');
    $data = [];

    \Drupal::service('civicrm')->initialize();
    $group_id = \Drupal::request()->attributes->get('civicrm_group')->id->getValue()[0]['value'];

    $data['totatl_member'] = $this->totalMembers($group_id);
    $publicTitle = $this->getGroupName($group_id)['frontend_title'];
    $defaultTitle = $this->getGroupName($group_id)['title'];
    $data['group_name'] = $publicTitle ? $publicTitle : $defaultTitle;
    $description = $this->getGroupName($group_id)['frontend_description'];
    $data['group_presentation'] = $description ? $description : '';

    $allDocuments = $this->getAllDocuments ($group_id);
    
    $allOtherDocs = $this->getAllDocs($group_id);

    foreach ($allOtherDocs as $docId) {
      $mediaObject = \Drupal::service('entity_type.manager')->getStorage('media')->load($docId);
      
      $title_doc = $custom_service->getNodeFieldValue($mediaObject, 'name');
      $allInfoDocs['first_type_de_document'] = $custom_service->getTypeDocument ($mediaObject);
      $allInfoDocs['first_element_id'] = $custom_service->getNodeFieldValue($mediaObject, 'mid');
      $date_doc = $custom_service->getNodeFieldValue($mediaObject, 'created');
      $datetime = new DrupalDateTime();
      $datetime->setTimestamp($date_doc);
  
      // Format the date using the desired format.
      $formatted_date = $datetime->format('d.m.Y');
      $allInfoDocs['date_doc'] = $formatted_date;


      $all_other_document[$title_doc][] = [
        'fileType' => $this->getFileType($mediaObject),
        'fileurl' => '',
        'size' => $this->getFileSize ($mediaObject),
        'fileId' => $this->getFile ($mediaObject)->id(),
        'type_document' => $custom_service->getTypeDocument ($mediaObject),
        'description' => $title_doc,
        'created_at' => $this->getFormattedDate($mediaObject),
        'paragraph_id' => null,
      ]; 
    
    }
    
    
    return [
      '#theme' => 'detail_group',
      '#cache' => ['max-age' => 0],
      '#content' => [
        'data' => $data,
      ],
    ];
  }

  private function getAllDocs ($groupId) {
    $db = \Drupal::database();
    $custom_service = \Drupal::service('phenix_custom_block.view_services');
    $res = $db->query('select * from media__field_groupes where field_groupes_target_id  = ' . $groupId)->fetchAll();
    $res = array_column($res, 'entity_id');
    unset($res[0]);
    return $res;
  }

  private function getAllDocuments ($groupId) {
    $allInfoDocs = [];
    $db = \Drupal::database();
    $custom_service = \Drupal::service('phenix_custom_block.view_services');
    $res = $db->query('select * from media__field_groupes where field_groupes_target_id  = ' . $groupId)->fetchAll();//TODO USE ABOVE FUNCTION
    $res = $this->getAllDocs($groupId);


    $docs = \Drupal::service('entity_type.manager')->getStorage('media')->loadMultiple($res);
    $firstDoc = reset($docs);
    if ($firstDoc) {

      $allInfoDocs['first_title'] = $custom_service->getNodeFieldValue($firstDoc, 'name');
      $allInfoDocs['first_type_de_document'] = $custom_service->getTypeDocument ($firstDoc);
      $allInfoDocs['first_element_id'] = $custom_service->getNodeFieldValue($firstDoc, 'mid');
      $date_doc = $custom_service->getNodeFieldValue($firstDoc, 'created');
      $datetime = new DrupalDateTime();
      $datetime->setTimestamp($date_doc);
      
      // Format the date using the desired format.
      $formatted_date = $datetime->format('d.m.Y');
      $allInfoDocs['date_doc'] = $formatted_date;
      $fileValue = $custom_service->getNodeFieldValue($firstDoc, 'field_media_document');
      $file = File::load($fileValue);
      $fileType = $custom_service->getNodeFieldValue($file, 'filemime');
      $fileType = $fileType =='application/pdf' ? 'pdf-3.png' : 'pdf-2.png';//todo mettre switch et ajouter tous les types de fichiers
      $allInfoDocs['type_file'] = $this->getFileType($firstDoc);

      // // Get the file size in bytes   TODO GET FILE PATH
      $file_uri = $custom_service->getNodeFieldValue($file, 'uri');
      $file_path = file_create_url($file_uri);
      $file_size_bytes = filesize($file_path);
      $file_size_bytes = round($file_size_bytes / 1024, 0);
      $allInfoDocs['file_size_readable'] = $file_size_bytes;
      $date_doc = str_replace(' ', '.', $date_doc);
      $media_extrait = $custom_service->getNodeFieldValue ($firstDoc, 'field_resume');
      $allInfoDocs['resume'] = $media_extrait;
      return $allInfoDocs;
    }
  }

  private function getFile ($media) {
    $custom_service = \Drupal::service('phenix_custom_block.view_services');
    $fileValue = $custom_service->getNodeFieldValue($media, 'field_media_document');
    $file = File::load($fileValue);
    return $file;
  }

  private function getFormattedDate($media) {
    $custom_service = \Drupal::service('phenix_custom_block.view_services');
    $date_doc = $custom_service->getNodeFieldValue($media, 'created');
    $datetime = new DrupalDateTime();
    $datetime->setTimestamp($date_doc);

    // Format the date using the desired format.
    return $datetime->format('d.m.Y');
  }

  private function getFileSize ($media) {
    $custom_service = \Drupal::service('phenix_custom_block.view_services');
    $file = $this->getFile($media);
    $file_uri = $custom_service->getNodeFieldValue($file, 'uri');
    $file_path = file_create_url($file_uri);
    $file_size_bytes = filesize($file_path);
    $file_size_bytes = round($file_size_bytes / 1024, 0);
    return $file_size_bytes;
  }
  
  private function totalMembers ($group_id) {
    return \Civi\Api4\GroupContact::get(FALSE)
      ->addSelect('COUNT(id) AS count')
      ->addWhere('group_id', '=', $group_id)
      ->addWhere('status', '!=', 'Removed')
      ->execute()->first()['count'];
  }

  private function getGroupName($group_id) {
    return \Civi\Api4\Group::get(FALSE)
      ->addSelect('title')
      ->addSelect('frontend_title')
      ->addSelect('frontend_description')
      ->addWhere('id', '=', $group_id)
      ->execute()->first();
  }

  private function getFileType ($media) {
    $custom_service = \Drupal::service('phenix_custom_block.view_services');
    $fileValue = $custom_service->getNodeFieldValue($media, 'field_media_document');
    $file = File::load($fileValue);
    $fileType = $custom_service->getNodeFieldValue($file, 'filemime');
    $fileType = $fileType =='application/pdf' ? 'pdf-3.png' : 'pdf-2.png';//todo mettre switch et ajouter tous les types de fichiers
    return $fileType;
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
