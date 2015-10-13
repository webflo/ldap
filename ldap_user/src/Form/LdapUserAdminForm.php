<?php

/**
 * @file
 * Contains \Drupal\ldap_user\Form\LdapUserAdminForm.
 */

namespace Drupal\ldap_user\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;

class LdapUserAdminForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'ldap_user_admin_form';
  }

  public function buildForm(array $form, \Drupal\Core\Form\FormStateInterface $form_state) {
    $ldap_user_conf = ldap_user_conf('admin');
    $form = $ldap_user_conf->drupalForm();
    return $form;
  }

  public function validateForm(array &$form, \Drupal\Core\Form\FormStateInterface $form_state) {

    $ldap_user_conf_admin = ldap_user_conf('admin');
    list($errors, $warnings) = $ldap_user_conf_admin->drupalFormValidate($form_state->getValues(), $form['#storage']);
    foreach ($errors as $error_name => $error_text) {
      $form_state->setErrorByName($error_name, t($error_text));
    }
    foreach ($warnings as $warning_name => $warning_text) {
      drupal_set_message($warning_text, 'warning');
    }
    $form_state->set(['ldap_warnings'], (boolean) (count($warnings) > 0));
  }

  public function submitForm(array &$form, \Drupal\Core\Form\FormStateInterface $form_state) {

    $ldap_user_conf = ldap_user_conf('admin');
    $ldap_user_conf->drupalFormSubmit($form_state->getValues(), $form['#storage']); // add form data to object and save or create

    if ($ldap_user_conf->hasError == FALSE) {
      drupal_set_message(t('LDAP user configuration saved'), 'status');
      drupal_goto(LDAP_SERVERS_MENU_BASE_PATH . '/user');
    }
    else {
      $form_state->setErrorByName($conf->errorName, $conf->errorMsg);
      $ldap_user_conf->clearError();
    }

  }

}
