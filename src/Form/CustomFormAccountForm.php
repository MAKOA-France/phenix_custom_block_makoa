<?php 

// modules/custom/phenix_custom_block/src/Form/CustomFormAccountForm.php
namespace Drupal\phenix_custom_block\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

class CustomFormAccountForm extends FormBase {
    
    public function getFormId() {
        return 'custom_form_account_form';
    }
    
    public function buildForm(array $form, FormStateInterface $form_state) {
        $config = \Drupal::configFactory()->getEditable('phenix_custom_block.settings');
        // Build your form elements here.
        // Example: $form['your_field'] = ...
        $form['image_field'] = [
            '#type' => 'managed_file',
            '#title' => $this->t('Image Upload'),
            '#upload_location' => 'public://images/',
            '#upload_validators' => [
                'file_validate_extensions' => ['jpg jpeg png gif'],
            ],
        ];
    
 /*        $form['hidden_field'] = [
            '#type' => 'textfield',
            '#title' => 'description',
            '#attributes' => ['class' => ['']]
        ]; */
            
        $form['submit'] = [
            '#type' => 'submit',
            '#value' => $this->t('Enregistrer'),
            '#attributes' => [
                'class' => ['add-new-image']
            ],
        ];  
        

        return $form;
    }

    public function submitForm(array &$form, FormStateInterface $form_state) {

        $config = \Drupal::configFactory()->getEditable('phenix_custom_block.settings');

        // Set the configuration value.
        if ($form_state->getValue('image_field')) {
            // dump($form_state->getValue('image_field')[0]);die;
            $config->set('image_field', $form_state->getValue('image_field')[0])->save();
        }
        $config->set('hidden_field', $form_state->getValue('hidden_field'))->save();
        
    }
}
