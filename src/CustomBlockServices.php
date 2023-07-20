<?php


namespace Drupal\phenix_custom_block;

use Drupal\Core\Session\AccountInterface;

/**
 * Class PubliciteService
 * @package Drupal\phenix_custom_block\Services
 */
class CustomBlockServices {

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
    public function getAllMyGroup () {
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


    /**
     * Recupère tous les reunions à venir
     * 
     */
    public function getAllMeetings () {
        $query = "SELECT
        Event.start_date AS event_start_date,
        civicrm_contact.id AS id,
        Event.id AS event_id, Event.title as event_title
      FROM
        civicrm_contact
      INNER JOIN civicrm_event AS Event ON civicrm_contact.id = Event.created_id
      WHERE
        (DATE_FORMAT((Event.start_date + INTERVAL 7200 SECOND), '%Y-%m-%dT%H:%i:%s') >= DATE_FORMAT(('2023-07-18T22:00:00' + INTERVAL 7200 SECOND), '%Y-%m-%dT%H:%i:%s'))
        AND (Event.is_active = '1') limit 3
      ";
      $results =  \Drupal::database()->query($query)->fetchAll();

      return $results;
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
}
