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
 * Defines WebformController class.
 */
class WebformController extends ControllerBase
{
  
  public function backToForm() {
    $req = \Drupal::request();
   
    $getId = $req->query->get('cid');
    $custom_service = \Drupal::service('phenix_custom_block.view_services');

    \Drupal::service('civicrm')->initialize();
    $idHash = \Civi\Api4\Contact::get(FALSE)
    ->selectRowCount()
    ->addSelect('hash')
    ->addWhere('id', '=', $getId)
    ->execute()->first()['hash'];

    
    $urlBackLink = '/form/poser-une-question?cid2=' . $getId . '&token=' . $idHash;

    return new JsonResponse(['back_link' => $urlBackLink]);
  }


}
