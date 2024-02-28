<?php

namespace Drupal\phenix_custom_block\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Form\FormBuilder;
use Drupal\Core\Language\LanguageManager;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Drupal\Component\Utility\Xss;
use Drupal\Core\Entity\Element\EntityAutocomplete;
use Drupal\webform\Entity\Webform;

/**
 * Defines FormulaireController class.
 */
class FormulaireController extends ControllerBase
{
  const ID_TYPE_ACTIVITE_UPDATE_ECO_DATA = 184;
  const ASSIGN_TO = 2696;
  public function infoEntreprise() {
    $customService = \Drupal::service('phenix_custom_block.view_services');
    $req = \Drupal::request();
    \Drupal::service('civicrm')->initialize();
    $subject = "Formulaire de données économiques de l'entreprise";
    $cid =  $req->query->get('cid');
    $whoFilledTheForm =  $req->query->get('Cname');
    $organisationName =  $customService->getContactNameById ($cid);
    $userMail = $req->query->get('usermail');
    
    $details['Entreprise : '] = $organisationName;
    $details['Nozzm de la personne ayant renseigné le formulaire : '] = $whoFilledTheForm;
    $details['Email de la personne ayant renseigné le formulaire : '] = $userMail;
    $source_contact_id = $this->getCIDbyEmail($userMail);
    if ($source_contact_id) {
      \Drupal::service('session')->set('contact_who_filled' . $cid, $source_contact_id);
      \Drupal::service('session')->set('current_ contact_id', $cid);
      $this->createActivity($cid, $subject, $details, $source_contact_id);
    }else {
      \Drupal::service('session')->set('current_ contact_id', $cid);
      $this->createActivity($cid, $subject, $details, false);
    }
    
    return new JsonResponse(['activity' => 'created activity agrement sanitaire']);
  }
  
  public function storeCIDinSession () {
    $req = \Drupal::request();
    $cid =  $req->query->get('contact_id');
    $cid = is_array($cid) ? $cid[1] : $cid;
    if($cid) {
    }
    \Drupal::service('session')->set('current_ contact_id', $cid);
    return new JsonResponse(['cid' => $cid]);
  }

  
  public function certificationActivity () {
    $req = \Drupal::request();
    $details = [];
    if ($req->query->get('valeur')) {
      \Drupal::service('civicrm')->initialize();
      $valeur_edited = json_decode($req->query->get('valeur'));
      $cid = $valeur_edited->entity_id;
      $organizationName  = $this->getOrganizationName($cid);
      $source_contact_id = \Drupal::service('session')->get('contact_who_filled' . $cid);
      $subject = "Formulaire de données économiques de l'entreprise";
      $certificationId = $valeur_edited->id;

      $certificationLabel = \Civi\Api4\CustomValue::get('certifications_mgd', FALSE)
        ->addSelect('cert_certif:label')
        ->addWhere('id', '=', $certificationId)
        ->execute()->first()['cert_certif:label'];

      $details['Entreprise : '] = $organizationName;
      $certificationPrecision = $valeur_edited->cert_precision;
      $details['Certification (' . $this->getLastYear() . ') : '] = '<p>Activité : ' . $certificationLabel .', Précision : '. $certificationPrecision .'</p>';
      $this->createActivity ($cid, $subject, $details, $source_contact_id);
    }
    return new JsonResponse(['tes' => 'true']);
  }  
  
  public function produitCommerciauxCalculTotal () {
    $req = \Drupal::request();
    $details = [];
    $data = $req->query->get('valeur');
    \Drupal::service('civicrm')->initialize();
    if ($data) {
      $data = json_decode($data);

      $idOfEditedLine = $data->id;
      $organisationId = $data->entity_id;

      $total = $this->calculeTotal($idOfEditedLine);

      $results = \Civi\Api4\CustomValue::update('commercialisation', TRUE)
        ->addValue('com_total', $total)
        ->addWhere('id', '=', $idOfEditedLine)
        ->execute();

    }

    return new JsonResponse(['tes' => 'true']);
  }

