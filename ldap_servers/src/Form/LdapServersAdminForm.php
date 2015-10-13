<?php

/**
 * @file
 * Contains \Drupal\ldap_servers\Form\LdapServersAdminForm.
 */

namespace Drupal\ldap_servers\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;
// use Drupal\LdapServerAdmin;

/**
 * Form controller for the ldap_server entity edit forms.
 *
 * @ingroup ldap_server
 */
class LdapServersAdminForm extends ContentEntityForm {

  /**
   * {@inheritdoc}
   */
  // public function getFormId() {
  //   return 'ldap_servers_admin_form';
  // }

  public function buildForm(array $form, FormStateInterface $form_state) {
    // ldap_servers_module_load_include('php', 'ldap_servers', 'LdapServerAdmin.class');
    // $server = new LdapServerAdmin($sid);
    $form = parent::buildForm($form, $form_state);
    $entity = $this->entity;
    return $form;
  }

  function save(array $form, FormStateInterface $form_state) {
    $form_state->setRedirect('entity.ldap_servers.edit_index');
    $entity = $this->getEntity();
    $entity->save();
  }

}
