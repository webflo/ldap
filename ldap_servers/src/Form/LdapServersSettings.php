<?php

/**
 * @file
 * Contains \Drupal\ldap_servers\Form\LdapServersSettings.
 */

namespace Drupal\ldap_servers\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;

class LdapServersSettings extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'ldap_servers_settings';
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('ldap_servers.settings');

    foreach (Element::children($form) as $variable) {
      $config->set($variable, $form_state->getValue($form[$variable]['#parents']));
    }
    $config->save();

    if (method_exists($this, '_submitForm')) {
      $this->_submitForm($form, $form_state);
    }

    parent::submitForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['ldap_servers.settings'];
  }

  public function buildForm(array $form, \Drupal\Core\Form\FormStateInterface $form_state) {
    ldap_servers_module_load_include('inc', 'ldap_servers', 'ldap_servers.functions');

    if (!ldap_servers_ldap_extension_loaded()) {
      drupal_set_message(t('PHP LDAP Extension is not loaded.'), "warning");
    }

    $https_approaches = [];
    $https_approaches[] = t('Use secure pages or secure login module to redirect to SSL (https)');
    $https_approaches[] = t('Run entire site with SSL (https)');
    $https_approaches[] = t('Remove logon block and redirect all /user page to https via webserver redirect');

    $form['#title'] = "Configure LDAP Preferences";
    $form['ssl'] = [
      '#type' => 'fieldset',
      '#title' => t('Require HTTPS on Credential Pages'),
    ];
    // @FIXME
    // theme() has been renamed to _theme() and should NEVER be called directly.
    // Calling _theme() directly can alter the expected output and potentially
    // introduce security issues (see https://www.drupal.org/node/2195739). You
    // should use renderable arrays instead.
    // 
    // 
    // @see https://www.drupal.org/node/2195739
    // $form['ssl']['ldap_servers_require_ssl_for_credentails'] = array(
    //     '#type' => 'checkbox',
    //     '#title' => t('If checked, modules using LDAP will not allow credentials to
    //       be entered on or submitted to HTTP pages, only HTTPS. This option should be used with an
    //       approach to get all logon forms to be https, such as:') .
    //       theme('item_list', array('items' => $https_approaches)),
    //     '#default_value' => variable_get('ldap_servers_require_ssl_for_credentails', 0),
    //   );


    $options = ldap_servers_encrypt_types('encrypt');

    // @FIXME
    // Could not extract the default value because it is either indeterminate, or
    // not scalar. You'll need to provide a default value in
    // config/install/ldap_servers.settings.yml and config/schema/ldap_servers.schema.yml.
    /**  when this is changed, need to decrypt and possibly encrypt pwd in newly selected format
     *   ... thus default needs to be "No Encryption" to avoid confusion.
     */
    $form['previous_encryption'] = [
      '#type' => 'hidden',
      '#default_value' => \Drupal::config('ldap_servers.settings')->get('ldap_servers_encryption'),
    ];
    $form['encryption'] = ['#type' => 'fieldset', '#title' => t('Encryption')];
    // @FIXME
    // Could not extract the default value because it is either indeterminate, or
    // not scalar. You'll need to provide a default value in
    // config/install/ldap_servers.settings.yml and config/schema/ldap_servers.schema.yml.
    $form['encryption']['ldap_servers_encryption'] = [
      '#type' => 'select',
      '#options' => $options,
      '#title' => t('Encrypt Stored LDAP Passwords?'),
      '#default_value' => \Drupal::config('ldap_servers.settings')->get('ldap_servers_encryption'),
      '#description' => t('With encryption, passwords will be stored in encrypted form.
    This is two way encryption because the actual password needs to used to bind to LDAP.
    So it offers minimal defense if someone gets in the filespace.  It mainly helps avoid the accidental
    discovery of a clear text password.'),
    ];


    // $options will be empty if server does not support mcrypt.
    // Disable the form field and explain this to the user.
    if (empty($options)) {
      $form['encryption']['ldap_servers_encryption']['#options'] = [
        LDAP_SERVERS_ENC_TYPE_CLEARTEXT => t('Not available.')
        ];
      $form['encryption']['ldap_servers_encryption']['#disabled'] = TRUE;
      $form['encryption']['ldap_servers_encryption']['#description'] .= ' <strong>' . t('Encryption is not supported on this web server.') . '</strong>';
    }

    $form = parent::buildForm($form, $form_state);
    array_unshift($form['#submit'], 'ldap_servers_settings_submit'); // needs to be first
    return $form;
  }

  public function _submitForm(array &$form, \Drupal\Core\Form\FormStateInterface $form_state) {
    if ($form_state->isSubmitted()) {
      $new_encyption = $form_state->getValue(['ldap_servers_encryption']);
      $old_encyption = $form_state->getValue(['previous_encryption']);

      // use db instead of functions to avoid classes encryption and decryption
      if ($new_encyption != $old_encyption) {
        $servers = db_query("SELECT sid, bindpw FROM {ldap_servers} WHERE bindpw is not NULL AND bindpw <> ''")->fetchAllAssoc('sid');
        foreach ($servers as $sid => $server) {
          if ($server->bindpw != '') {
            $decrypted_bind_pwd = ldap_servers_decrypt($server->bindpw, $old_encyption);
            $rencrypted = ldap_servers_encrypt($decrypted_bind_pwd, $new_encyption);
          }
          else {
            $rencrypted = '';
          }
          db_query("UPDATE {ldap_servers} SET bindpw = :bindpw WHERE sid = :sid", [
            ':bindpw' => $rencrypted,
            ':sid' => $sid,
          ]);
        }
      }
    }
  }

}
