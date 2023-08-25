<?php


namespace Drupal\phenix_custom_block;

use Drupal\media\Entity\Media;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\taxonomy\Entity\Term;
use Drupal\file\Entity\File;
/**
 * Class PubliciteService
 * @package Drupal\phenix_custom_block\Services
 */
class CustomBlockServices {
    /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The config factory service.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager service.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   The config factory service.
   */
  public function __construct(EntityTypeManagerInterface $entityTypeManager, ConfigFactoryInterface $configFactory) {
    $this->entityTypeManager = $entityTypeManager;
    $this->configFactory = $configFactory;
  }
    /**
     * Recupère la total de membre dans un groupe
     */
    public function getTotalGroupMember ($group_id)  {
        \Drupal::service('civicrm')->initialize(); 
        $groupContacts = \Civi\Api4\GroupContact::get(FALSE)
          ->addSelect('COUNT(group_id) AS count')
          ->addWhere('group_id', '=', $group_id)
          ->addWhere('status', '=', 'Added')
          ->execute()->getIterator();
        $groupContacts = iterator_to_array($groupContacts); 
        return $groupContacts;
    }

    /**
     * Permet de recuperer tous mes groupes
     */
    public function getAllMyGroup ($cid) {
        $query = "SELECT
            civicrm_group_civicrm_group_contact.id AS civicrm_group_civicrm_group_contact_id,
            civicrm_group_civicrm_group_contact.title AS civicrm_group_civicrm_group_contact_title,
            civicrm_group_civicrm_group_contact.frontend_title AS civicrm_group_civicrm_group_contact_frontend_title,
            civicrm_group_civicrm_group_contact.parents AS civicrm_group_civicrm_group_contact_parents,
            civicrm_group_civicrm_group_contact.name AS civicrm_group_civicrm_group_contact_name,
            civicrm_group_civicrm_group_contact.group_type AS civicrm_group_civicrm_group_contact_group_type,
            MIN(civicrm_contact.id) AS id,
            MIN(users_field_data_civicrm_uf_match.uid) AS users_field_data_civicrm_uf_match_uid,
            MIN(civicrm_contact_civicrm_uf_match.id) AS civicrm_contact_civicrm_uf_match_id,
            MIN(civicrm_group_civicrm_group_contact.id) AS civicrm_group_civicrm_group_contact_id_1
        FROM
            civicrm_contact
            LEFT JOIN civicrm_uf_match civicrm_uf_match ON civicrm_contact.id = civicrm_uf_match.contact_id
            LEFT JOIN users_field_data users_field_data_civicrm_uf_match ON civicrm_uf_match.uf_id = users_field_data_civicrm_uf_match.uid
            LEFT JOIN civicrm_uf_match users_field_data_civicrm_uf_match__civicrm_uf_match ON users_field_data_civicrm_uf_match.uid = users_field_data_civicrm_uf_match__civicrm_uf_match.uf_id
            LEFT JOIN civicrm_contact civicrm_contact_civicrm_uf_match ON users_field_data_civicrm_uf_match__civicrm_uf_match.contact_id = civicrm_contact_civicrm_uf_match.id
            LEFT JOIN civicrm_group_contact civicrm_group_contact ON civicrm_contact.id = civicrm_group_contact.contact_id AND civicrm_group_contact.status = 'Added'
            LEFT JOIN civicrm_group civicrm_group_civicrm_group_contact ON civicrm_group_contact.group_id = civicrm_group_civicrm_group_contact.id
        WHERE
            (civicrm_group_civicrm_group_contact.group_type LIKE '%3%')
            AND (civicrm_group_civicrm_group_contact.is_active = '1')
            AND civicrm_contact.id = $cid
        GROUP BY
            civicrm_group_civicrm_group_contact_id,
            civicrm_group_civicrm_group_contact_title,
            civicrm_group_civicrm_group_contact_frontend_title,
            civicrm_group_civicrm_group_contact_parents,
            civicrm_group_civicrm_group_contact_name,
            civicrm_group_civicrm_group_contact_group_type
        ORDER BY
            civicrm_group_civicrm_group_contact_parents ASC,
            civicrm_group_civicrm_group_contact_name ASC limit 3
        "; 

        $results =  \Drupal::database()->query($query)->fetchAll();

        return $results;
    }


