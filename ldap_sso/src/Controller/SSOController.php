<?php

/**
 * @file
 * Contains \Drupal\ldap_sso\Controller\SSOController.
 */

namespace Drupal\ldap_sso\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Form\FormState;

class SSOController extends ControllerBase {

  public function login() {
    if ($this->currentUser()->isAuthenticated()) {
      return $this->redirect('<front>');
    }

    $remote_user = NULL;
    if (isset($_SERVER['REMOTE_USER'])) {
      $remote_user = $_SERVER['REMOTE_USER'];
    }
    elseif (!isset($remote_user) && isset($_SERVER['REDIRECT_REMOTE_USER'])) {
      $remote_user = $_SERVER['REDIRECT_REMOTE_USER'];
    }

    if ($remote_user) {
      $fake_form_state = new FormState();
      $fake_form_state->setValues([
        'name' => $remote_user,
        'pass' => user_password(20),
        'sso_login' => TRUE,
      ]);

      $account = ldap_authentication_user_login_authenticate_validate(array(), $fake_form_state, TRUE);
      if ($account) {
        user_login_finalize($account);
      }
      return $this->redirect('<front>');
    }

    return $this->redirect('user.login');
  }

}