  private function calculeTotal ($idOfLine) {
    \Drupal::service('civicrm')->initialize();
    $commercialisations = \Civi\Api4\CustomValue::get('commercialisation', FALSE)
        ->addSelect('com_boeuf', 'com_veau', 'com_porc', 'com_agneau', 'com_caprin', 'com_melange')
        ->addWhere('id', '=', $idOfLine)
        ->execute()->first();

      unset($commercialisations['id']);
      $commercialisations = array_sum($commercialisations);
      return $commercialisations;
  }

  
  public function abattageActivity () {
    $req = \Drupal::request();
    $details = [];

    if ($req->query->get('valeur')) {
      \Drupal::service('civicrm')->initialize();
      $valeur_edited = json_decode($req->query->get('valeur'));
      $idAbattage = $valeur_edited->id;
      $cid = $valeur_edited->entity_id;
      $organizationName  = $this->getOrganizationName($cid);
      $source_contact_id = \Drupal::service('session')->get('contact_who_filled' . $cid);
      $subject = "Formulaire de données économiques de l'entreprise";
      // $label = $this->getLabelElement($valeur_edited, 75);
      $details['Entreprise : '] = $organizationName;
      $editedAbattage = $this->abattageEditedLine($idAbattage);
      $label = '';
      if ($editedAbattage) {
        $label = $editedAbattage['abattage_type_viandes:label'] . ' : ' . $editedAbattage['abattage_tonnage_abattu'];
      }
      $details['Valeur modifié pour abattage :  (' . $this->getLastYear() . ') : '] = $label;
      $this->createActivity ($cid, $subject, $details, $source_contact_id);
    }
    return new JsonResponse(['tes' => 'true']);
  }  

  private function abattageEditedLine ($idAbattage) {
    return \Civi\Api4\CustomValue::get('prod_approv_abattage', FALSE)
      ->addSelect('abattage_type_viandes:label', 'abattage_tonnage_abattu')
      ->addWhere('id', '=', $idAbattage)
      ->execute()->first();
  }

  
  public function donneeGeneraleActivity () {
    $req = \Drupal::request();
    $details = [];
    if ($req->query->get('valeur')) {
      \Drupal::service('civicrm')->initialize();
      $valeur_edited = json_decode($req->query->get('valeur'));
      $cid = $valeur_edited->entity_id;
      $organizationName  = $this->getOrganizationName($cid);
      $source_contact_id = \Drupal::service('session')->get('contact_who_filled' . $cid);
      $subject = "Formulaire de données économiques de l'entreprise";
      $label = $this->getLabelElement($valeur_edited, 75);
      $details['Entreprise : '] = $organizationName;
      $details['Données générales (' . $this->getLastYear() . ') : '] = $label;

      $this->createActivity ($cid, $subject, $details, $source_contact_id);
    }
    return new JsonResponse(['tes' => 'true']);
  }  
  
  public function agrementSanitaire () {
    $req = \Drupal::request();
    $details = [];
    if ($req->query->get('valeur')) {
      \Drupal::service('civicrm')->initialize();
      $valeur_edited = json_decode($req->query->get('valeur'));
      
      $cid = $valeur_edited->entity_id;
      $argSanId = $valeur_edited->id;
      $agrsanNumero = $valeur_edited->agrsan_numero;

      $agrementsSanitaireType = \Civi\Api4\CustomValue::get('agrements_sanitaires', FALSE)
        ->addSelect('agrsan_type:label')
        ->addWhere('id', '=', $argSanId)
        ->execute()->first()['agrsan_type:label'];

      $organizationName  = $this->getOrganizationName($cid);
      $source_contact_id = \Drupal::service('session')->get('contact_who_filled' . $cid);
      $subject = "Formulaire de données économiques de l'entreprise";
      $details['Entreprise : '] = $organizationName;
      $details['Agréments sanitaire  : '] = '<p>' . $agrementsSanitaireType . ' : ' . $agrsanNumero . '</p>';
      $this->createActivity ($cid, $subject, $details, $source_contact_id);
    }
    return new JsonResponse(['tes' => 'true', 'cid' => $cid]);
  }  

  
  public function produitCommercialisesActivity () {
    $req = \Drupal::request();
    $details = [];
    if ($req->query->get('valeur')) {
      \Drupal::service('civicrm')->initialize();
      $valeur_edited = json_decode($req->query->get('valeur'));
      $cid = $valeur_edited->entity_id;
      $organizationName  = $this->getOrganizationName($cid);
      $source_contact_id = \Drupal::service('session')->get('contact_who_filled' . $cid);
      $subject = "Formulaire de données économiques de l'entreprise";
      $label = $this->getLabelElement($valeur_edited, 79);
      $details['Entreprise : '] = $organizationName;
      
      $details['Produits commercialisés (' . $this->getLastYear() . ') : '] = $label;

      $this->createActivity ($cid, $subject, $details, $source_contact_id);
    }
    return new JsonResponse(['tes' => 'true']);
  }  