    private function getAllEventId () {
      $query = "SELECT
        Event.start_date AS event_start_date,
        civicrm_contact.id AS id,
        Event.id AS event_id, 
        Event.title as event_title
      FROM
        civicrm_contact
      INNER JOIN civicrm_event AS Event ON civicrm_contact.id = Event.created_id
      WHERE
      DATE_FORMAT(
              (Event.start_date  + INTERVAL 7200 SECOND),
              '%Y-%m-%dT%H:%i:%s'
          ) >= DATE_FORMAT(
              (NOW() + INTERVAL 7200 SECOND),
              '%Y-%m-%dT%H:%i:%s'
          )
        -- (DATE_FORMAT((Event.start_date + INTERVAL 7200 SECOND), '%Y-%m-%dT%H:%i:%s') >= DATE_FORMAT((NOW() + INTERVAL 7200 SECOND), '%Y-%m-%dT%H:%i:%s'))
        AND
         (Event.is_active = '1')
      ";

        $results =  \Drupal::database()->query($query)->fetchAll();
        return $results;
    } 

    /**
     * Recupère tous les reunions à venir
     * 
     */
    public function getAllMeetings ($cid) {
       /*  $query = "SELECT
        Event.start_date AS event_start_date,
        civicrm_contact.id AS id,
        Event.id AS event_id, Event.title as event_title
      FROM
        civicrm_contact
      INNER JOIN civicrm_event AS Event ON civicrm_contact.id = Event.created_id
      WHERE
        -- (DATE_FORMAT((Event.start_date + INTERVAL 7200 SECOND), '%Y-%m-%dT%H:%i:%s') >= DATE_FORMAT((NOW() + INTERVAL 7200 SECOND), '%Y-%m-%dT%H:%i:%s'))
        -- AND
         (Event.is_active = '1')  AND civicrm_contact.id = $cid  order by start_date limit 3
      "; */


      $isAllowedMeeting = $this->checkIfContactIsInsideAGroup($cid);
      
      // Use the ArrayFilter class to remove false values
      $isAllowedMeeting = $this->removeFalseValues($isAllowedMeeting);
      $isAllowedMeeting = array_keys($isAllowedMeeting);
      if ($isAllowedMeeting) {
        $isAllowedMeeting = implode(', ', $isAllowedMeeting);
        
        $query = "SELECT
      `created_id_civicrm_contact`.`start_date` AS `event_start_date`,
      `created_id_civicrm_contact`.`title`  as event_title,
      `civicrm_contact`.`id` AS `id`,
      `created_id_civicrm_contact`.`id` AS `created_id_civicrm_contact_id`
  FROM
      `civicrm_contact`
  INNER JOIN
      `civicrm_event` AS `created_id_civicrm_contact` ON `civicrm_contact`.`id` = `created_id_civicrm_contact`.`created_id`
  WHERE
      (
          DATE_FORMAT(
              (`created_id_civicrm_contact`.`start_date` + INTERVAL 7200 SECOND),
              '%Y-%m-%dT%H:%i:%s'
          ) >= DATE_FORMAT(
              (NOW() + INTERVAL 7200 SECOND),
              '%Y-%m-%dT%H:%i:%s'
          )
      )
      AND
      (`created_id_civicrm_contact`.`is_active` = '1')  AND `created_id_civicrm_contact`.`id` IN (" . $isAllowedMeeting . ")   ORDER BY
      `event_start_date` ASC limit 3;
  ";
      $results =  \Drupal::database()->query($query)->fetchAll();
      
    }
     
      return $results;
    }

    /**
     * 
     */
    private function checkIfContactIsInsideAGroup ($cid) {

      $allEvent = $this->getAllEventId();
      $contactInsideAgroup = [];
      foreach($allEvent as $event) {
        $event_id = $event->event_id;
        if ($event_id) {

          $events = \Civi\Api4\Event::get()
          ->addSelect('rsvpevent_cg_linked_groups.rsvpevent_cf_linked_groups')
          ->addWhere('id', '=', $event_id)
          ->execute();
          if ($events) {
            
            $eventGroupId = $events->getIterator();
            $eventGroupId = iterator_to_array($eventGroupId);  
            foreach ($eventGroupId as $group_id) {
              $allContactId = \Civi\Api4\GroupContact::get()
              ->addSelect('contact_id')
              ->addWhere('group_id', '=', $group_id['rsvpevent_cg_linked_groups.rsvpevent_cf_linked_groups'][0])
              ->execute()->getIterator();
              $allContactId = iterator_to_array($allContactId);  
              $allContactId = array_column($allContactId, 'contact_id');
              $contactInsideAgroup[$event_id] = in_array($cid, $allContactId);
            }
            
          }
        }
      }

      return $contactInsideAgroup;
    }

