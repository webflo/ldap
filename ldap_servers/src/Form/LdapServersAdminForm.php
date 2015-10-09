<?php

/**
 * @file
 * Contains \Drupal\ldap_servers\Form\LdapServersAdminForm.
 */

namespace Drupal\ldap_servers\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;

class LdapServersAdminForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'ldap_servers_admin_form';
  }

  public function buildForm(array $form, \Drupal\Core\Form\FormStateInterface $form_state, $op = NULL, $sid = NULL) {
    ldap_servers_module_load_include('php', 'ldap_servers', 'LdapServerAdmin.class');
    $server = new LdapServerAdmin($sid);
    $form = $server->drupalForm($op);
    return $form;
  }

  public function validateForm(array &$form, \Drupal\Core\Form\FormStateInterface $form_state) {

    $op = \Drupal\Component\Utility\Unicode::strtolower($form_state->get([
      'clicked_button',
      '#value',
    ]));
    ldap_servers_module_load_include('php', 'ldap_servers', 'LdapServerAdmin.class');
    $server = new LdapServerAdmin($form_state->getValue(['sid']));

    $errors = $server->drupalFormValidate($op, $form_state->getValues());
    foreach ($errors as $error_name => $error_text) {
      $form_state->setErrorByName($error_name, t($error_text));
    }
    $warnings = $server->drupalFormWarnings($op, $form_state->getValues(), (boolean) (count($errors) > 0));
    foreach ($warnings as $warning_name => $warning_text) {
      drupal_set_message($warning_text, 'warning');
    }

    $form_state->set(['ldap_warnings'], (boolean) (count($warnings) > 0));

  }

  public function submitForm(array &$form, \Drupal\Core\Form\FormStateInterface $form_state) {
    $button = \Drupal\Component\Utility\Unicode::strtolower($form_state->get(['clicked_button', '#value']));
    $op = ($button == 'add') ? 'add' : 'edit';
    $verb = ($op == 'edit') ? 'edited' : $op . 'ed';
    ldap_servers_module_load_include('php', 'ldap_servers', 'LdapServerAdmin.class');
    $server = new LdapServerAdmin($form_state->getValue(['sid']));
    $server->drupalFormSubmit($op, $form_state->getValues()); // add form data to object and save or create

    if ($server->hasError() == FALSE) {
      drupal_set_message(t('LDAP Server %name !verb.', [
        '!verb' => $verb,
        '%name' => $server->name,
      ]), 'status');
      ldap_servers_cache_clear();
      if ($form_state->get(['ldap_warnings']) && $op != 'add') {
        // do nothing, but don't redirect away from form.
      // if there are warnings, want them to see form even if its been saved
      }
      else {
        drupal_goto(LDAP_SERVERS_MENU_BASE_PATH . '/servers');
      }
    }
    else {
      $form_state->setErrorByName($server->errorName(), $server->errorMsg());
      $server->clearError();
    }
    ldap_servers_cache_clear();
  }

}
