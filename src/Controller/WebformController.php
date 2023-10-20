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
 /*    die('qmldkf');
    $req = \Drupal::request();
    $getId = $req->query->get('id');
    $custom_service = \Drupal::service('phenix_custom_block.view_services');

    $hashContactViaDatabase = $custom_service->checkIfHashContactIsGood($getId);
    
    $urlBackLink = '/form/poser-une-question?cid2=' . $getId . '?&token=' . $hashContactViaDatabase;
dump('here'); */

    return new JsonResponse(['back_link' => 'noooooo']);
  }


}