  public function achatViandeActivity () {
    $req = \Drupal::request();
    $details = [];
    if ($req->query->get('valeur')) {
      \Drupal::service('civicrm')->initialize();
      $valeur_edited = json_decode($req->query->get('valeur'));
      $cid = $valeur_edited->entity_id;
      $organizationName  = $this->getOrganizationName($cid);
      $source_contact_id = \Drupal::service('session')->get('contact_who_filled' . $cid);
      $subject = "Formulaire de données économiques de l'entreprise";
      $label = $this->getLabelElement($valeur_edited, 77);
      $details['Entreprise : '] = $organizationName;
      $details[' Production - Approvisionnement - achat viande (' . $this->getLastYear() . ') : '] = $label;
      $this->createActivity ($cid, $subject, $details, $source_contact_id);
    }
    return new JsonResponse(['tes' => 'true']);
  }

  public function transformationDecoupeActivity () {
    $req = \Drupal::request();
    $details = [];
    if ($req->query->get('valeur')) {
      \Drupal::service('civicrm')->initialize();
      $valeur_edited = json_decode($req->query->get('valeur'));
      $cid = $valeur_edited->entity_id;
      $idCustomProduit = $valeur_edited->id;
      $organizationName  = $this->getOrganizationName($cid);
      $source_contact_id = \Drupal::service('session')->get('contact_who_filled' . $cid);
      $subject = "Formulaire de données économiques de l'entreprise";
      $label = $this->getLabelElement($valeur_edited, 78);
      $details['Entreprise : '] = $organizationName;
      $typeOfViande = $this->getTypeViandeByDecoupe ($idCustomProduit);
      $details[' Production - Découpe et transformation (' . $this->getLastYear() . ') : '] = '<p> Pour ' . $typeOfViande . ' : ' .$label;
      $this->createActivity ($cid, $subject, $details, $source_contact_id);
    }
    return new JsonResponse(['tes' => 'true']);
  }

  public function effectifAnnuelActivity () {
    $elements = $this->getEditedValueDecoded();
    $subject = "Formulaire de données économiques de l'entreprise";
    $details['Entreprise : '] = $elements['organization_name'];
    $details[' Effectif annuel pour l\'annee ' . $this->getLastYear() . ' est : '] = '' . $elements['valeur_modifiee'];
    $isCreated = $this->createActivity ($elements['cid'], $subject, $details, $elements['source_contact_id']);
    return new JsonResponse(['tes' => $isCreated]);
  }

  private function getEditedValueDecoded() {
    $req = \Drupal::request();
    $elements = [];
    if ($req->query->get('valeur')) {
      \Drupal::service('civicrm')->initialize();
      $valeur_edited = json_decode($req->query->get('valeur'));
      $cid = $valeur_edited->entity_id;
      $elements['cid']  = $cid;
      $elements['valeur_modifiee']  = $valeur_edited->Effectif_annee;
      $elements['organization_name'] = $this->getOrganizationName($cid);
      $elements['source_contact_id'] = \Drupal::service('session')->get('contact_who_filled' . $cid);
      $elements['valeur_edited'] = $valeur_edited;
    }

    return $elements;
  }