  public function removeFalseValues($array) {
    return array_filter($array, function ($value) {
        return $value !== false;
    });
  }

    
  public function getContactIdByEmail ($email) {
    $db = \Drupal::database();
    if ($email) {
      return $db->query("select contact_id from civicrm_email where email = '" . $email . "'")->fetch()->contact_id;
    }
    return false;
  }
    /**
     * Permet de récupérer le jour/mois/année heure:minute
     * @return array()
     */
    public function formatDateWithMonthInLetterAndHours ($start_date) {
        // Create a DateTime object from the date string
        $dateTime = new \DateTime($start_date);
    
        // Get the day
        $day = $dateTime->format('d');

        // Get the month
        $month = $dateTime->format('m');
        // Obtient le mois en français
        setlocale(LC_TIME, 'fr_FR.utf8');
        $month = strftime('%B', $dateTime->getTimestamp());

        // Get the year
        $year = $dateTime->format('Y');
        
        // Get the hour
        $hour = $dateTime->format('H');

        // Get the minute value.
        $minute = $dateTime->format('i');

        return [
            'day' => $day, 
            'month' => $month, 
            'year' => $year,
            'hour' => $hour,
            'minute' => $minute
        ];
    }

    /**
     * Personnalisation de la page Commission --> bloc reunion
     */
    public function customizeViewReunionOfTheCommissionPage(&$var) {
      $view = $var['view'];
      $field = $var['field'];
      $requests = \Drupal::request();
      $row = $var['row'];

      if ($field->field == 'title' ) {
        $current_id = $var['row']->id;
        $start_date = $row->civicrm_event_start_date;
        $start_date = $this->formatDateWithMonthInLetterAndHours($start_date);
        $value = $field->getValue($row);
        $classOddAndEven = 'odd';
        if ($view->current_display == 'block_1') {
          $classOddAndEven = 'even';
        }

        $var['output'] = [
          '#theme' => 'phenix_custom_block_alter_view_detail_commission_reunion',
          '#cache' => ['max-age' => 0],
          '#content' => [
            'start_date' => $start_date,
            'event_id' => $current_id,
            'class_odd_even' => $classOddAndEven,
            'title' => $value
          ]
        ];
      }
    }

    public function getLinkedGroupWithEvent ($eventId) {
        $events = \Civi\Api4\Event::get(FALSE)
            ->addSelect('rsvpevent_cg_linked_groups.rsvpevent_cf_linked_groups')
            ->addWhere('id', '=', $eventId)
            ->execute()->getIterator();
        $events = iterator_to_array($events);   
        $group_ids = array_column($events, 'rsvpevent_cg_linked_groups.rsvpevent_cf_linked_groups');
        $data_groups = [];
        foreach ($group_ids[0] as $group_id) {
            $group_name = \Civi\Api4\Group::get(FALSE)
            ->addSelect('title')
            ->addWhere('id', '=', $group_id)
            ->execute()->first();
            
            $data_groups[$eventId] .= $group_name['title'] . ' - ';
        }
        


        return $data_groups;
    }
    
    public function getNodeFieldValue ($node, $field) {
        $value = '';
        $getValue = $node->get($field)->getValue();
        if (!empty($getValue)) {
          if (isset($getValue[0]['target_id'])) { //For entity reference (img / taxonomy ...)
            $value = $getValue[0]['target_id'];
          }elseif (isset($getValue[0]['value']))  { //For simple text / date
            $value = $getValue[0]['value'];
          }else if(isset($getValue[0]['uri'])) {
            $value = $getValue[0]['uri'];
          }else { //other type of field
            $value = $getValue['x-default'];
          }
        }
        return $value;
      }
      
      public function getTypeDocument ($media) {
        if (!$media) {
          return null;
        }
        $type_doc = '';
        $type_doc_value = $this->getNodeFieldValue($media, 'field_type_de_document');
        if ($type_doc_value != 18) {
          $type_doc = $media->get('field_type_de_document')->getFieldDefinition()->getItemDefinition()->getSettings()['allowed_values'][$type_doc_value];
        }
        return $type_doc;
      }
      
      public function getTypeDocumentWithAutre ($media) {
        if (!$media) {
          return null;
        }
        $type_doc = '';
        $type_doc_value = $this->getNodeFieldValue($media, 'field_type_de_document');
        // if ($type_doc_value != 18) {
          $type_doc = $media->get('field_type_de_document')->getFieldDefinition()->getItemDefinition()->getSettings()['allowed_values'][$type_doc_value];
        // }
        return $type_doc;
      }



