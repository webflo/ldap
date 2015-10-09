<?php

/**
 * @file
 * Contains \Drupal\ldap_servers\Form\LdapServersAdminDelete.
 */

namespace Drupal\ldap_servers\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;

class LdapServersAdminDelete extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'ldap_servers_admin_delete';
  }

  public function buildForm(array $form, \Drupal\Core\Form\FormStateInterface $form_state, $op = NULL, $sid = NULL) {

    if ($sid && ($ldap_server = ldap_servers_get_servers($sid, 'all', TRUE))) {
      // array()

      $variables = [
        'ldap_server' => $ldap_server,
        'actions' => FALSE,
        'type' => 'detail',
      ];
      // @FIXME
      // theme() has been renamed to _theme() and should NEVER be called directly.
      // Calling _theme() directly can alter the expected output and potentially
      // introduce security issues (see https://www.drupal.org/node/2195739). You
      // should use renderable arrays instead.
      // 
      // 
      // @see https://www.drupal.org/node/2195739
      // $form['#prefix'] = '<div>' . theme('ldap_servers_server', $variables) . '</div>';

      $form['sid'] = [
        '#type' => 'hidden',
        '#value' => $sid,
      ];
      $form['name'] = [
        '#type' => 'hidden',
        '#value' => $ldap_server->name,
      ];

      $warnings = \Drupal::moduleHandler()->invokeAll('ldap_server_in_use', [
        $sid,
        $ldap_server->name,
      ]);
      if (count($warnings)) {
        drupal_set_message(join("<br/>", array_values($warnings)), 'error');
        drupal_goto(LDAP_SERVERS_MENU_BASE_PATH . '/servers/list');
      }
      else {
        return confirm_form($form, 'Delete Confirmation Form', LDAP_SERVERS_MENU_BASE_PATH . '/servers/list', '<p>' . t('Are you sure you want to delete the LDAP server named <em><strong>%name</strong></em> ?', [
          '%name' => $ldap_server->name
          ]) . '</p><p>' . t('This action cannot be undone.') . '</p>', t('Delete'), t('Cancel'));
      }
    }
    drupal_goto(LDAP_SERVERS_MENU_BASE_PATH . '/servers/list');
  }

  public function submitForm(array &$form, \Drupal\Core\Form\FormStateInterface $form_state) {
    $values = $form_state->getValues();
    $sid = $values['sid'];
    ldap_servers_module_load_include('php', 'ldap_servers', 'LdapServerAdmin.class');
    $ldap_server = new LdapServerAdmin($sid);
    if ($values['confirm'] && $sid) {
      if ($result = $ldap_server->delete($sid)) {
        $tokens = ['%name' => $ldap_server->name, '!sid' => $sid];
        drupal_set_message(t('LDAP Server %name (server id = !sid) has been deleted.', $tokens), 'status');
        \Drupal::logger('ldap')->notice('LDAP Server deleted: %name (sid = !sid) ', []);
      }
      else {
        drupal_set_message(t('LDAP Server delete failed.'), 'warning');
      }
    }
    else {
      drupal_set_message(t('LDAP Server delete cancelled.'), 'status');
    }
    ldap_servers_cache_clear();
    drupal_goto(LDAP_SERVERS_MENU_BASE_PATH . '/servers/list');
  }

}
