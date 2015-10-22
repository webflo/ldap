<?php

/**
 * @file
 * Contains \Drupal\ldap_user\Form\LdapUserAdminForm.
 */

namespace Drupal\ldap_user\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Render\Element;
use Symfony\Component\HttpFoundation\RedirectResponse;

class LdapUserAdminForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'ldap_user_admin_form';
  }

  /**
   * {@inheritdoc}
   */
  public function getEditableConfigNames() {
    return ['ldap_user_admin.settings'];
  }

  public function buildForm(array $form, \Drupal\Core\Form\FormStateInterface $form_state) {
    $ldap_user_conf_admin = ldap_user_conf('admin');
    $form = $ldap_user_conf_admin->drupalForm();
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

    $ldap_user_conf_admin = ldap_user_conf('admin');
    $ldap_user_conf_admin->drupalFormSubmit($form_state->getValues(), $form['#storage']); // add form data to object and save or create

    if ($ldap_user_conf_admin->hasError == FALSE) {
      drupal_set_message(t('LDAP user configuration saved'), 'status');
      return new RedirectResponse(\Drupal::url('ldap_user.admin_form'));
    }
    else {
      $form_state->setErrorByName($conf->errorName, $conf->errorMsg);
      $ldap_user_conf_admin->clearError();
    }

  }

}