      /**
       * Gabarit text + image
       */
      public function allDataTxtImg (&$var) {
        $data = '';
        $storage = $this->entityTypeManager->getStorage('paragraph');
        $term = $var['elements']['#taxonomy_term'];
        $data .= $this->getNodeFieldValue($term, 'description');
        $field_dossier = $term->get('field_dossier')->getValue();
        if ($field_dossier) {
          $all_dossier = array_column($field_dossier, 'target_id');
          $paragraphs = $storage->loadMultiple($all_dossier);
          $is_odd = 'odd';
          $counter = 0;
          foreach ($paragraphs as $paragraph) {
            
            if ($paragraph->hasField('field_video')) {//Si de type video
              $this->getVideoHtml ($paragraph, $data);
            }elseif ($paragraph->hasField('field_image_media')) {//Si de type image
              $this->getImageHtml ($paragraph, $data);              
            }elseif ($paragraph->hasField('field_document')) {//Si de type document
              $this->getDocumentHtml($paragraph, $data, $var);
            }elseif ($paragraph->hasField('field_texte_formate')) {//Si de type texte formatté
              $this->getFormattedTexttHtml($paragraph, $data);
            }elseif ($paragraph->hasField('field_lien')) {//Si de type liste de liens
              $this->getLinkHtml($paragraph, $data, $counter);
              
              // dump($paragraph);
            }
           
          }
        }
      
        return $data;
      }


  /**
   * Load a video by its ID.
   *
   * @param int $video_id
   *   The ID of the video to load.
   *
   * @return \Drupal\media\Entity\Media|null
   *   The loaded Media entity representing the video or NULL if not found.
   */
  public function load_video_by_id($video_id) {
    // Load a single video by its ID.
    return Media::load($video_id);
  }

  /**
   * Permet de recuperer l'html qui doit être rendu pour les textes formattés
   */
  public function getFormattedTexttHtml ($paragraph, &$data) {
    $formattedText = $paragraph->get('field_texte_formate')->getValue();
    $hasTable = strpos($formattedText[0]['value'], '<table>') !== false ? true : false;
    $class_for_table = '';
    if ($hasTable) {
      $class_for_table = strpos($formattedText[0]['value'], '<th><img') !== false ? 'img-txt-side-by-side' : 'table-only';
    }

    //check if the formatted text is faq
    $isFaq = $this->checkIfFaqAndEditHtml($formattedText[0]['value'], $data);
    if ($isFaq) {
      return $data .= '<div class="formatted-text ' . $class_for_table . '">' . $formattedText[0]['value'] . '</div>';
    }

    $data .= '<div class="formatted-text ' . $class_for_table . '">' . $formattedText[0]['value'] . '</div>';
    return $data;
  }

  /**
   * Permet de recuperer l'html qui doit être rendu pour les liste de liens
   */
  public function getLinkHtml ($paragraph, &$data, $counter) {
    $data .= '<a href="'. $this->getNodeFieldValue($paragraph, 'field_lien') . '" class="link-custom" ><i class="fas fa-external-link-alt custom-link-font-awesome"></i>' . $this->getNodeFieldValue($paragraph, 'field_lien') . '</a>';
  }

  /**
   * Permet de recuperer l'html qui doit être rendu pour les documents
   */
  public function getDocumentHtml ($paragraph, &$data, &$var) {
    $documents = $paragraph->get('field_document')->getValue();
    $documents_ids = array_column($documents, 'target_id');
    $all_doc_info = [];
    $paragraphId = $paragraph->id();
    
    foreach ($documents_ids as $document_id) {
      $media = Media::load($document_id);
      if ($media) {
        $title = $this->getNodeFieldValue ($media, 'name') ?: $this->getNodeFieldValue($media, 'field_titre_public');
        $file_id = $this->getNodeFieldValue($media, 'field_media_document');
        $all_doc_info[$file_id]['title'] = $title;
        $all_doc_info[$file_id]['media_id'] = $media->id();
        $file_info = File::load($file_id);
        
        $file_type = $this->getNodeFieldValue($file_info, 'filemime');
        $file_type = $file_type == 'application/pdf' ? 'pdf-3.png' : 'pdf-2.png';//todo mettre switch et ajouter tous les types de fichiers
        $all_doc_info[$file_id]['file_type'] = $file_type;
        
        $type_de_document = $this->getTypeDocument ($media);
        $all_doc_info[$file_id]['type_de_document'] = $type_de_document;
        
         // // Get the file size in bytes
        $file_url = $this->getNodeFieldValue($file_info, 'uri');
        // TODO "public://documents/2628.pdf"
        $file_size_bytes = filesize('/var/aegir/platforms/civicrm-d9/' . $file_url);
        
        // // Convert the size to a human-readable format
        $file_size_readable = round($file_size_bytes / 1024, 0); 
        // dump($file_size_readable)
        $date_doc_timestamp = $this->getNodeFieldValue($file_info, 'created');
        $date_doc = $this->convertTimesptamToDate($date_doc_timestamp);
        $all_doc_info[$file_id]['created_at'] = $date_doc;
      }
    }

    $var['last_doc'] = [
      '#theme' => 'phenix_custom_block_last_doc_txt_img',
      '#cache' => ['max-age' => 0],
      '#content' => [
        'data' => $all_doc_info,
        'paragraph_id' => $paragraphId,
      ]
    ]; 
    $data .= render($var['last_doc']);
    return $data;
  }