  public function getTypeViandeByDecoupe ($idCustomProduit) {
    $prodDecoupes = \Civi\Api4\CustomValue::get('prod_decoupe', FALSE)
      ->addSelect('decoupe_type_viandes:label')
      ->addWhere('id', '=', $idCustomProduit)
      ->execute()->first()['decoupe_type_viandes:label'];

    return $prodDecoupes;
  }

  /**
   * TODO HERE 
   */
  public function getOrganisationId () {
    
    $req = \Drupal::request();
    $cid = $req->get('contact_id');
    \Drupal::service('civicrm')->initialize();
    $results = \Civi\Api4\AuthxCredential::create(FALSE)
    ->setContactId($cid)
    ->execute()->first()['cred'];
    
    return new JsonResponse(['tes' => 'true', 'res' => $results]);
  }

 /**
  * Permet de créer une activité
  */
 public function createActivity ($cid, $subject, $details, $source_contact_id) {
   $html = '';
   if ($cid) {

     foreach ($details as $keyDetail => $valueDetail) {
       if ($valueDetail) {
         $html .= $keyDetail . '<br>';
         $html .= $valueDetail . '<br>';
       }
     }
     \Drupal::service('civicrm')->initialize();
     if ($source_contact_id) {

       return \Civi\Api4\Activity::create(FALSE)
       ->addValue('activity_type_id', self::ID_TYPE_ACTIVITE_UPDATE_ECO_DATA)
       ->addValue('subject', $subject)
       ->addValue('assignee_contact_id', [self::ASSIGN_TO])
       ->addValue('target_contact_id', [
         $cid, $source_contact_id
         ])
         ->addValue('status_id', 2)//status = fait ; 1 à faire
         ->addValue('details',  $html)
         ->addValue('source_contact_id', $cid)
         ->execute();
      }
        return \Civi\Api4\Activity::create(FALSE)
       ->addValue('activity_type_id', self::ID_TYPE_ACTIVITE_UPDATE_ECO_DATA)
       ->addValue('subject', $subject)
       ->addValue('assignee_contact_id', [self::ASSIGN_TO])
         ->addValue('status_id', 2)//status = fait ; 1 à faire
         ->addValue('details',  $html)
         ->addValue('source_contact_id', $cid)
         ->execute();
   }
 }

 private function getLastYear () {
  // Get the current year
  $currentYear = date('Y');

  // Calculate the previous year
  return $currentYear - 1;
 }

 private function getLabelElement ($donneGeneralObject, $groupId) {
    $html = '';
    foreach ($donneGeneralObject as $key => $elementId) {
      if ($key != 'entity_id' && $key != 'id') {
        $allElementDonneGeneral = \Civi\Api4\CustomField::get(FALSE)
          ->addSelect('custom_group_id:name', 'custom_group_id:label', 'name')
          ->addWhere('custom_group_id', '=', $groupId)
          ->execute()->getIterator();
        $allElementDonneGeneral = iterator_to_array($allElementDonneGeneral); 
        $allElementDonneGeneral = array_values($allElementDonneGeneral); 

        foreach($allElementDonneGeneral as $keyDonneGeneral => $labelDonneGeneral) {
          if ($labelDonneGeneral['name'] == $key) {
            $html = '<p>' .  $labelDonneGeneral['custom_group_id:label'] . ' : ' . $elementId . '</p>';
          }
        }

      }
    }
    return $html;
 }
 
 private function getOrganizationName($cid) {
    $name =  \Civi\Api4\Contact::get(FALSE)
      ->addSelect('display_name')
      ->addWhere('id', '=', $cid)
      ->execute()->first()['display_name'];
    return $name;
 }

 private function getCIDbyEmail ($email) {
  \Drupal::service('civicrm')->initialize();
  return  \Civi\Api4\Contact::get(FALSE)
  ->addSelect('id')
  ->addWhere('email_primary.email', '=', $email)
  ->execute()->first()['id'];
 }


}
