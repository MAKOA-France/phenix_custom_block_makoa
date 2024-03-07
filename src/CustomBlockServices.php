<?php


namespace Drupal\phenix_custom_block;

use Drupal\media\Entity\Media;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\taxonomy\Entity\Term;
use Drupal\file\Entity\File;
use Drupal\Component\Utility\Unicode;
use Drupal\Core\Url;
use Drupal\Core\Link;
use \Drupal\Component\Utility\UrlHelper;

use Drupal\Core\Routing\TrustedRedirectResponse;

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
      if (!$cid) {
        return;
      }
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


    public function createActivity ($infos) {
      return \Civi\Api4\Activity::create(FALSE)
        ->addValue('activity_type_id', 60)
        ->addValue('subject', $infos['subject'])
        ->addValue('details', $infos['the_question'])
        ->addValue('target_contact_id', [
          $infos['employer'],
        ])
        ->addValue('source_contact_id', $infos['employer'])
        ->addValue('assignee_contact_id', [
          $infos['assignee_to'],
        ])
        ->execute();
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

          $events = \Civi\Api4\Event::get(FALSE)
          ->addSelect('rsvpevent_cg_linked_groups.rsvpevent_cf_linked_groups')
          ->addWhere('id', '=', $event_id)
          ->execute();
          if ($events) {
            
            $eventGroupId = $events->getIterator();
            $eventGroupId = iterator_to_array($eventGroupId);  
            foreach ($eventGroupId as $group_id) {
              $allContactId = \Civi\Api4\GroupContact::get(FALSE)
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

  /**
   * Filtrer la vue "Mes commissions" par groupes auxquels le contact appartient
   */
  public function filterByGroupAdded ($query, $cid) {
    $query->where[] =  array(
      'conditions' => array(
        array(
          'field' => 'civicrm_group_contact.status',
          'value' => 'Added',
          'operator' => '=',
        ),
      ),
      'type' => 'AND',
    );
    
    return $query;
  }
  

  public function getEventAdherent ($cid) {
    $query = "select event_id, contact_id from civicrm_participant where contact_id = " . $cid; 

    $results =  \Drupal::database()->query($query)->fetchAll();
    return $results;
  }

  public function filterByContactId ($query, $cid) {
    $query->where[] =  array(
      'conditions' => array(
        array(
          'field' => 'civicrm_group_contact.status',
          'value' => 'Added',
          'operator' => '=',
        ),
        array(
          'field' => 'civicrm_group_contact.contact_id',
          'value' => $cid,
          'operator' => '=',
        ),
      ),
      'type' => 'AND',
    );
    
    return $query;
  }

  public function filterMeetByContactId ($query, $cid) {
    $whiteListEvent = $this->getEventAdherent ($cid);
    if ($whiteListEvent) {
      $whiteListEvent = array_column($whiteListEvent, 'event_id');
    }
    $query->where[] =  array(
      'conditions' => array(
        array(
          'field' => 'created_id_civicrm_contact.id',
          'value' => $whiteListEvent,
          'operator' => 'IN',
        ),
      ),
      'type' => 'AND',
    );
    
    return $query;
  }

  /**
   * ('group_id.group_type', 'LIKE', '%3%') veut dire = group afficher sur extranet
   */
  public function getAllGroupIdWhereUserDoesntBelongAndGroupIsAfficherSurExtranet ($cid, $groupIds) {
    $groupContacts = \Civi\Api4\GroupContact::get(FALSE)
      ->addSelect('group_id')
      ->addWhere('group_id.group_type', 'LIKE', '%3%')
      ->addWhere('group_id.is_active', '=', TRUE)
      ->addWhere('contact_id', '!=', $cid)
      ->addWhere('status', '=', 'Added')
      ->addGroupBy('group_id')
      ->addWhere('group_id', 'NOT IN', $groupIds)
      ->execute()->getIterator();

    $groupContacts = iterator_to_array($groupContacts);   
    $groupContacts = array_column($groupContacts, 'group_id');   
    return $groupContacts;
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
       * 
       */
      public function checkIfThereIsAlreadyAdata ($cid) {
        return \Civi\Api4\CustomValue::get('phx_CC', FALSE)
          ->addSelect('Effectif_annee', 'Coti_CA_annuel')
          ->addWhere('entity_id', '=', $cid)
          ->addWhere('Annee', '=', $this->getPreviousOnlyYear())
          ->execute()->first();
      }

      /**
       * 
       */
      public function createLineEffectif ($current_cid) {
        if (\Civi\Api4\CustomValue::get('phx_CC', FALSE)
        ->addSelect('Effectif_annee', 'Coti_CA_annuel')
        ->addWhere('entity_id', '=', $current_cid)
        ->execute()->first()) {
          if (\Civi\Api4\CustomValue::get('phx_CC', FALSE)
                ->addSelect('Effectif_annee', 'Coti_CA_annuel')
                ->addWhere('entity_id', '=', $current_cid)
                ->addWhere('Annee', '=', $this->getPreviousOnlyYear())
                ->execute()->first()) {

                  return ;
                }else {
                  return \Civi\Api4\CustomValue::create('phx_CC', FALSE)
                  ->addValue('entity_id', $current_cid)
                  ->addValue('Annee', $this->getPreviousOnlyYear())
                  ->execute();
                }
        } else {
           if (\Civi\Api4\CustomValue::get('phx_CC', FALSE)
                ->addSelect('Effectif_annee', 'Coti_CA_annuel')
                ->addWhere('entity_id', '=', $current_cid)
                ->addWhere('Annee', '=', $this->getPreviousOnlyYear())
                ->execute()->first()) {

                  return ;
                }else {
                  return \Civi\Api4\CustomValue::create('phx_CC', FALSE)
                  ->addValue('entity_id', $current_cid)
                  ->addValue('Annee', $this->getPreviousOnlyYear())
                  ->execute();
                  // $query = \Drupal::database()->query('insert into civicrm_value_phx_chiffrescle (entity_id) values ' . $current_cid)->execute();
                }
        }
     
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
    $has_document = !empty($documents);
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
        $date_doc_timestamp = $this->getNodeFieldValue($file_info, 'created');
        $date_doc = $this->convertTimesptamToDate($date_doc_timestamp);
        $all_doc_info[$file_id]['created_at'] = $date_doc;
      }
    }
    $has_document = !empty($all_doc_info);
    $var['last_doc'] = [
      '#theme' => 'phenix_custom_block_last_doc_txt_img',
      '#cache' => ['max-age' => 0],
      '#content' => [
        'data' => $all_doc_info,
        'paragraph_id' => $paragraphId,
        'has_document' => $has_document,
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
  public function convertTimesptamToDate($timestamp) {
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
  if ($term->hasField('field_gabarit_texte_et_images')) {
    $isRubriqueWithImgAndTxt = $this->getNodeFieldValue($term, 'field_gabarit_texte_et_images');
    return $isRubriqueWithImgAndTxt;
  }
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


public function getCredentialAuthx ($cid) {
  \Drupal::service('civicrm')->initialize();
  return  \Civi\Api4\AuthxCredential::create(FALSE)
        ->setContactId($cid)
        ->execute()->first()['cred'];
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
  $img = '';
  
  switch($file_type) {
    case 'application/pdf':
      $img = 'pdf-3.png';
      break;
    case 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet':
    case 'application/vnd.ms-excel':
      $img = 'pdf.png';
      break;
    case 'application/vnd.openxmlformats-officedocument.wordprocessingml.document':
    case 'application/msword':
      $img = 'pdf-2.png';
      break;
    case 'application/rtf':
    case 'application/zip':
      $img = 'Icon metro-file-zip.png';
      break;
  }

  return $img;
}

/**
 * 
 */
public function customizeDetailPageGroupIfUserDoesntBelongToGroup (&$var) {

  // Get the current user.
  $current_user = \Drupal::currentUser();

  // Get the user roles.
  $user_roles = $current_user->getRoles();
  if ((!in_array('administrator', $user_roles) && !in_array('super_utilisateur', $user_roles) && !in_array('permanent', $user_roles)) && (!$this->checkIfUserIsMembreOfCurrentGroup())) {
    $response = new TrustedRedirectResponse('/');
    $response->send();
  }
  // if (!$this->checkIfUserIsMembreOfCurrentGroup()) {  //Si l'user n'est pas membre du groupe on redirige veres la page d'accueil
  //   unset($var['page']['content']['b_zf_content']);
  // }
}

public function checkIfUserIsMembreOfCurrentGroup() {
  //Tdoo check if user appartient au current group sinon on n'affiche que le dernier doc de type compte rendu
  $req = \Drupal::request();
  if ($req->get('civicrm_group')) {

    $current_group_id = $this->getNodeFieldValue($req->get('civicrm_group'), 'id');
    // Get the current user account object.
    $user = \Drupal::currentUser();
    
    // Get the email address of the current user.
    $email = $user->getEmail();
    $cid = $this->getContactIdByEmail($email);
    $groupContacts = \Civi\Api4\GroupContact::get(FALSE)
    ->addSelect('contact_id')
    ->addWhere('group_id', '=', $current_group_id)
    ->addWhere('status', '=', 'Added')
    ->execute()->getIterator();
    
    
    $groupContacts = iterator_to_array($groupContacts); 
    $groupContacts = array_column($groupContacts, 'contact_id'); 
    return in_array($cid, $groupContacts);
  }
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
  $string_query = 'select entity_id from media__field_tag where field_tag_target_id = ' . $term_object_id;
  $all_linked_doc = $db->query($string_query)->fetchAll();
  $is_term_social = $this->getNodeFieldValue($term_object, 'field_social') ? true : false;
  \Drupal::service('page_cache_kill_switch')->trigger();
  \Drupal::cache()->invalidateAll();
  if ($all_linked_doc) {
    $all_linked_doc = array_column($all_linked_doc, 'entity_id');


    //sort by created 
    $entityTypeManager = \Drupal::entityTypeManager();
    $query = $entityTypeManager->getStorage('media')->getQuery()
      ->condition('mid', $all_linked_doc, 'IN')
      ->sort('created', 'DESC') // Sort by creation date in descending order
      ->range(0, count($all_linked_doc)); // Limit the results to the specified media IDs
      
    $media_entities = $query->execute();

    $media_entities = $this->skipDocSocial($media_entities);
    
    $media_entities = $this->sortTermIdByDateCreation($media_entities, $is_term_social);
    

    $media_entities = $entityTypeManager->getStorage('media')->loadMultiple($media_entities);

    // $media_entities = \Drupal::entityTypeManager()->getStorage('media')->loadMultiple($all_linked_doc);
    
    if (count($media_entities) > 0) {
      $first_doc = reset($media_entities);
      $created_at = $this->getNodeFieldValue($first_doc, 'created');
      $document_year = $this->getYearFromTimestamp($created_at);
      $first_doc_title = $this->getNodeFieldValue($first_doc, 'field_titre_public') ? $this->getNodeFieldValue($first_doc, 'field_titre_public') : $this->getNodeFieldValue($first_doc, 'name');
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
      $filieres = $this->getFiliereLabels($first_doc);
      
      $allOtherDoc = array_shift($media_entities);
     
      $seeMoreDoc = $this->getAllOtherDocInfo ($media_entities, $term_name) ? true : false;
      $allowToEdit = $this->checkIfUserCanEditDoc ();
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
          'display_see_other_doc' => $seeMoreDoc,
          'term_name' => $term_name,
          'document_year' => $document_year,
          'is_page_last_doc' => true,
          'there_is_a_document' => true,
          'can_edit_doc' => $allowToEdit,
          'filiere' => $filieres,
          'term_id' => $term_object_id,
          'is_adherent' => $this->isAdherent(),
          'not_adherent_or_social' => $this->notAdherentOrSocial(),
        ], 
        'there_is_a_doc' => true,
      ];
    }
  }else {
    return $var['content'] = [
      '#theme' => 'phenix_custom_block_last_doc_automatique',
      '#cache' => ['max-age' => 0],
      '#content' => [
        'data' => [],
        'there_is_a_document' => false,
      ], 
      'there_is_a_doc' => false,
    ];
    }
}

public function getAllLinkedDocByTags (&$var) {
  $db = \Drupal::database();
  $term_object = $var['elements']['#taxonomy_term'];
  $term_object_id = $this->getNodeFieldValue($term_object, 'tid');
  $term_object = Term::load($term_object_id);
  $term_name = $this->getNodeFieldValue($term_object, 'name');
  $string_query = 'select entity_id from media__field_tag where field_tag_target_id = ' . $term_object_id;
  $all_linked_doc = $db->query($string_query)->fetchAll();
  return $all_linked_doc;
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
    $media_name = $this->getNodeFieldValue($doc, 'field_titre_public') ? $this->getNodeFieldValue($doc, 'field_titre_public') : $this->getNodeFieldValue($doc, 'name');
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
        'media_id' => $doc->id(),
        'filiere' => $this->getFiliereLabels($doc)
        
      ]; 
  }

  return $all_documents;
      
}

public function getFileSize($file_object) {
  $first_doc_file_url = $this->getNodeFieldValue($file_object, 'uri');
  $first_doc_file_size = filesize($first_doc_file_url);
  return round($first_doc_file_size / 1024, 0);
}

public function customSearchTitreDossier (&$var) {
  $field = $var['field'];
  $view = $var['view'];
	$row = $var['row'];
  $value = $field->getValue($row);
  $entity = $var['row']->_entity;
  $current_user = \Drupal::currentUser();
  $user_roles = $current_user->getRoles();
  $entity = $row->_entity;
  if ($field->field == 'rendered_item') {
    $var['output'] = ['#markup' => '<span class="empty-td"></span>'];
  }
  if ($field->field == 'body') {
    $var['output'] = ['#markup' => '<span class="empty-td"></span>'];
  }
  if ($field->field == 'field_media_oembed_video') {
    $var['output'] = ['#markup' => '<span class="empty-td"></span>'];
  }
  if ($field->field == 'field_media_video_file') {
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
  if ($field->field == 'title_1') {
    $var['output'] = ['#markup' => '<span class="empty-td"></span>'];
  }

  if ($field->field == 'parent_type') {
    $var['output'] = ['#markup' => '<span class="empty-td"></span>'];
  }
  if ($field->field == 'id') {
    $var['output'] = ['#markup' => '<span class="empty-td"></span>'];
  }
  if ($field->field == 'type') {
    $var['output'] = ['#markup' => '<span class="empty-td"></span>'];
  }
  if ($field->field == 'parent_field_name') {
    $var['output'] = ['#markup' => '<span class="empty-td"></span>'];
  }
  if ($field->field == 'parent_id') {
    $var['output'] = ['#markup' => '<span class="empty-td"></span>'];
  }

   
  if ($field->field == 'field_texte_formate' && $value) {
    $textDescription = $this->getNodeFieldValue($entity, $field->field);

    $termId = $this->getNodeFieldValue($entity, 'parent_id');
     
    $termObj = Term::load($termId);
    
    $termName = $this->getNodeFieldValue($termObj, 'name');
    
    $published_on = $this->getNodeFieldValue($termObj, 'changed');
    $convertedDate = $this->convertTimestampToDateDMYHS($published_on);

    // Remove HTML tags
    $plainTextContent = strip_tags($value);

    // Truncate the text
    $maxLength = 200; // Adjust the maximum length as needed
    $value = mb_substr($plainTextContent, 0, $maxLength);


    $info_term = [
      '#theme' => 'phenix_custom_bloc_search_description_term',
      '#cache' => ['max-age' => 0],
      '#content' => [
        'title' => $termName,
        'resume' => $value,
        'published_on' => $convertedDate, 
        'node_id' => $termObj->id(),
        ]
      ]; 
      $var['output'] = $info_term;
    

    
  }

  if ($field->field == 'field_titre' && (!$value)) {

    $var['output'] = ['#markup' => '<span class="empty-td"></span>'];
  }
      
  if ($field->field == 'field_texte_formate' && (!$value)) {

    $var['output'] = ['#markup' => '<span class="empty-td"></span>'];
  }

  if ($field->field == 'field_titre' && $value) {
    if ($entity->hasField('type') && $this->getNodeFieldValue($entity, 'type') == 'dossier' && $entity->hasField('parent_id')) {

      $termId = $this->getNodeFieldValue($entity, 'parent_id');
      $isLinkedWithMenu = $this->isTermLinkedWithMenu($termId);
      $termObj = Term::load($termId);
      
      $termName = $this->getNodeFieldValue($termObj, 'name');
      
      // Generate the URL for the taxonomy term.
      $url = Url::fromRoute('entity.taxonomy_term.canonical', ['taxonomy_term' => $termId]);
      
      // Create a link using the URL and the term name as the link text.
      $link = Link::fromTextAndUrl($termName, $url);
      // Render the link.
      $output = $link->toRenderable();
      $linkUrl = \Drupal::service('renderer')->render($output)->__toString();
      $published_on = $this->getNodeFieldValue($termObj, 'changed');
      $convertedDate = $this->convertTimestampToDateDMYHS($published_on);
      
      $info_term = [
        '#theme' => 'phenix_custom_bloc_search_titre_dossier_paragraph',
        '#cache' => ['max-age' => 0],
        '#content' => [
          'title' => $termName,
          'resume' => $value,
          'published_on' => $convertedDate, 
          'node_id' => $termObj->id(),
          ]
        ]; 
        $var['output'] = $info_term;
      }
    // $var['output'] = ['#markup' => $linkUrl];
  }
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
  $current_user = \Drupal::currentUser();
  $user_roles = $current_user->getRoles();

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
  if ($field->field == 'title_1') {
    $var['output'] = ['#markup' => '<span class="empty-td"></span>'];
  }
  

  //Si l'entity media a une video
  if ($entity->hasField('field_media_video_file') || $entity->hasField('field_media_oembed_video')) {
    if ($field->field == 'thumbnail') {
      $var['output'] = ['#markup' => '<p class="thumbnail-type">Mp4</p>'];
    }
  }

  //pour les videos
  if($field->field == 'field_media_video_file') {
    //Pour les media de type video
    if ($entity->hasField('field_media_video_file')) {
      $file_video_id = $field->getValue($row);

      $current_output = $var['output'];
      $published_on = $this->getNodeFieldValue($entity, 'created');
      $field_media_video_file = $this->getNodeFieldValue($entity, 'field_media_video_file');
      $convertedDate = $this->convertTimestampToDateDMYHS($published_on);
      $title = $this->getNodeFieldValue($entity, 'name');
      $file_video_object = File::load($file_video_id);
      $extension_video = $this->getNodeFieldValue($file_video_object, 'filemime');
      $video_info = [
        '#theme' => 'phenix_custom_bloc_search_media_video',
        '#cache' => ['max-age' => 0],
        '#content' => [
          'video' => $current_output->__toString(),
          'title' => $title,
          'resume' => '',
          'published_on' => $convertedDate,
          'media_id' => $field_media_video_file, 
          ]
      ];
      $var['output'] = $video_info;
    }
  }



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
      $isUserSocial = $this->checkIfUserIsAdminOrSocial();
      $isDocSocial = $this->getNodeFieldValue($entity, 'field_social');


      ///CHeck si le document est lié avec une rubrique social VIA PARAGRAPHES
      // $isLinkedWithTermSocial = $this->checkIfDocumentIsLinkedWithTermSocial($entity->id());
      

      //Checker d'abord si le document est social
      $isDocSocial = $this->isDocSocial ($entity->id());

      //Si l'utilisateur est admin ou SU ou permanent
      // if ((!in_array('administrator', $user_roles) && !in_array('super_utilisateur', $user_roles) && !in_array('permanent', $user_roles)) && $isDocSocial) {
      //   $doc_info = [
      //     '#theme' => 'phenix_custom_bloc_search',
      //     '#cache' => ['max-age' => 0],
      //     '#content' => [
      //       'has_result' => false
      //       ]
      //     ];
      //     $var['output'] = ['#markup' => '<p class="row-to-hide"></p>'];
      //   return $doc_info;
      // }
      
      $current_timestamp = \Drupal::time()->getRequestTime();
      $two_years_ago_timestamp = strtotime('-2 years', $current_timestamp);
      $created_at = $this->getNodeFieldValue($entity, 'created');
      $isDocSocial = false;


      $linked_term = $entity->get('field_tag')->getValue();
      if ($linked_term) {
        $linked_term = array_column($linked_term, 'target_id');
        $curr_term = Term::loadmultiple($linked_term);
        $allnames = '';
        if(count($curr_term) > 1) {
          foreach($curr_term as $key => $value_term) {
            $allnames .= $this->getNodeFieldValue($value_term, 'name') . ', ';
          }
          $allnames = rtrim($allnames, ', ');
        }else {
          $allnames = (reset($curr_term))->get('name')->getValue()[0]['value'];
        }
        
        
      }
      $type_doc = $entity->get('field_type_de_document')->getValue()[0]['value'];
      $libelle = $this->getTypeDocumentWithAutre($entity);
      $allowToEdit = $this->checkIfUserCanEditDoc ();
      $icon_html = '';
      //recuperer le type de document 
      if ($entity->hasField('field_media_document')) {

        $file = $this->getNodeFieldValue($entity, 'field_media_document');
        $file = \Drupal\file\Entity\File::load($file);
        $filememe = $this->getNodeFieldValue($file, 'filemime');
        $file_type = 'pdf-3.png';
        switch($filememe) {
        case 'application/pdf':
          $file_type = 'pdf-3.png';
          break;
        case 'application/vnd.openxmlformats-officedocument.wordprocessingml.document':
          $file_type = 'pdf-2.png';
          break;
          case 'application/msword':
          $file_type = 'pdf-2.png';
          break;
          case 'application/rtf':
            $txt_file = '.rtf';
          break;
        case 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet':
          $file_type = 'pdf.png';
          break;
        case 'application/vnd.ms-excel':
          $file_type = 'pdf.png';
          break;
        }
        
        $icon_html = ['#markup' => '<img   class="icon-search-result-doc" loading="lazy" src="/files/assets/'. $file_type .'">'];
      }


      if ($libelle) {
        $libelle = $libelle == 'Autre' ? false : $libelle;
        $doc_info = [
          '#theme' => 'phenix_custom_bloc_search',
          '#cache' => ['max-age' => 0],
          '#content' => [
            'title' => $value,
            'resume' => $resume,
            'type_document' => $libelle,
            'filiere' => rtrim($label, ', '),
            'published_on' => $convertedDate,
            'media_id' => $entity->id(),
            'can_edit_doc' => $allowToEdit,
            'has_result' => true, 
            'linked_term_name' => $allnames,
            'doc_token' => $this->generateTokenToMedia($entity),
            'icon_doc' => $icon_html,
            ]
        ];
        $var['output'] = $doc_info;
      }
    }


    
    
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
public function is_valid_uri_scheme($media) {
  // Get the URI of the media entity.
  $uri = $media->getSource()->getSourceFieldValue($media);

  // Extract the scheme from the URI.
  $scheme = parse_url($uri, PHP_URL_SCHEME);

  // Get the stream wrapper manager service.
  $stream_wrapper_manager = \Drupal::service('stream_wrapper_manager');

  // Check if the scheme is valid.
  return $stream_wrapper_manager->isValidScheme($scheme);
}

public function allFormulaireTabsAvailable () {
  return [
    "entreprise",
    "effectif",
    "dirigeants",
    "abonnements",
    "contact entreprise",
    "agrement sanitaire",
    "donnee generale",
    "achat viande",
    "produit commercialisés",
    "certification",
    "activités",
    "abattage"
  ];
}

public function generateTokenToMedia ($media) {
  $csrfToken = '';

    // Vérifier si le média existe et s'il a une relation de fichier
    if ($media && $media->hasField('field_media_document')) {
      // Récupérer la cible de la relation de fichier (le fichier)
      $file_target = $media->get('field_media_document')->target_id;

      // Charger l'entité de fichier correspondante
      $file = File::load($file_target);

      // Vérifier si le fichier existe
      if ($this->is_valid_uri_scheme($media)) {

        if ($file) {
          // Obtenir l'URI du fichier
          $file_uri = $file->getFileUri();
          
          // Générer une instance de l'URL à partir de l'URI du fichier
          $url = Url::fromUri(file_create_url($file_uri));
          
          // Générer un token CSRF
          $csrfToken = \Drupal::service('csrf_token')->get();
        }
      }else {
        $idTokenized = base64_encode($media->id());
        return $idTokenized;
      }
    }
    return $csrfToken;
}

public function getChecksum ($cid) {
  return \Civi\Api4\Contact::getChecksum(FALSE)
  ->setContactId($cid)
  ->execute()->first()['checksum'];
}

/**
 * Requete de test pour reuperer un document lié par paragraphe
 * sselect DISTINCT field_document_target_id, DOC.entity_id , TAX.entity_id, DOS.entity_id as DOS_entity_id from paragraph__field_document as DOC   LEFT JOIN taxonomy_term__field_dossier as DOS ON DOC.entity_id = DOS.field_dossier_target_id  LEFT JOIN taxonomy_term__field_social as TAX ON DOS.entity_id = TAX.entity_id   where     field_document_target_id = 28906;
 */
public function allDocumentIdSocial () {
  //Lors de l'edition d'un doc "social" est coché
  $sql = "select  DISTINCT entity_id from media__field_social where field_social_value = 1;";
  $idDocHasSocialChecked = \Drupal::database()->query($sql)->fetchAll();
  $idDocHasSocialChecked = array_column($idDocHasSocialChecked, 'entity_id');

  $sqlDocLieParagraph  = "select DISTINCT field_document_target_id from paragraph__field_document as DOC LEFT JOIN taxonomy_term__field_dossier as DOS ON DOC.entity_id = DOS.field_dossier_target_id LEFT JOIN taxonomy_term__field_social as TAX ON DOS.entity_id = TAX.entity_id where TAX.field_social_value = 1";
  $idDocHasTermSocialChecked = \Drupal::database()->query($sqlDocLieParagraph)->fetchAll();
  $idDocHasTermSocialChecked = array_column($idDocHasTermSocialChecked, 'field_document_target_id');

  // Combine the two arrays
  $combinedArray = array_merge($idDocHasSocialChecked, $idDocHasTermSocialChecked);

  // Get distinct elements
  $idDocSocial = array_unique($combinedArray);
  return $idDocSocial;
}

/**
 * 
 */
public function checkIfMediaShouldNotBeDisplayed ($query) {
  
  // Get the entity type manager service.
  $entity_type_manager = \Drupal::entityTypeManager();

  // Specify the media type you want to query.
  $media_type = 'document';

  // Get the media entity type.
  $media_entity_type = $entity_type_manager->getDefinition('media');

  // Get the current date in the site's timezone.
  $current_date = new DrupalDateTime('now');

  // Set the current date to the start of the week (Sunday).
  $current_date->modify('-2 years');

  // Get the timestamp for the start of the current week.
  $current_week_start_timestamp = $current_date->getTimestamp();

  // Use the entity query to retrieve media entities of the specified type created today.
  $query_get_document = $entity_type_manager->getStorage($media_entity_type->id())->getQuery();
  $result = $query_get_document
    ->condition('bundle', $media_type)
    ->condition('created', $current_week_start_timestamp, '>')
    ->execute();

  $query->addCondition('mid',  array_values($result),"IN");
  return $query;
}

  /**
   * TODO CHECK TERM IF SOCIAL AND THEN ESCAPE
   */
  public function getAllTermRubrique () {
    // Load the taxonomy vocabulary (replace 'your_vocabulary_name' with your actual vocabulary name).
    $vid = 'rubrique';
    $vocabulary = \Drupal\taxonomy\Entity\Vocabulary::load($vid);
    $alltermId = [];
    if ($vocabulary) {
      // Load all terms in the vocabulary.
      $query = \Drupal::entityTypeManager()
        ->getStorage('taxonomy_term')
        ->getQuery();
      $query->condition('vid', $vid);
      $tids = $query->execute();
    
      // Load the taxonomy terms based on the term IDs.
      $terms = \Drupal\taxonomy\Entity\Term::loadMultiple($tids);
    
      // Iterate through the terms.
      foreach ($terms as $term) {
        // Do something with each term.
        // $term is an instance of the Term entity.
        $term_name = $term->getName();
        $term_id = $term->id();
        $isLinkedWithMenu = $this->isTermLinkedWithMenu($term->id());
        $isTermSocial = $this->isTermSocial($term->id());
        if ($isLinkedWithMenu && !$isTermSocial) {
          $alltermId[] = $term->id();
        }
      }
    }
    return $alltermId;
  
  }
public function idDocumentMoindeDeuxAnsPlusDocumentsSocial ($query) {
  // Get the entity type manager service.
  $entity_type_manager = \Drupal::entityTypeManager();

  // Specify the media type you want to query.
  $media_type = 'document';

  // Get the media entity type.
  $media_entity_type = $entity_type_manager->getDefinition('media');

  // Get the current date in the site's timezone.
  $current_date = new DrupalDateTime('now');

  // Set the current date to the start of the week (Sunday).
  $current_date->modify('-2 years');

  // Get the timestamp for the start of the current week.
  $current_week_start_timestamp = $current_date->getTimestamp();

  // Use the entity query to retrieve media entities of the specified type created today.
  $query_get_document = $entity_type_manager->getStorage($media_entity_type->id())->getQuery();
  $result = $query_get_document
    ->condition('bundle', $media_type)
    ->condition('created', $current_week_start_timestamp, '>')
    ->execute();

    $docMoinsDeDeuxAns = array_values($result);
    $allIds = array_merge($docMoinsDeDeuxAns, $this->allDocumentIdSocial());
    
  $query->addCondition('mid',  $allIds,"IN");
}

public function displayOnlyDocLinkedWithMenu ($query) {
  $whiteListeDocumentId = [];
  //document lié aux termes via ajout document
  $sql = 'select DISTINCT entity_id from media__field_tag';
  $sqlQuery = \Drupal::database()->query($sql)->fetchAll();
  $whiteListeDocumentViaAddDocId = array_column($sqlQuery, 'entity_id');
  
  
  //document lié aux termes via paragraphes
  $sql = 'SELECT DISTINCT field_document_target_id FROM paragraph__field_document';
  $sqlQuery = \Drupal::database()->query($sql)->fetchAll();
  $whiteListeDocumentViaParagrapheId = array_column($sqlQuery, 'field_document_target_id');

  //Merger les deux array après
  $whiteListeIds = array_merge($whiteListeDocumentViaAddDocId, $whiteListeDocumentViaParagrapheId);



  $orGroup = $query->createConditionGroup('OR');
  // $orGroup->addCondition('mid',  30159);
  // $orGroup->addCondition('mid',  30162);
  
  $query->addCondition('mid',  array_values($whiteListeIds),"IN");
  // $orGroup->addCondition('title',  '%conso%'," LIKE ");
  // $query->addConditionGroup($orGroup);
  // $query->setGroupOperator('OR');
  // Create a group of conditions with the "OR" operator.

  return $query; 

}


public function isAdherent () {
  $current_user = \Drupal::currentUser();
  $user_roles = $current_user->getRoles();
  $isAdherent = false;
  if (in_array('adherent', $user_roles) && (!in_array('super_utilisateur', $user_roles) || !in_array('administrator', $user_roles))) {
    $isAdherent = true;
  }
  return $isAdherent;
}

public function filterBysocial($query) {
  $idDocSocial = $this->allDocumentIdSocial();
  $query->addCondition('mid',  $idDocSocial,"NOT IN");
  return $query;
}



///CHeck si le document est lié avec une rubrique social VIA PARAGRAPHES
//TODO Unused function pour le moment
// private function checkIfDocumentIsLinkedWithTermSocial ($idDoc) {
//   $queryGetDossierId = \Drupal::database()->query('select entity_id from paragraph__field_document where field_document_target_id = ' . $idDoc);  
//   $queryGetDossierId = $queryGetDossierId->fetch()->entity_id;
//   $isLinkedWithTermSocial = false;
//   if ($queryGetDossierId) {
//     $queryGetTermId = \Drupal::database()->query('select entity_id from taxonomy_term__field_dossier where field_dossier_target_id = ' . $queryGetDossierId);
//     $queryGetTermId = $queryGetTermId->fetch()->entity_id;
//     $termObj = Term::load($queryGetTermId);
//     if ($termObj) {
//       $isTermSocial = $this->getNodeFieldValue($termObj, 'field_social');
//       $isLinkedWithTermSocial = $isTermSocial ? true : false;          
//     }
//   }
  
//   return $isLinkedWithTermSocial;
// }


///CHeck si le document est lié avec une rubrique social VIA DOCUMENT TAGS
//TODO UNUSED function pour le moment 
/* private function checkIfDocumentIsLinkedWithTermSocialByTag ($idDoc) {
  $queryGetDossierId = \Drupal::database()->query('select field_tag_target_id from media__field_tag where entity_id = ' . $idDoc);  
  $queryTermIds = $queryGetDossierId->fetchAll();
  $isLinkedWithTermSocial = false;
  if ($queryTermIds) {
    $queryTermIds = array_column($queryTermIds, 'field_tag_target_id');
    $terms = Term::loadMultiple($queryTermIds);
    foreach ($terms as $term) {
      $isTermSocial = $this->getNodeFieldValue($term, 'field_social');
      if ($isTermSocial > 0) {
        $isLinkedWithTermSocial = true; 
        break; 
      }
    }
  }

  return $isLinkedWithTermSocial;
} */

public function isDocSocial ($idDoc) {
  $docObj = Media::load($idDoc);
  if ($docObj) {
    if ($this->getNodeFieldValue($docObj, 'field_social') == '0') {
      return false;
    }
    return $this->getNodeFieldValue($docObj, 'field_social');
  }
  return false;
}

public function accessRubriqueSocial ($idDoc) {
  ///CHeck si le document est lié avec une rubrique social 
  $queryGetDossierId = \Drupal::database()->query('select entity_id from paragraph__field_document where field_document_target_id = ' . $idDoc);  
  $queryGetDossierId = $queryGetDossierId->fetch()->entity_id;
  if ($queryGetDossierId) {
    $queryGetTermId = \Drupal::database()->query('select entity_id from taxonomy_term__field_dossier where field_dossier_target_id = ' . $queryGetDossierId);
    $queryGetTermId = $queryGetTermId->fetch()->entity_id;
    $termObj = Term::load($queryGetTermId);
    if ($termObj) {

      $isTermSocial = $this->getNodeFieldValue($termObj, 'field_social');
      
      $current_timestamp = \Drupal::time()->getRequestTime();
      $two_years_ago_timestamp = strtotime('-2 years', $current_timestamp);
      $created_at = $this->getNodeFieldValue($entity, 'created');
      if (($created_at <= $two_years_ago_timestamp) && !$isTermSocial) {
        $doc_info = [
          '#theme' => 'phenix_custom_bloc_search',
          '#cache' => ['max-age' => 0],
          '#content' => [
            'has_result' => false
            ]
          ];
          $var['output'] = ['#markup' => '<p class="row-to-hide"></p>'];
        return $doc_info;
      }
    }
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
      case 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet':
        $txt_file = '.excel';
        break;
      case 'application/vnd.ms-excel':
        $txt_file = '.xls';
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
  if ($field->field == 'body') {
    $var['output'] = ['#markup' => '<span class="empty-td"></span>'];
  }
  if ($field->field == 'title') {
    $var['output'] = ['#markup' => '<span class="empty-td"></span>'];
  }
  if ($field->field == 'rendered_item') {
    $published_on = $this->getNodeFieldValue($entity, 'created');
    $convertedDate = $this->convertTimestampToDateDMYHS($published_on);
    $max_length = 200;
    $truncated_text = Unicode::truncate($this->getNodeFieldValue($entity, 'body'), $max_length, TRUE, TRUE);

     $info_node_article = [
      '#theme' => 'phenix_custom_bloc_search_node',
      '#cache' => ['max-age' => 0],
      '#content' => [
         'title' => $entity->getTitle(),
        'resume' => $truncated_text,
        'published_on' => $convertedDate, 
        'node_id' => $entity->id(),
      ]
    ]; 
    $var['output'] = $info_node_article;
    return $var;
  }
  if ($field->field == 'thumbnail') {
    $var['output'] = ['#markup' => '<p class="thumbnail-type"> Article </p>'];
  }
  if (in_array($field->field, ['title_1', 'name_1', 'description'])) {
    $var['output'] = ['#markup' => '<span class="empty-td"></span>'];
  }
}

public function customResultSearchMeeting(&$var){
  $field = $var['field'];
	$row = $var['row'];
  $value = $field->getValue($row);
  $view = $var['view'];
  $entity = $var['row']->_entity;

  //Pour les résultats de type reunion
  if($entity->getEntityTypeId() == 'civicrm_event') {

    if ($field->field == 'body') {
      $var['output'] = ['#markup' => '<span class="empty-td"></span>'];
    }
    if (in_array($field->field, ['title', 'title_1', 'description', 'name_1'])) {
      $var['output'] = ['#markup' => '<span class="empty-td"></span>'];
    }
    
    if ($field->field == 'thumbnail') {
      $var['output'] = ['#markup' => '<p class="thumbnail-type"> Réunion </p>'];
    }
    if ($field->field == 'rendered_item') {
      // $published_on = $this->getNodeFieldValue($entity, 'created');
      // $convertedDate = $this->convertTimestampToDateDMYHS($published_on);
      // Create a DateTime object from the date string
      $start_date = $this->getNodeFieldValue($entity, 'start_date');
      $dateTime = new \DateTime($start_date);
      
      // Get the day
      $day = $dateTime->format('d');

      // Get the month
      $month = $dateTime->format('m');
      // Obtient le mois en français
      setlocale(LC_TIME, 'fr_FR.utf8');
      $dayLetter = strftime('%A', $dateTime->getTimestamp());

      // Get the year
      $year = $dateTime->format('Y');
      
      // Get the hour
      $hour = $dateTime->format('H');

      // Get the minute value.
      $minute = $dateTime->format('i');
      $event_title = $this->getNodeFieldValue($entity, 'title');
      $info_node_article = [
        '#theme' => 'phenix_custom_bloc_search_civicrm_meeting',
        '#cache' => ['max-age' => 0],
        '#content' => [
          'published_on' => $dayLetter . ' ' . $day . '/' . $month . '/' . $year . ' à ' . $hour . ':' . $minute,
          // 'resume' => $this->getNodeFieldValue($entity, 'body'),
          'title' => $event_title, 
          'event_id' => $entity->id(),
          ]
        ]; 
        $var['output'] = $info_node_article;
        return $var;
      }
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

    //raha toa ka document dia filtrena par, document manana menu
  //raha tsy manan menu izy de soit cachena soit unsetena

  $isTheTermHasLinkedMenu = \Drupal::database()->query("select link__uri from menu_link_content_data where link__uri like '%internal:/taxonomy/term/5561%'")->fetch();
  // $query = \Drupal::database()->query("select REVERSE(SUBSTRING_INDEX(REVERSE(link__uri), '/', 1)) AS term_id from menu_link_content_data where link__uri like '%/taxonomy/term/%';")->fetchAll();
    // if (!$isTheTermHasLinkedMenu) {
    //   unset($var['row']);
    //   unset($var['view']);
    //   $var['output'] =  ['#markup' => '<span class="tohide"></span>'];
    // }
  
  if ($field->field == 'body') {
    $var['output'] = '';
  }
  if ($field->field == 'thumbnail') {
    $var['output'] = ['#markup' => '<span class="node-type thumbnail-type">Rubrique</span>'];
  }
  if ($field->field == 'name_1') {
    $var['output'] = ['#markup' => '<span class="empty-td"></span>'];
  }
  if ($field->field == 'description' || $field->field == 'title_1') {
    $var['output'] = ['#markup' => '<span class="empty-td"></span>'];
  }
  if ($field->field == 'rendered_item') {
    $published_on = $this->getNodeFieldValue($entity, 'changed');
    $convertedDate = $this->convertTimestampToDateDMYHS($published_on);
    $description =  $this->getNodeFieldValue($entity, 'description');
    if (!$description && $entity->hasField('field_dossier')) {//
      $dossier_id = $this->getNodeFieldValue($entity, 'field_dossier');
      $paragraph = \Drupal\paragraphs\Entity\Paragraph::load($dossier_id);
      if ($paragraph) {
  
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
    }
    // Truncate the text
    $maxLength = 200; // Adjust the maximum length as needed
    $nombreCaracteres = mb_strlen($description);
    $troisPoint = ($nombreCaracteres <200) ? ' ' : ' ...';
    $description = mb_substr($description, 0, $maxLength) . $troisPoint;

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
  return \Civi\Api4\OptionValue::get(FALSE)
  ->addSelect('label')
  ->addWhere('id', '=', $id)
  ->execute()->first();
}

/**
 * Ajoute le titre pour le résultat de recherche ==> "Résultats pour "mot clé"
 */
public function addTitleToViewSearch(&$var, $total) {
  if ($var['view']->id() == 'rechercher') {
    $field_name = 'rendered_item';

    // Get the field handler.
    $field = $var['view']->display_handler->getHandler('field', $field_name);

    // Change the label of the field.
    $keyword = \Drupal::request()->query->get('search_api_fulltext');
    // If you want to change the label only for a specific display, you can check for the display ID.
    // Replace 'block_1' with your specific display ID.
    if ($var['display_id'] == 'page_1') {
      $resultat_text = ($total >1) ? ' résultats ' : ' résultat ';
      $field->options['label'] = [
        '#markup' => '<p class="result-label">' . $total . $resultat_text . '  pour <span class="res-keyword">"' . $keyword . '"</span>',
      ];
    }

  }
}

private function getMediaName ($file) {
  $get_description_by_id = \Drupal::database()->query('select * from  media__field_media_document where entity_id = ' . $file->id())->fetch()->field_media_document_description;
  $size_of_file = filesize('/var/aegir/platforms/civicrm-d9/' . $file->createFileUrl());
  $media_name = $this->getNodeFieldValue($media, 'field_titre_public') ?: $get_description_by_id;
}

public function checkIfUserCanEditDoc () {
  // Get the current user object.
  $current_user = \Drupal::currentUser();

  $user = \Drupal\user\Entity\User::load($current_user->id());

  // Get an array of role IDs for the current user.
  $user_roles = $current_user->getRoles();
  $whiteListRole = ['administrator', 'super_utilisateur', 'permanent'];
  $allowToEdit = false;
  if (in_array('administrator', $user_roles) || in_array('super_utilisateur', $user_roles) || in_array('permanent', $user_roles)) {
    $allowToEdit = true;
  }

  return $allowToEdit;
}

public function checkIfUserIsAdminOrSocial () {
  // Get the current user object.
  $current_user = \Drupal::currentUser();

  $user = \Drupal\user\Entity\User::load($current_user->id());

  // Get an array of role IDs for the current user.
  $user_roles = $current_user->getRoles();
  $whiteListRole = ['administrator', 'social', 'super_utilisateur'];
  $allowToEdit = false;
  
  if (in_array('administrator', $user_roles) || in_array('social', $user_roles) || in_array('super_utilisateur', $user_roles)) {
    $allowToEdit = true;
  }

  return $allowToEdit;
}

/**
 * 
 */
public function skipDocSocial ($currentIdDocs) {
  $docs = \Drupal::service('entity_type.manager')->getStorage('media')->loadMultiple($currentIdDocs);
  $isUserSocial = $this->checkIfUserIsAdminOrSocial();
  //si l'utilisateur n'est pas social
  uasort($docs, function($a, $b) {
    $timestampA = $a->get('created')->value;
    $timestampB = $b->get('created')->value;
    return $timestampB - $timestampA;
  });
  if (!$isUserSocial) {
    $currentIdDocs = [];

    foreach($docs as $doc) {
      $isDocSocial = $this->getNodeFieldValue($doc, 'field_social');
      if (!$isDocSocial) {
        $currentIdDocs[] =  $doc->id();
      }
    }

    return $currentIdDocs;
  }
  return $currentIdDocs;
}


/**
 * 
 */
public function getOnlyDocCompteRendu ($currentIdDocs) {
  $docs = \Drupal::service('entity_type.manager')->getStorage('media')->loadMultiple($currentIdDocs);
  //si l'utilisateur n'est pas social
  uasort($docs, function($a, $b) {
    $timestampA = $a->get('created')->value;
    $timestampB = $b->get('created')->value;
    return $timestampB - $timestampA;
  });
    $currentIdDocs = [];

    foreach($docs as $doc) {
      $typeDocComteRendu = $this->getNodeFieldValue($doc, 'field_type_de_document');
      if ($typeDocComteRendu == 1) {
        $currentIdDocs[] =  $doc->id();
      }
    }

  return $currentIdDocs;
}

public function NePasAfficherDansOption (&$options) {
    // Load the parent term by its ID.
  $parent_term_id = 5569; // Replace with the ID of your parent term.
  $parent_term = Term::load($parent_term_id);

  $allChild = [];
  if ($parent_term) {
    // Load all child terms of the parent term.
    $child_tree_objects = \Drupal::entityTypeManager()->getStorage('taxonomy_term')->loadTree('rubrique', $parent_term_id);

    foreach ($child_tree_objects as $child_term) {
      // Access the child term's properties.
      $term_name = $child_term->name->value;
      // $allChild[] = $child_term->tid;
      unset($options[$child_term->tid]);
    } 
  }

  unset($options[$parent_term_id]);

  return $options;

}

public function sortTermIdByDateCreation ($res, $isTermSocial = false) {
  $docs = \Drupal::service('entity_type.manager')->getStorage('media')->loadMultiple($res);
  if ($docs) {

    uasort($docs, function($a, $b) {
      $timestampA = $a->get('created')->value;
      $timestampB = $b->get('created')->value;
      return $timestampB - $timestampA;
    });
    $current_timestamp = \Drupal::time()->getRequestTime();
    $newres = [];
    foreach($docs as $d ) {
      $two_years_ago_timestamp = strtotime('-2 years', $current_timestamp);
      if (($d->get('created')->value <= $two_years_ago_timestamp) && !$isTermSocial) {//si le document date d'il y a deux ans on ne l'affiche pas (sauf pour le rôle social)
        continue;
      }else {
        $newres[] = $d->id();
      }
    }
    return $newres;
  }
  return $res;
}

public function compareByDate($a, $b) {
  $dateA = $this->getNodeFieldValue($a, 'created'); // Replace 'dateProperty' with your date property name
  $dateB = $this->getNodeFieldValue($b, 'created'); // Replace 'dateProperty' with your date property name
  
  if ($dateA == $dateB) {
      return 0;
  }
  
  return ($dateA < $dateB) ? -1 : 1;
}

public function getFiliereLabels ($media) {
  $filieres = getNodeFieldValue($media, 'field_filieres');
  $filiere_label = '';
  if ($filieres) {
    $filieres = json_decode($filieres);
    $filieres = array_column($filieres, 'id');

    $filieresOption = \Civi\Api4\OptionValue::get(FALSE)
    ->addSelect('id', 'label')
    ->addWhere('option_group_id', '=', 163)
    ->execute()->getIterator();
    $filieresOption = iterator_to_array($filieresOption); 
    $filiere_label = '';
    foreach ($filieresOption as $fil) {
      foreach($filieres as $filiere) {
        if ($filiere == $fil['id']) {
          $filiere_label .= $fil['label'] . ', ';
        }
      }
    }
    $filiere_label = rtrim($filiere_label, ', ');
  }
  return $filiere_label;
}

public function isActive($string) {
  $class = '';
  $current_path = \Drupal::service('path.current')->getPath();
  if (strpos($current_path, $string) !== false) {
    $class = 'active';
  }
  return $class; 
}

public function notAdherentOrSocial  () {
   // Get the current user object.
   $current_user = \Drupal::currentUser();
   $user = \Drupal\user\Entity\User::load($current_user->id());
  $notAdherentOrSocial = true;
   // Get an array of role IDs for the current user.
   $user_roles = $current_user->getRoles();
   if (sizeof($user_roles) < 3 && (in_array('social', $user_roles) || in_array('adherent', $user_roles))) {
    $notAdherentOrSocial = false;
   }
   return $notAdherentOrSocial;
}

  public function getPreviousOnlyYear() {
    $today = date('Y-m-d'); // Get the current date (e.g., 2023-12-11)
    return date('Y-01-01', strtotime('-1 year', strtotime($today)));
  }

  public function allTypeCommercialViande() {
    $all_types = \Civi\Api4\OptionValue::get(FALSE)
      ->addSelect('value')
      ->addWhere('option_group_id', '=', 162)
      ->execute()->getIterator();
      
      $all_types = iterator_to_array($all_types);
      $all_types = array_column($all_types, 'value');
      return $all_types;
  }

  public function allTypeCertification () {
    $all_types = \Civi\Api4\OptionValue::get(FALSE)
    ->addSelect('value')
    ->addWhere('option_group_id', '=', 101)
    ->execute()->getIterator();
      
    $all_types = iterator_to_array($all_types);
    $all_types = array_column($all_types, 'value');
    return $all_types;
  }

  public function getPreviousYear () {
    return date('Y', strtotime('-1 year'));

  }

  public function formulaireDonneeEcoCheckIfThereIsDonneeGeneraleByCid($cid) {
    $hasValue = \Drupal::database()->query("select entity_id , dg_annee from civicrm_value_donnees_generales_annee where dg_annee like '%" . $this->getPreviousYear() . "%' and  entity_id =" . $cid)->fetch();
    return $hasValue;
  }

  public function createEmptyLineForDonneeGenerale ($cid) {
    $results = \Civi\Api4\CustomValue::create('donnees_generales_annee', FALSE)
    ->addValue('dg_annee', '2023-01-01')
    ->addValue('dg_ca', '')
    ->addValue('dg_ca_gms', '')
    ->addValue('dg_ca_bouchers', '')
    ->addValue('dg_ca_rdh', '')
    ->addValue('dg_ca_exp', '')
    ->addValue('dg_ca_grossiste', '')
    ->addValue('dg_ca_ind', '')
    ->addValue('entity_id', $cid)
    ->execute();
  }

  public function allTypeViande () {
    $all_types = \Civi\Api4\OptionValue::get(FALSE)
    ->addSelect('value')
    ->addWhere('option_group_id', '=', 159)
    ->execute()->getIterator();
      
    $all_types = iterator_to_array($all_types);
    $all_types = array_column($all_types, 'value');
    return $all_types;
  }

  public function allTypeAgrementSanitaire () {
    $all_types = \Civi\Api4\OptionValue::get(FALSE)
    ->addSelect('value')
    ->addWhere('option_group_id', '=', 158)
    ->execute()->getIterator();
      
    $all_types = iterator_to_array($all_types);
    $all_types = array_column($all_types, 'value');
    return $all_types;
  }

  public function allValueCertificationInPreviousYearByContact ($cid) {
    //todo previous year
    $custom_service = \Drupal::service('phenix_custom_block.view_services');
    $get_all_values = \Civi\Api4\Contact::get(FALSE)
      ->addSelect('custom_certifications_mgd.cert_certif')
      ->addJoin('Custom_certifications_mgd AS custom_certifications_mgd', 'LEFT')
      ->addWhere('id', '=', $cid)
      ->addWhere('custom_certifications_mgd.cert_annee', '=', $this->getPreviousOnlyYear())
      ->execute();
    $get_all_values = $get_all_values->getIterator();
    $get_all_values = iterator_to_array($get_all_values);
    
    $get_all_values = array_column($get_all_values, 'custom_certifications_mgd.cert_certif');
    return $get_all_values;
  }

  public function getContactNameById ($cid) {
    return \Civi\Api4\Contact::get(FALSE)
    ->addSelect('display_name')
    ->addWhere('id', '=', $cid)
    ->execute()->first()['display_name'];
  }

  public function allValueDecoupeInPreviousYearByContact ($cid) {
    //todo previous year
    $custom_service = \Drupal::service('phenix_custom_block.view_services');
    $get_all_values = \Civi\Api4\Contact::get(FALSE)
      ->addSelect('custom_prod_decoupe.decoupe_type_viandes')
      ->addJoin('Custom_prod_decoupe AS custom_prod_decoupe', 'LEFT')
      ->addWhere('id', '=', $cid)
      ->addWhere('custom_prod_decoupe.decoupe_annee', '=', $this->getPreviousOnlyYear())
      ->execute();
    $get_all_values = $get_all_values->getIterator();
    $get_all_values = iterator_to_array($get_all_values);
    
    $get_all_values = array_column($get_all_values, 'custom_prod_decoupe.decoupe_type_viandes');
    return $get_all_values;
  }

  public function allValueProduitCommercialiseInPreviousYearByContact ($cid) {
    //todo previous year
    $get_all_values = \Civi\Api4\Contact::get(FALSE)
      ->addSelect( 'custom_commercialisation.com_type_produit_commercial')
      ->addJoin('Custom_commercialisation AS custom_commercialisation', 'LEFT')
      ->addWhere('id', '=', $cid)
      ->addWhere('custom_commercialisation.com_annee', '=', $this->getPreviousOnlyYear())
      ->execute();
    $get_all_values = $get_all_values->getIterator();
    $get_all_values = iterator_to_array($get_all_values);
    
    $get_all_values = array_column($get_all_values, 'custom_commercialisation.com_type_produit_commercial');
    return $get_all_values;
  }

  public function allValueAchatInPreviousYearByContact ($cid) {
    //todo previous year
    $custom_service = \Drupal::service('phenix_custom_block.view_services');
    $get_all_values = \Civi\Api4\Contact::get(FALSE)
    ->addSelect('custom_prod_approv_achat.achat_type_viandes')
    ->addJoin('Custom_prod_approv_achat AS custom_prod_approv_achat', 'LEFT')
    ->addWhere('contact_type', '=', 'Organization')
    ->addWhere('id', '=', $cid)
    ->addWhere('custom_prod_approv_achat.achat_annee', '=', $this->getPreviousOnlyYear())
    ->execute()->getIterator();
    $get_all_values = iterator_to_array($get_all_values);
    $get_all_values = array_column($get_all_values, 'custom_prod_approv_achat.achat_type_viandes');
    return $get_all_values;
  }
  public function allValueAbattageInPreviousYearByContact ($cid) {
    //todo previous year
    $custom_service = \Drupal::service('phenix_custom_block.view_services');
    $prev_year = $this->getPreviousOnlyYear();
    $get_all_values = \Civi\Api4\Contact::get(FALSE)
    ->addSelect('custom_prod_approv_abattage.abattage_type_viandes')
    ->addJoin('Custom_prod_approv_abattage AS custom_prod_approv_abattage', 'LEFT')
    ->addWhere('id', '=', $cid)
    ->addWhere('custom_prod_approv_abattage.abattage_annee', '=', $prev_year)
    ->execute()->getIterator();
    $get_all_values = iterator_to_array($get_all_values);
    $get_all_values = array_column($get_all_values, 'custom_prod_approv_abattage.abattage_type_viandes');
    return $get_all_values;
  }
  public function allValueAgrementSanitaireDefaultValue ($cid) {
    //todo previous year
    $custom_service = \Drupal::service('phenix_custom_block.view_services');
    $prev_year = $this->getPreviousOnlyYear();
    $get_all_values = \Civi\Api4\Contact::get(FALSE)
      ->addSelect('custom_agrements_sanitaires.agrsan_type')
      ->addJoin('Custom_agrements_sanitaires AS custom_agrements_sanitaires', 'LEFT')
      ->addWhere('id', '=', $cid)
      ->execute()->getIterator();
    $get_all_values = iterator_to_array($get_all_values);
    $get_all_values = array_column($get_all_values, 'custom_agrements_sanitaires.agrsan_type');
    return $get_all_values;
  }

  /**
   * Permet de checker la permission de visualiser un media
   */
  public function checkPermissionMedia ($whiteListRole, $permissionMedia, $account) {
    $current_role = $account->getRoles();

    //Checker le role de l'utilisateur courant
    $hasRole = in_array($whiteListRole, $current_role);

    $request = \Drupal::request();
    
    //Si l'utilisateur n'est pas ni admin / Permanent / admin client / super user
    if ((!in_array('super_utilisateur', $current_role) && !in_array('admin_client', $current_role) && !in_array('permanent', $current_role) && !in_array('administrator', $current_role))) {
      //tester d'abord s'il y a du mid
      if ($request->attributes->get('media') && $request->attributes->get('media')->get('mid')){
        $media_id = $request->attributes->get('media')->get('mid')->getValue()[0]['value'];
        $mediaObject = \Drupal\media\Entity\Media::load($media_id);
        
        $documentPermission = $this->getNodeFieldValue($mediaObject, $permissionMedia);
        
        if ($documentPermission) {
          if ($hasRole) {
            return  \Drupal\Core\Access\AccessResult::allowed();
          }else {
            $response = new \Symfony\Component\HttpFoundation\RedirectResponse('/');
            $response->send();
          }
        }
      }
    } 
  }

  public function isTermLinkedWithMenu ($term_id) {
    $term = \Drupal\taxonomy\Entity\Term::load($term_id);
    // Check if the term entity is valid
    if ($term) {
      // Check if there are menu links associated with the term
      // $menu_link_manager = \Drupal::service('plugin.manager.menu.link');
      // $menu_links = $menu_link_manager->loadLinksByRoute('entity.taxonomy_term.canonical', ['taxonomy_term' => $term_id]);

      $title = $this->getNodeFieldValue($term, 'name');
      $title = str_replace("'", "''", $title);
      $query = "select enabled from menu_link_content_data where title =  '$title'"; 
      if (\Drupal::database()->query($query)) {

        if (\Drupal::database()->query($query)) {
          $fetched  = \Drupal::database()->query($query)->fetchAll();
          $fetched = array_column($fetched, 'enabled');
          return in_array('1', $fetched);
        }
      } 

      return false;
    }
  } 
  
  public function getIdParagraphesWhereTitreLikeKeyWord($keyWord) {
    //Récuperer le term parent qui est lié à ce paragraphe
    $query = "select TAX.entity_id as tid, PAR.entity_id as pid from paragraph__field_titre as PAR LEFT JOIN taxonomy_term__field_dossier  as TAX ON PAR.entity_id = TAX.field_dossier_target_id where field_titre_value like '%$keyWord%'";
    $datas = \Drupal::database()->query($query)->fetchAll();
    $whiteListparaId = [];

    //Vérifier si le term est lié au menu
    foreach ($datas as $data) {
      $isLinkedWithMenu = $this->isTermLinkedWithMenu($data->tid);
      
      $isTermSocial = $this->isTermSocial($data->tid);
      if ($isLinkedWithMenu  && !$isTermSocial ) {
        $documentId = \Drupal::database()->query('select field_document_target_id from paragraph__field_document where entity_id = ' . $data->pid . '')->fetchAll();
        if ($documentId) {
          $currentTermId = 0;
          foreach($documentId as $docId) {
            $media = Media::load($docId->field_document_target_id);
            $created = $this->getNodeFieldValue($media, 'created');
            $now = time();
            $twoYearsAgo = strtotime("-2 years", $now);
            $date = date("d-m-Y", $created);
            // Convertir la date donnée en timestamp
            $dateTimestamp = strtotime($date);
            // Obtenir le timestamp actuel
            $now = time();
            // Calculer le timestamp pour il y a exactement deux ans à partir de maintenant
            $twoYearsAgo = strtotime("-2 years", $now);
            // Vérifier si la date donnée est plus de deux ans dans le passé
            if (($dateTimestamp > $twoYearsAgo) && ($currentTermId != $data->tid)) {
              $whiteListparaId[] = $data->pid;
              $currentTermId = $data->tid;
            }
          }
        }
      }
    }

    return $whiteListparaId;
  }

    /**
   * 
   */
  public function getAllIdParagrapheWhenContentLikeKeyword ($keyword) {
    $query=  " select entity_id from paragraph__field_texte_formate where field_texte_formate_value like '%" . $keyword . "%'";
    $allEntityId = \Drupal::database()->query($query)->fetchCol('entity_id');
    $whiteListEntityId = [];
    foreach ($allEntityId as $entityId) {
      $termId = \Drupal::database()->query('select entity_id from taxonomy_term__field_dossier where field_dossier_target_id = ' . $entityId)->fetch()->entity_id;
      
      $isLinkedWithMenu = $this->isTermLinkedWithMenu($termId);
      $isTermSocial = $this->isTermSocial($termId);
      if ($isLinkedWithMenu && !$isTermSocial) {
        $whiteListEntityId[] = $entityId;
      }
    }
    return $whiteListEntityId;
  }


  public function isTermSocial ($termId) {
    $termObj = Term::load($termId);
    $isTermSocial = false;
    if ($termObj) {
      $isTermSocial = $this->getNodeFieldValue($termObj, 'field_social');
      return $isTermSocial;
    }
    return $isTermSocial;
  }


}
