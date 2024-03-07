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
    if ($userMail) {
      \Drupal::service('session')->set('mail_of_user_who_filled_the_form', $userMail);
    }
    $details['Entreprise : '] = $organisationName;
    $details['Nom de la personne ayant renseigné le formulaire : '] = $whoFilledTheForm;
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
    
    return new JsonResponse(['activity' => 'created activity info entreprise']);
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

      $this->createHtmlDetailActivity ($details, $cid, '/civicrm/donnees-economique-entreprise-donnee-generale', ' Données générales ', '<p>Données générales (' . $this->getLastYear() . ') : ' .  $label . '</p>');

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
      $subject = "Formulaire de données économiques de l'entreprise";
      $cid = $req->query->get('cid');
      $argSanId = $valeur_edited->id;
      $agrsanNumero = $valeur_edited->agrsan_numero;

      $agrementsSanitaireType = \Civi\Api4\CustomValue::get('agrements_sanitaires', FALSE)
        ->addSelect('agrsan_type:label')
        ->addWhere('id', '=', $argSanId)
        ->execute()->first()['agrsan_type:label'];

      $this->createHtmlDetailActivity ($details, $cid, '/civicrm/donnees-economique-entreprise-agrement-sanitaire', ' Agrément sanitaire ', '<p>' . $agrementsSanitaireType . ' : ' . $agrsanNumero . '</p>');
        
      // $details['Agréments sanitaire  : '] = '<p>' . $agrementsSanitaireType . ' : ' . $agrsanNumero . '</p>';
      $this->createActivity ($cid, $subject, $details, $source_contact_id);
    }
    return new JsonResponse(['tes' => 'true', 'cid' => $cid]);
  }  

  private function createHtmlDetailActivity (&$details, $cid, $urlTab, $tabName, $dataEdited) {
    $customService = \Drupal::service('phenix_custom_block.view_services');
    $req = \Drupal::request();
    $checksum = get_checksum($cid);
    $urlTab = '<a href="' . $urlTab . '?cs=' . $checksum . '&_authx=' . get_credential_authx($cid) . '&_authxSes=1#?id=' . $cid . '">url</a>';
    $details['<h2>Onglet ' . $tabName . '</h2>'] = '<h4 class="boosturl">Url pour y accéder : ' . $urlTab . '</h4>';
    $subject = "Formulaire de données économiques de l'entreprise";
    $details['<p> Entreprise : '] = $customService->getContactNameById($cid) . '</p>';
    $elements['organization_name'];
    $details['Les informations de qui ont été modifiés pour cet onglet'] = $dataEdited;

    $whoFilledTheForm =  \Drupal::service('session')->get('contact_who_filled_the_form');
    $userMail = \Drupal::service('session')->get('mail_of_user_who_filled_the_form');
    
    $details['<p>Nom de la personne ayant renseigné le formulaire : '] = $whoFilledTheForm . '</p>';
    $details['<p>Email de la personne ayant renseigné le formulaire : '] = $userMail . '</p>';

    return $details;
  }

  /**
   * 
   */
  public function getChecksumAndAuthx () {
        $req = \Drupal::request();
        \Drupal::service('civicrm')->initialize();
        $customService = \Drupal::service('phenix_custom_block.view_services');
        $cid = $req->query->get('cid');
        if ($cid) {
          $cid = intval($cid);
          $checksum = $customService->getChecksum ($cid);
          $authx = $customService->getCredentialAuthx ($cid);
          return new JsonResponse(['checksum' => $checksum, 'authx' => $authx]);
        }
        return new JsonResponse(['checksum' => false, 'authx' => true]);
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

      $this->createHtmlDetailActivity ($details, $cid, '/civicrm/donnees-economique-entreprise-produit-commercialises', ' Produits commercialisés ', '  <p> Produits commercialisés (' . $this->getLastYear() . ') : ' . $label . '</p>');

      $this->createActivity ($cid, $subject, $details, $source_contact_id);
    }
    return new JsonResponse(['tes' => 'true']);
  }  

  public function achatViandeActivity () {
    $req = \Drupal::request();
    $details = [];
    $customService = \Drupal::service('phenix_custom_block.view_services');
    if ($req->query->get('valeur')) {
      \Drupal::service('civicrm')->initialize();
      $valeur_edited = json_decode($req->query->get('valeur'));
      $cid = $req->query->get('cid');
      $organizationName  = $this->getOrganizationName($cid);
      $source_contact_id = \Drupal::service('session')->get('contact_who_filled' . $cid);
      $subject = "Formulaire de données économiques de l'entreprise";
      $label = $this->getLabelElement($valeur_edited, 77);
      
      $this->createHtmlDetailActivity ($details, $cid, '/civicrm/donnees-economique-entreprise-achat-viande', ' Achat de viande ', ' <p>Production - Approvisionnement - achat viande (' . $this->getLastYear() . ') : ' . $label . '</p>');

      $this->createActivity ($cid, $subject, $details, $source_contact_id);
    }
    return new JsonResponse(['tes' => 'true']);
  }

  public function transformationDecoupeActivity () {
    $req = \Drupal::request();
    $details = [];
    $customService = \Drupal::service('phenix_custom_block.view_services');
    if ($req->query->get('valeur')) {
      \Drupal::service('civicrm')->initialize();
      $valeur_edited = json_decode($req->query->get('valeur'));
      $cid = $req->query->get('cid');
      $idCustomProduit = $valeur_edited->id;
      $organizationName  = $this->getOrganizationName($cid);
      $source_contact_id = \Drupal::service('session')->get('contact_who_filled' . $cid);
      $subject = "Formulaire de données économiques de l'entreprise";
      $label = $this->getLabelElement($valeur_edited, 78);

      $this->createHtmlDetailActivity ($details, $cid, '/civicrm/donnees-economique-entreprise-transformation-decoupe', ' Découpe et transformation ', '  <p>Production - Découpe et transformation (' . $this->getLastYear() . ') :  ' . $typeOfViande . ' : ' .$label . ' </p>');

      $typeOfViande = $this->getTypeViandeByDecoupe ($idCustomProduit);
      $this->createActivity ($cid, $subject, $details, $source_contact_id);
    }
    return new JsonResponse(['tes' => 'true']);
  }

  public function effectifAnnuelActivity () {
    $elements = $this->getEditedValueDecoded();

     //créer une url pour rediriger vers l'onglet 
    $cid = $elements['cid'];
    $checksum = get_checksum($cid);
    $url_tab = '<a href="/civicrm/donnees-economique-entreprise-effectif-annuel?cs=' . $checksum . '#?id=' . $cid . '">url</a>';

    $details['<h2>Onglet "Effectif"</h2>'] = '<h4>Url pour y accéder : ' . $url_tab . '</h4>';
    $subject = "Formulaire de données économiques de l'entreprise";
    $details['<p> Entreprise : '] = $elements['organization_name'] . '</p>';
    $elements['organization_name'];
    $details['Les informations de qui ont été modifiés pour cet onglet'] = '<p> Effectif annuel pour l\'annee ' . $this->getLastYear() . ' est : ' . $elements['valeur_modifiee'] . '</p>';
    $actual_session_activity = \Drupal::service('session')->get('all_activity');
    $effectif_activity_detail = '<p> Effectif annuel pour l\'annee ' . $this->getLastYear() . ' est : ' . $elements['valeur_modifiee'] . '</p>';
    $this->detailAddWhoFilledTheForm ($details);
    \Drupal::service('session')->set('all_activity', $actual_session_activity . $effectif_activity_detail);

    $isCreated = $this->createActivity ($elements['cid'], $subject, $details, $elements['source_contact_id']);
    return new JsonResponse(['tes' => $isCreated]);
  }

  private function detailAddWhoFilledTheForm (&$details) {
    $whoFilledTheForm =  \Drupal::service('session')->get('contact_who_filled_the_form');
    $userMail = \Drupal::service('session')->get('mail_of_user_who_filled_the_form');
    $details['<p class="who-fill">Nom de la personne ayant renseigné le formulaire : '] = $whoFilledTheForm . '</p>';
    $details['<p class="email-who-fill">Email de la personne ayant renseigné le formulaire : '] = $userMail. '</p>';
    return $details;
  }


  public function listContactActivity () {
    $elements = $this->getEditedValueDecoded();
    $req = \Drupal::request();
     //créer une url pour rediriger vers l'onglet 
    $cid = $req->query->get('cid');
    $checksum = get_checksum($cid);
    $url_tab = '<a href="/civicrm/bulletin-cotisation-contact-entreprise?cs=' . $checksum . '&_authx=' . get_credential_authx($cid) . '&_authxSes=1#?id=' . $cid . '">url</a>';

    $subject = "Formulaire de données économiques de l'entreprise";
    $actual_session_activity = \Drupal::service('session')->get('all_activity');

    $this->createHtmlDetailActivity ($details, $cid, '/civicrm/bulletin-cotisation-contact-entreprise', ' Contacts ', '<p> Le contact qui a été modifiés est ' . $elements['valeur_edited']->sort_name . '</p>');

    $contact_modifie = '<p> Le contact qui a été modifiés est  : ' . $elements['valeur_edited']->sort_name . '</p>';
    \Drupal::service('session')->set('all_activity', $actual_session_activity . $effectif_activity_detail);

    $isCreated = $this->createActivity ($cid, $subject, $details, $elements['source_contact_id']);
    return new JsonResponse(['tes' => $isCreated]);
  }

  public function dirigeantActivity () {
    $req = \Drupal::request();
    $customService = \Drupal::service('phenix_custom_block.view_services');
    $elements = $this->getEditedValueDecoded();
    $cid = $req->query->get('cid');
    // $details[' information dirigeant modifié : '] = 'qsfdgqdgqfd' . $elements['valeur_modifiee'];
    $actual_session_activity = \Drupal::service('session')->get('all_activity');
    
    //changement de la fonction
    if ($elements['valeur_edited']->fonction ) {
      \Drupal::service('civicrm')->initialize();
      $all_function = '';
      foreach ($elements['valeur_edited']->fonction as $id_fonction) {
        
        $indiviualContactFonctions = \Civi\Api4\CustomValue::get('indiviual_contact_fonction', FALSE)
        ->addSelect('fonction:label')
        ->addWhere('fonction', '=', $id_fonction)
        ->execute()->first()['fonction:label'][0];
        if ($indiviualContactFonctions ) {
          $all_function .= $indiviualContactFonctions . ', ';
        }
      }

      $all_function = trim($all_function, ', ');
      
      //activité Seulement pour "fonction" pour le moment

    $checksum = get_checksum($cid);
    // $url_tab = '<a href="/civicrm/bulletin-de-cotisation-dirigeants?cs=' . $checksum . '&_authx=' . get_credential_authx($cid) . '&_authxSes=1#?id=' . $cid . '">url</a>';
    $url_tab = '<a href="/civicrm/bulletin-de-cotisation-dirigeants?cs=' . $checksum . '#?id=' . $cid . '">url</a>';

    $details['<h2>Onglet "Dirigeants"</h2>'] = '<h4 class="boosturl">Url pour y accéder : ' . $url_tab . '</h4>';
    $subject = "Formulaire de données économiques de l'entreprise";
    $details['<p> Entreprise : '] = $customService->getContactNameById($cid) . '</p>';
    $elements['organization_name'];
    $effectif_activity_detail = '<p> information dirigeant modifié : ' . $all_function . '</p>';
    $details['Les informations de qui ont été modifiés pour cet onglet'] = '<p>Fonctions :  ' . $all_function . '</p>';

    $isCreated = $this->createActivity ($cid, $subject, $details, $elements['source_contact_id']);
    \Drupal::service('session')->set('all_activity', $actual_session_activity . $effectif_activity_detail);
    }
    
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

  public function verifyToken () {
    $req = \Drupal::request();
    $cid = $req->get('contact_id');
    $checksum = $req->get('checksum');
    $hasToken = false;
    \Drupal::service('civicrm')->initialize();
    if ($cid) {

      $isValidChecksum = \Civi\Api4\Contact::validateChecksum(FALSE)
        ->setContactId($cid)  
        ->setChecksum($checksum)
        ->execute()->first()['valid'];
      if ($isValidChecksum) {
        $hasToken = true;
      }
    }

    return new JsonResponse(['hasToken' => $hasToken,]);
  }

  /**
   * TODO HERE 
   */
  public function getOrganisationId () {
    
    $req = \Drupal::request();
    $cid = $req->get('contact_id');
    $url = $req->get('url');
    // dump($req, $_SERVER, $url, explode('?cs=', $url), $_SERVER['HTTP_HOST']);

    $customService = \Drupal::service('phenix_custom_block.view_services');
    \Drupal::service('civicrm')->initialize();
    $authx = \Civi\Api4\AuthxCredential::create(FALSE)
    ->setContactId($cid)
    ->execute()->first()['cred'];

    $checksum = $customService->getChecksum($cid);

    switch($url) {
      case (strpos($url, 'effectif-annuel') !== false):
        $rightUrl = '/civicrm/donnees-economique-entreprise-effectif-annuel?cs=' . $checksum . '&_authx=' . $authx . '&_authxSes=1#?id=' . $cid;
        break;
      case (strpos($url, 'cotisation-dirigeants') !== false):
        $rightUrl = '/civicrm/bulletin-de-cotisation-dirigeants?cs=' . $checksum . '&_authx=' . $authx . '&_authxSes=1#?Contact_Custom_indiviual_contact_fonction_entity_id_01.entreprise=' . $cid;
        break;
      case (strpos($url, 'liste-abonnement') !== false):
        $rightUrl = '/civicrm/buttetin-cotisation-liste-abonnement?cs=' . $checksum . '&_authx=' . $authx . '&_authxSes=1#?id=' . $cid;
        break;
      case (strpos($url, 'contact-entreprise') !== false):
        $rightUrl = '/civicrm/bulletin-cotisation-contact-entreprise?cs=' . $checksum . '&_authx=' . $authx . '&_authxSes=1#?id=' . $cid;
        break;
      case (strpos($url, '-agrement-sanitaire') !== false):
        $rightUrl = '/civicrm/donnees-economique-entreprise-agrement-sanitaire?cs=' . $checksum . '&_authx=' . $authx . '&_authxSes=1#?id=' . $cid;
        break;
      case (strpos($url, 'donnee-generale') !== false):
        $rightUrl = '/civicrm/donnees-economique-entreprise-donnee-generale?cs=' . $checksum . '&_authx=' . $authx . '&_authxSes=1#?id=' . $cid;
        break;
      case (strpos($url, 'entreprise-abattages') !== false):
        $rightUrl = '/civicrm/donnees-economique-entreprise-abattages?cs=' . $checksum . '&_authx=' . $authx . '&_authxSes=1#?id=' . $cid;
        break;
      case (strpos($url, 'achat-viande') !== false):
        $rightUrl = '/civicrm/donnees-economique-entreprise-achat-viande?cs=' . $checksum . '&_authx=' . $authx . '&_authxSes=1#?id=' . $cid;
        break;
      case (strpos($url, 'transformation-decoupe') !== false):
        $rightUrl = '/civicrm/donnees-economique-entreprise-transformation-decoupe?cs=' . $checksum . '&_authx=' . $authx . '&_authxSes=1#?id=' . $cid;
        break;
      case (strpos($url, 'produit-commercialises') !== false):
        $rightUrl = '/civicrm/donnees-economique-entreprise-produit-commercialises?cs=' . $checksum . '&_authx=' . $authx . '&_authxSes=1#?id=' . $cid;
        break;
      case (strpos($url, 'formulaire-certification') !== false):
        $rightUrl = '/civicrm/donnees-economique-entreprise-formulaire-certification?cs=' . $checksum . '&_authx=' . $authx . '&_authxSes=1#?Organization1=' . $cid;;
        break;
      case (strpos($url, 'activity-certification') !== false):
        $rightUrl = '/civicrm/donnees-economique-entreprise-detail-activity-certification?cs=' . $checksum . '&_authx=' . $authx . '&_authxSes=1#?id=' . $cid;;
        break;
    }

    
    return new JsonResponse(['tes' => 'true', 'res' => $authx, 'checksum' => $checksum, 'url' => $rightUrl]);
  }

 /**
  * Permet de créer une activité
  */
 public function createActivity ($cid, $subject, $details, $source_contact_id) {
   $html = '';
   if ($cid) {

     foreach ($details as $keyDetail => $valueDetail) {
       if ($valueDetail) {
         $html .= $keyDetail . '';
         $html .= $valueDetail . '';
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
