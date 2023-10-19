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
 *  id = "detail_meet_all_document",
 *  admin_label = @Translation("Block document detail reunion"),
 *  category = @Translation("Block document detail reunion"),
 * )
 */
class DetailMeetDocumentBlock  extends BlockBase  {



  /**
   * {@inheritdoc}
   */
  public function build() {

    // Get the current user.
    $current_user = \Drupal::currentUser();
    $custom_service = \Drupal::service('phenix_custom_block.view_services');
    $data = [];
    \Drupal::service('page_cache_kill_switch')->trigger();
    \Drupal::service('civicrm')->initialize();
    if (\Drupal::request()->attributes->get('civicrm_event')) {

      $eventId = \Drupal::request()->attributes->get('civicrm_event')->id->getValue()[0]['value'];
      
      // Load the CiviCRM event by its ID.
      $allDocuments = $this->getAllDocuments ($eventId);
     
    $allOtherDocs = $this->getAllDocs($eventId, true);

    foreach ($allOtherDocs as $docId) {
      $mediaObject = \Drupal::service('entity_type.manager')->getStorage('media')->load($docId);
      if ($mediaObject) {

        $title_doc = $custom_service->getNodeFieldValue($mediaObject, 'field_titre_public') ? $custom_service->getNodeFieldValue($mediaObject, 'field_titre_public') : $custom_service->getNodeFieldValue($mediaObject, 'name');
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
          'media_id' => $mediaObject->id(),
          'type_document' => $custom_service->getTypeDocument ($mediaObject),
          'description' => $title_doc,
          'created_at' => $this->getFormattedDate($mediaObject),
          'paragraph_id' => null,
          'filiere' => $custom_service->getFiliereLabels($mediaObject)
        ]; 
      }
    
    } 
    if (!$allDocuments) {//s'il n'y a aucun document on return
      return;
    }

    $allowToEdit = $custom_service->checkIfUserCanEditDoc ();
    
    return [
      '#theme' => 'document_detail_meet',
      '#cache' => ['max-age' => 0],
      '#content' => [
        'data' => $all_other_document,
        'first_title' => 'Documents',
        'first_type_de_document' => $allDocuments['first_type_de_document'],
        'resume' => $allDocuments['resume'],
        'file_type' =>  $allDocuments['type_file'],
        'file_size' => $allDocuments['file_size_readable'],
        'date_doc' => $allDocuments['date_doc'],
        'first_element_id' => $allDocuments['first_element_id'],
        'first_element_title' => $allDocuments['first_title'],
        'display_see_other_doc' => count($allOtherDocs),
        'is_page_last_doc' => false,
        'event_id' => 991,
        'can_edit_doc' => $allowToEdit,
        'filiere' => $custom_service->getFiliereLabels($mediaObject),
      ],
    ];
  }
  }

  private function getAllDocs ($groupId, $notIncludeFirstDoc) {
    $db = \Drupal::database();
    $custom_service = \Drupal::service('phenix_custom_block.view_services');
    $res = $db->query('select field_documents_target_id from civicrm_event__field_documents where entity_id  = ' . $groupId)->fetchAll();
    $res = array_column($res, 'field_documents_target_id');

    $res = $custom_service->sortTermIdByDateCreation($res);

    
    $res = $custom_service->skipDocSocial($res);

    

    if ($notIncludeFirstDoc) {
      unset($res[0]);
    }
    return $res;
  }

  private function getAllDocuments ($groupId): array {
    $allInfoDocs = [];
    $db = \Drupal::database();
    $custom_service = \Drupal::service('phenix_custom_block.view_services');
    $res = $db->query('select field_documents_target_id from civicrm_event__field_documents where entity_id  = ' . $groupId)->fetchAll();//TODO USE ABOVE FUNCTION
    $res = $this->getAllDocs($groupId, false);
    
    $docs = \Drupal::service('entity_type.manager')->getStorage('media')->loadMultiple($res);
    $firstDoc = reset($docs);

    if ($firstDoc) {

      $allInfoDocs['first_title'] = $custom_service->getNodeFieldValue($firstDoc, 'name');
      $allInfoDocs['first_type_de_document'] = $custom_service->getTypeDocument ($firstDoc);
      $allInfoDocs['first_element_id'] = $custom_service->getNodeFieldValue($firstDoc, 'mid');
      $date_doc = $custom_service->getNodeFieldValue($firstDoc, 'created');
      $allInfoDocs['filiere'] = $custom_service->getFiliereLabels($firstDoc);
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
    }

    // dump($allInfoDocs, ' what');
    return $allInfoDocs;
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
    return \Civi\Api4\GroupContact::get(false)
      ->addSelect('COUNT(id) AS count')
      ->addWhere('group_id', '=', $group_id)
      ->addWhere('status', '!=', 'Removed')
      ->execute()->first()['count'];
  }

  private function getGroupName($group_id) {
    return \Civi\Api4\Group::get(false)
      ->addSelect('title')
      ->addWhere('id', '=', $group_id)
      ->execute()->first()['title'];
  }

  private function getFileType ($media) {
    $custom_service = \Drupal::service('phenix_custom_block.view_services');
    $fileValue = $custom_service->getNodeFieldValue($media, 'field_media_document');
    $file = File::load($fileValue);
    $fileType = $custom_service->getNodeFieldValue($file, 'filemime');
    $fileType = $fileType =='application/pdf' ? 'pdf-3.png' : 'pdf-2.png';//todo mettre switch et ajouter tous les types de fichiers
    return $fileType;
  }

  private function getGroupPresentation ($group_id) {
    return "Lorem Ipsum is simply dummy text of the printing and typesetting industry. 
    Lorem Ipsum has been the industry's standard dummy text ever since the 1500s, 
    when an unknown printer took a galley of type and scrambled it to make a type specimen book. ";
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
