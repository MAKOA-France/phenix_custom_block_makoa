<?php 

namespace Drupal\phenix_custom_block\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

class MetierNewlsetterForm extends FormBase {
    
    public function getFormId() {
        return 'metier_newsletter_form';
    }
    
    public function buildForm(array $form, FormStateInterface $form_state) {
        $config = \Drupal::configFactory()->getEditable('phenix_custom_block.settings');
        // Build your form elements here.
        // Example: $form['your_field'] = ...
        $form['mail_newsletter'] = [
            '#type' => 'email',
            '#title' => $this->t('Nom de la personne ayant renseigné le formulaire *'),
            '#title_display' => 'invisible',
            '#attributes' => [
              'class' => ['metier-newsletter-input'],
              'placeholder' => $this->t('Saisissez votre adresse email'), // Placeholder text

            ],
            // '#default_value' => $config->get('personne_who_filled')
          ];
    
 /*        $form['hidden_field'] = [
            '#type' => 'textfield',
            '#title' => 'description',
            '#attributes' => ['class' => ['']]
        ]; */
            
        $form['submit'] = [
            '#type' => 'submit',
            '#value' => $this->t('Envoyer'),
            '#attributes' => [
                'class' => ['metier-submit-newsletter']
            ],
        ];  
        

        return $form;
    }

    public function submitForm(array &$form, FormStateInterface $form_state) {

        $config = \Drupal::configFactory()->getEditable('phenix_custom_block.settings');

        // Set the configuration value.
        // if ($form_state->getValue('image_field')) {
        //     $config->set('image_field', $form_state->getValue('image_field')[0])->save();
        // }
        // $config->set('hidden_field', $form_state->getValue('hidden_field'))->save();
        
    }
}
