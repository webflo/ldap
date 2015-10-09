<?php /**
 * @file
 * Contains \Drupal\ldap_servers\Controller\DefaultController.
 */

namespace Drupal\ldap_servers\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 * Default controller for the ldap_servers module.
 */
class DefaultController extends ControllerBase {

  public function ldap_servers_edit_index() {

    return ldap_servers_servers_list(NULL, TRUE);

  }

}