 /**
  * Convert timestamp to date (d.m.Y)
  * @param int $timestamp
  *   The Unix timestamp.
  *
  * @return string
  *   The formatted date string.
  */
  private function convertTimesptamToDate($timestamp) {
    $format = 'd.m.y';
    // Create a new DrupalDateTime object using the timestamp.
    $date = DrupalDateTime::createFromTimestamp($timestamp);

    // Format the date using the desired format.
    $formatted_date = $date->format($format);
    return $formatted_date;
  }

  private function getYearFromTimestamp($timestamp) {
    $format = 'Y';
    // Create a new DrupalDateTime object using the timestamp.
    $date = DrupalDateTime::createFromTimestamp($timestamp);

    // Format the date using the desired format.
    $year = $date->format($format);
    return $year;
  }


  public function convertTimestampToDateDMYHS ($timestamp) {
    // Convert the timestamp to a formatted date and time string.
    $date_format = 'l d/m/Y - H:i'; // Define your desired date and time format.
    return \Drupal::service('date.formatter')->format($timestamp, 'custom', $date_format);

  }

  /**
   * Retourne le renderable html d'image
   */
  public function getImageHtml ($paragraph, &$data) {
    if ($paragraph) {

      $image_media = $this->getNodeFieldValue($paragraph, 'field_image_media');
      $media_entity = Media::load($image_media);
      if ($media_entity) {

        $image_field = $media_entity->get('field_media_image');
        
        // Get the first item from the field (assuming it's a single-value field).
        $image_item = $image_field->first();
        
        // Render the image using Drupal's render system.
        $image_render_array = $image_item->view([
          'type' => 'image', // Replace with the desired image style, if any.
          'settings' => [
            // 'image_style' => 'thumbnail', // Replace with the desired image style, if any.
          ],
        ]);
        
        $data  .= '<div class="img-html-bloc">' . render($image_render_array)->__toString() . '</div>';
        return $data;
      }
  }
}

  /**
   * return $data contenant l'html de la video
   */
  private function getVideoHtml ($paragraph, &$data) {
    $video_id = $this->getNodeFieldValue($paragraph, 'field_video');
    $video = $this->load_video_by_id($video_id);

    // Use the 'full' view mode to render the media entity.
    $view_builder = $this->entityTypeManager->getViewBuilder('media');
    $video_render_array = $view_builder->view($video, 'full');
    $data .= '<div class="text-img-video"> ' . render($video_render_array)->__toString() . '</div>';
    return $data;
  }

