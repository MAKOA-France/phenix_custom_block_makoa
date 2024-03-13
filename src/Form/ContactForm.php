<?php

namespace Drupal\phenix_custom_block\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

class ContactForm extends FormBase {
  public function getFormId() {
    return 'my_contact_form';
  }

  public function buildForm(array $form, FormStateInterface $form_state) {
    $custom_service = \Drupal::service('phenix_custom_block.view_services');
     // Handle form submission.
     $config = \Drupal::configFactory()->getEditable('phenix_custom_block.settings.contact');

    $form['personne_who_filled'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Nom de la personne ayant renseigné le formulaire *'),
      '#attributes' => [
        'class' => ['cust-form-person-who-filled']
      ],
      // '#default_value' => $config->get('personne_who_filled')
    ];
    $form['contact_email'] = [
      '#type' => 'email',
      '#title' => $this->t('Mail de la personne ayant renseigné le formulaire'),
      // '#default_value' => $config->get('contact_email')
    ];

    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Submit'),
      '#attributes' => [
        'class' => ['cust-form-submit hide']
      ]
    ];

    return $form;
  }

  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Handle form submission.
    $config = \Drupal::configFactory()->getEditable('phenix_custom_block.settings.contact');
    $custom_service = \Drupal::service('phenix_custom_block.view_services');
    // Set the configuration value.
    if ($form_state->getValue('personne_who_filled')) {
        $config->set('personne_who_filled', $form_state->getValue('personne_who_filled'))->save();
    }
    if ($form_state->getValue('contact_email')) {
        $config->set('contact_email', \Drupal::service('session')->get('data-info-entreprise'))->save();
        // $config->set('contact_email', $form_state->getValue('contact_email'))->save();
    }

 
    $personWhoFilled = $form_state->getValue('personne_who_filled');
    $personWhoFilledMail = $form_state->getValue('contact_email');

    $dataInSession = \Drupal::service('session')->get('data-info-entreprise');
    $dataInSession = json_decode($dataInSession);
     $newData = [];
    if ($dataInSession) {
      foreach ($dataInSession as $key => $data) {
        if ($key == 'detail') {
          if ($form_state->getValue('personne_who_filled')) {
            $data .= '<p>' . $this->t("Nom de la personne ayant renseigné le formulaire") . ' : ' . $personWhoFilled . '</p>';
           }
           if ($form_state->getValue('contact_email')) {
             $data .= '<p>' . $this->t("Email de la personne ayant renseigné le formulaire ") . ' : ' . $personWhoFilledMail . '</p>';
           }
         }
         $dataInSession->$key = $data;
      }

    //Stocker dans la session la personne (nom + email) qui a rempli le formulaire
    \Drupal::service('session')->set('contact_who_filled_the_form', $personWhoFilled);
    \Drupal::service('session')->set('mail_of_user_who_filled_the_form', $personWhoFilledMail);

      $this->createActivityForInfoEntreprise($dataInSession->cid, $dataInSession->subject, $dataInSession->detail, $dataInSession->source_contact_id);
    }

    // Rediriger vers la page d'effectif 
    $checksum = $custom_service->getChecksum($dataInSession->cid);
    $authx  = $custom_service->getCredentialAuthx($dataInSession->cid);
    // $url_tab = '<a href="/civicrm/bulletin-de-cotisation-infomration-contact?cs' . $checksum . '&_authx=' . $authx . '&_authxSes=1#?Organization1=' . $dataInSession->cid . '">url</a>';
    $url_tab = '<a href="/civicrm/bulletin-de-cotisation-infomration-contact?cs' . $checksum . '#?Organization1=' . $dataInSession->cid . '">url</a>';




    $response = new \Symfony\Component\HttpFoundation\RedirectResponse('/civicrm/donnees-economique-entreprise-effectif-annuel?cs=' . $checksum . '#?id=' . $dataInSession->cid . '');
    $response->send();
    
    // $config->set('hidden_field', $form_state->getValue('hidden_field'))->save();
    
  }

  /**
   * 
   */
  public function createActivityForInfoEntreprise ($cid, $subject, $details, $source_contact_id) {
    $html = '';
    if ($cid) {
     
      // foreach ($details as $keyDetail => $valueDetail) {
      //   if ($valueDetail) {
      //     $html .= $keyDetail . '<br>';
      //     $html .= $valueDetail . '<br>';
      //   }
      // }
      \Drupal::service('civicrm')->initialize();
      
      if ($source_contact_id) {
  
        return \Civi\Api4\Activity::create(FALSE)
        ->addValue('activity_type_id', 184)
        ->addValue('subject', $subject)
        ->addValue('assignee_contact_id', [2696])//Myriam
        ->addValue('target_contact_id', [
          $cid, $source_contact_id
          ])
          ->addValue('status_id', 2)//status = fait ; 1 à faire
          ->addValue('details',  $details)
          ->addValue('source_contact_id', $cid)
          ->execute();
        }
      return \Civi\Api4\Activity::create(FALSE)
        ->addValue('activity_type_id', 184)
        ->addValue('subject', $subject)
        ->addValue('assignee_contact_id', [2696])//Myriam
          ->addValue('status_id', 2)//status = fait ; 1 à faire
          ->addValue('details',  $details)
          ->addValue('source_contact_id', $cid)
          ->execute();
    }
  
  }
}