  private function checkIfFaqAndEditHtml(&$text, &$data) {
    if (strpos($text, 'equently asked question') !== false ? true : (strpos($text, 'foire aux question') !== false)) {
      preg_match_all('/<h4><strong>[0-9a-z\'?<> &;="-_éèùîôÉÔ]+<\/h4>/', $text, $matches);
      $last_element = count($matches[0]) - 1;
      
      foreach ($matches[0] as $key => $match) {

        switch($key) {
          case 0:
            $text_match = str_replace('<h4><strong>', '<div class="middle faq-dropdown"><h4><strong>', $match);
            $text = str_replace($match, $text_match, $text);
            break;
          case $last_element:
            $text_match = str_replace('<h4><strong>', '</div><div class="ttt"><h4><strong>', $match);
            $text = str_replace($match, $text_match, $text);
          default :
            $text_match = str_replace('<h4><strong>', '</div><div class=" middle faq-dropdown"><h4><strong>', $match);
            $text = str_replace($match, $text_match, $text);
            break;
        }
      } 
    }

    return $text;
  }


public function isRubriqueWithTxtAndImg ($term_id) {

  $term = Term::load($term_id);
  $isRubriqueWithImgAndTxt = $this->getNodeFieldValue($term, 'field_taxonomy_views_integrator_');
  return $isRubriqueWithImgAndTxt;
}

public function hasChildren ($term_id) {
  // Replace 'taxonomy_vocabulary_machine_name' with the actual machine name of your vocabulary.
  $vid = 'rubrique';

  // Replace 'term_tid' with the actual term ID you want to check.
  $tid = $term_id;
  
  $term = Term::load($term_id);

  if ($term) {
    $vid = $term->bundle();
    $children = \Drupal::entityTypeManager()
        ->getStorage('taxonomy_term')
        ->loadChildren($tid, $vid);
      
    if (!empty($children)) {
        return true;
    } else {
        return false;
    }
  }

}

/**
 * Recupère la date de création d'un fichier
 */
public function getCreatedDocument ($file) {
  $fileId = $file->id();
  if ($file) {
    $file_created_at = getNodeFieldValue ($file, 'created');
    return date('d m Y', $file_created_at);
  }
}

public function getFileTypeExtension ($file_type) {
  return $file_type == 'application/pdf' ? 'pdf-3.png' : 'pdf-2.png';
}

/**
 * Recupère les information sur le premièr document qui sera mis en evidence (document lié au terme)
 */
public function getAllDataForDocumentLieAuxTermeFirstElement (&$var) {
  
  $data = [];
  $db = \Drupal::database();
  $term_object = $var['elements']['#taxonomy_term'];
  $term_object_id = $this->getNodeFieldValue($term_object, 'tid');
  $term_object = Term::load($term_object_id);
  $term_name = $this->getNodeFieldValue($term_object, 'name');
  $string_query = 'select entity_id from media__field_tags where field_tags_target_id = ' . $term_object_id;
  $all_linked_doc = $db->query($string_query)->fetchAll();
  if ($all_linked_doc) {
    $all_linked_doc = array_column($all_linked_doc, 'entity_id');


    //sort by created 
    $entityTypeManager = \Drupal::entityTypeManager();
    $query = $entityTypeManager->getStorage('media')->getQuery()
      ->condition('mid', $all_linked_doc, 'IN')
      ->sort('created', 'DESC') // Sort by creation date in descending order
      ->range(0, count($all_linked_doc)); // Limit the results to the specified media IDs
      
    $media_entities = $query->execute();

    $media_entities = $entityTypeManager->getStorage('media')->loadMultiple($media_entities);





    // $media_entities = \Drupal::entityTypeManager()->getStorage('media')->loadMultiple($all_linked_doc);
    
    if (count($media_entities) > 1) {
      $first_doc = reset($media_entities);
      $created_at = $this->getNodeFieldValue($first_doc, 'created');
      $document_year = $this->getYearFromTimestamp($created_at);
      $first_doc_title = $this->getNodeFieldValue($first_doc, 'name');
      $first_doc_type_doc = $this->getTypeDocument ($first_doc);
      $first_doc_extrait = $this->getNodeFieldValue ($first_doc, 'field_resume');
      $first_doc_file = $this->getNodeFieldValue ($first_doc, 'field_media_document');
      $file_object = File::load($first_doc_file);
      $first_doc_created_at = $this->getCreatedDocument($file_object);
      $file_type = $this->getNodeFieldValue($file_object, 'filemime');
      $first_doc_img_file = $this->getFileTypeExtension($file_type);
      $first_doc_file_url = $this->getNodeFieldValue($file_object, 'uri');
      $first_doc_file_size = filesize($first_doc_file_url);
      $first_doc_file_size = round($first_doc_file_size / 1024, 0);
      $first_doc_id = $first_doc->id();
      $display_see_other_doc = true;
      
      $allOtherDoc = array_shift($media_entities);


      return $var['content'] = [
        '#theme' => 'phenix_custom_block_last_doc_automatique',
        '#cache' => ['max-age' => 0],
        '#content' => [
          'data' => $this->getAllOtherDocInfo ($media_entities, $term_name),
          'first_element' => 'tttt',
          'first_title' => $first_doc_title,
          'first_type_de_document' => $first_doc_type_doc,
          'resume' => $first_doc_extrait,
          'file_type' => $first_doc_img_file,
          'file_size' => $first_doc_file_size,
          'date_doc' => $first_doc_created_at,
          'first_element_id' => $first_doc_id,
          'first_element_title' => $first_doc_title,
          'display_see_other_doc' => $display_see_other_doc,
          'term_name' => $term_name,
          'document_year' => $document_year,
          'is_page_last_doc' => true,
        ]
      ];
    } 
  }
}
 
private function getAllOtherDocInfo ($allDoc, $termName) {
  $all_documents = [];
  foreach($allDoc as $doc) {
    $created_at = $this->getNodeFieldValue($doc, 'created');
    $document_year = $this->getYearFromTimestamp($created_at);
    $file = $this->getNodeFieldValue ($doc, 'field_media_document');
    $file_object = File::load($file);
    $file_type = $this->getNodeFieldValue($file_object, 'filemime');
    $first_doc_img_file = $this->getFileTypeExtension($file_type);
    $first_doc_file_url = $this->getNodeFieldValue($file_object, 'uri');
    $first_doc_file_size = filesize($first_doc_file_url);
    $first_doc_file_size = round($first_doc_file_size / 1024, 0);
    $media_name = $this->getNodeFieldValue($doc, 'name');
    $type_doc = $this->getTypeDocument ($doc);
     $all_documents[$media_name][] = [
        'fileType' => $first_doc_img_file,
        'fileurl' => $first_doc_file_url,
        'size' => $first_doc_file_size,
        'fileId' => $file,
        'type_document' => $type_doc,
        'description' => $media_name,
        'document_year' => $document_year,
        'created_at' => $this->convertTimesptamToDate($created_at),
        'paragraph_id' => '',
        'term_name' => $termName,
      ]; 
  }

  return $all_documents;
      
}

/**
 * Personnaliser l'affichage des resultat de recherche (search api)
 */
public function customResultSearchDoc (&$var) {
  $field = $var['field'];
  $view = $var['view'];
	$row = $var['row'];
  $value = $field->getValue($row);
  $entity = $var['row']->_entity;
  
  if ($field->field == 'rendered_item') {
    $var['output'] = ['#markup' => '<span class="empty-td"></span>'];
  }
  if ($field->field == 'body') {
    $var['output'] = ['#markup' => '<span class="empty-td"></span>'];
  }
  if ($field->field == 'title') {
    $var['output'] = ['#markup' => '<span class="empty-td"></span>'];
  }
  if ($field->field == 'name') {
    $var['output'] = ['#markup' => '<span class="empty-td"></span>'];
  }
  if ($field->field == 'description') {
    $var['output'] = ['#markup' => '<span class="empty-td"></span>'];
  }
  if ($field->field == 'name_1') {
    $var['output'] = ['#markup' => '<span class="empty-td"></span>'];
  }
  // $type_doc = $custom_service->getNodeFieldValue($entity, 'field_type_de_document');
  // dump('qsmdlkf', $entity->hasField('field_type_de_document'), $entity);
  
  if($field->field == 'name') {
    if ($entity->hasField('field_type_de_document')) {
      $published_on = $this->getNodeFieldValue($entity, 'created');
      $convertedDate = $this->convertTimestampToDateDMYHS($published_on);
      $title = $this->getNodeFieldValue($entity, 'name');
      $resume = $this->getNodeFieldValue($entity, 'field_resume');
      $filieres = $this->getNodeFieldValue($entity, 'field_filieres');
      $label = '';
      if ($filieres) {
        foreach(json_decode($filieres) as $filiere) {
          $label .= $this->getFiliereLabelById($filiere->id)['label'] . ', ';
          
        }  
      }
    
      $type_doc = $entity->get('field_type_de_document')->getValue()[0]['value'];
      $libelle = $this->getTypeDocumentWithAutre($entity);
      
      if ($libelle) {
        $doc_info = [
          '#theme' => 'phenix_custom_bloc_search',
          '#cache' => ['max-age' => 0],
          '#content' => [
            'title' => $value,
            'resume' => $resume,
            'type_document' => $libelle,
            'filiere' => rtrim($label, ', '),
            'published_on' => $convertedDate,
            'media_id' => $entity->id()
            ]
        ];
        $var['output'] = $doc_info;
      }
    }


    //Pour les media de type video
    if ($entity->hasField('field_media_oembed_video')) {
      $published_on = $this->getNodeFieldValue($entity, 'created');
      $convertedDate = $this->convertTimestampToDateDMYHS($published_on);
      $title = $this->getNodeFieldValue($entity, 'name');
      $video = $this->getNodeFieldValue($entity, 'field_media_oembed_video');
      $video_info = [
        '#theme' => 'phenix_custom_bloc_search_media_video',
        '#cache' => ['max-age' => 0],
        '#content' => [
          'title' => $title,
          'resume' => $resume,
          'published_on' => $convertedDate,
          'media_id' => $entity->id()
          ]
      ];
      $var['output'] = $video_info;
    }
    // return $var;
  }
}

/**
 * 
 */
public function customResultThumbnail(&$var) {
  $field = $var['field'];
	$row = $var['row'];
  $value = $field->getValue($row);
  $view = $var['view'];
  $entity = $var['row']->_entity;
  
  if ($value && $entity->hasField('field_media_document')) {
    $doc = $this->getNodeFieldValue($entity, 'field_media_document');
    $file = \Drupal\file\Entity\File::load($doc);
    $filememe = $this->getNodeFieldValue($file, 'filemime');
    $txt_file = '';
    
    switch($filememe) {
      case 'application/pdf':
        $txt_file = '.pdf';
        break;
      case 'application/vnd.openxmlformats-officedocument.wordprocessingml.document':
        $txt_file = '.docx';
        break;
      case 'application/msword':
        $txt_file = '.doc';
        break;
      case 'application/rtf':
        $txt_file = '.rtf';
        break;
        
    }
      
    $var['output'] = ['#markup' => '<p class="thumbnail-type"> ' . $txt_file . ' </p>'];
  }
}

public function customResultSearchNode(&$var){
  $field = $var['field'];
	$row = $var['row'];
  $value = $field->getValue($row);
  $view = $var['view'];
  $entity = $var['row']->_entity;
  // dump($field->field);
  if ($field->field == 'body') {
    $var['output'] = ['#markup' => '<span class="empty-td"></span>'];
  }
  if ($field->field == 'title') {
    $var['output'] = ['#markup' => '<span class="empty-td"></span>'];
  }
  if ($field->field == 'rendered_item') {
    $published_on = $this->getNodeFieldValue($entity, 'created');
    $convertedDate = $this->convertTimestampToDateDMYHS($published_on);
    
     $info_node_article = [
      '#theme' => 'phenix_custom_bloc_search_node',
      '#cache' => ['max-age' => 0],
      '#content' => [
         'title' => $entity->getTitle(),
        'resume' => $this->getNodeFieldValue($entity, 'body'),
        'published_on' => $convertedDate, 
        'node_id' => $entity->id(),
      ]
    ]; 
    $var['output'] = $info_node_article;
    return $var;
  }
}

/**
 * Personnaliser l'affichage du resultat de recherche des term
 */
public function customResultSearchTerm(&$var){
  $field = $var['field'];
	$row = $var['row'];
  $value = $field->getValue($row);
  $view = $var['view'];
  $entity = $var['row']->_entity;
  
  if ($field->field == 'body') {
    $var['output'] = '';
  }
  if ($field->field == 'thumbnail') {
    $var['output'] = ['#markup' => '<span class="node-type thumbnail-type">Rubrique</span>'];
  }
  if ($field->field == 'name_1') {
    $var['output'] = ['#markup' => '<span class="empty-td"></span>'];
  }
  if ($field->field == 'description') {
    $var['output'] = ['#markup' => '<span class="empty-td"></span>'];
  }
  if ($field->field == 'rendered_item') {
    $published_on = $this->getNodeFieldValue($entity, 'changed');
    $convertedDate = $this->convertTimestampToDateDMYHS($published_on);
    $description =  $this->getNodeFieldValue($entity, 'description');
    if (!$description) {//
      $dossier_id = $this->getNodeFieldValue($entity, 'field_dossier');
      $paragraph = \Drupal\paragraphs\Entity\Paragraph::load($dossier_id);
      $description = $this->getNodeFieldValue($paragraph, 'field_texte_formate');

      
      // Create a DOMDocument instance and load the HTML
      $doc = new \DOMDocument();
      $doc->loadHTML($description);

      // Use DOMXPath to query for text nodes
      $xpath = new \DOMXPath($doc);

      // Query for all text nodes within the table element
      $textNodes = $xpath->query('//table//text()');

      // Initialize a variable to store the extracted text
      $extractedText = '';

      // Loop through the text nodes and concatenate their text content
      foreach ($textNodes as $node) {
          $extractedText .= $node->nodeValue . ' ';
      }

      // Remove extra whitespace and trim the result
      $description = trim($extractedText);
      $description = utf8_decode($description);
      
    }
     $info_node_article = [
      '#theme' => 'phenix_custom_bloc_search_term',
      '#cache' => ['max-age' => 0],
      '#content' => [
         'title' => $this->getNodeFieldValue($entity, 'name'),
        'resume' => $description,
        'published_on' => $convertedDate, 
        'node_id' => $entity->id(),
      ]
    ]; 
    $var['output'] = $info_node_article;
    return $var;
  }
}

/**
 * Recupère le libellé du filiere par id
 */
private function getFiliereLabelById ($id) {
  return \Civi\Api4\OptionValue::get()
  ->addSelect('label')
  ->addWhere('id', '=', $id)
  ->execute()->first();
}

/**
 * Ajoute le titre pour le résultat de recherche ==> "Résultats pour "mot clé"
 */
public function addTitleToViewSearch(&$var) {
  if ($var['view']->id() == 'rechercher') {
    $field_name = 'rendered_item';

    // Get the field handler.
    $field = $var['view']->display_handler->getHandler('field', $field_name);

    // Change the label of the field.
    $keyword = \Drupal::request()->query->get('search_api_fulltext');
    // If you want to change the label only for a specific display, you can check for the display ID.
    // Replace 'block_1' with your specific display ID.
    if ($var['display_id'] == 'page_1') {
      $field->options['label'] = [
        '#markup' => '<p class="result-label">Résultat pour <span class="res-keyword">"' . $keyword . '"</span>',
      ];
    }

  }
}

private function getMediaName ($file) {
  $get_description_by_id = \Drupal::database()->query('select * from  media__field_media_document where entity_id = ' . $file->id())->fetch()->field_media_document_description;
  $size_of_file = filesize('/var/aegir/platforms/civicrm-d9/' . $file->createFileUrl());
  $media_name = $this->getNodeFieldValue($media, 'field_titre_public') ?: $get_description_by_id;
}


}
