<?php /**
 * @file
 * Contains \Drupal\ldap_authentication\EventSubscriber\LDAPAuthenticationSubscriber.
 */

namespace Drupal\ldap_authentication\EventSubscriber;

use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class LDAPAuthenticationSubscriber implements EventSubscriberInterface {

  public function onEvent() {
    drupal_set_message("Replacement for ldap_authentication_init");
    $auth_conf = ldap_authentication_get_valid_conf();
    if ($auth_conf && $auth_conf->templateUsagePromptUser) {
      ldap_authentication_check_for_email_template();
    }
  }

  /**
   * {@inheritdoc}
   */
  static function getSubscribedEvents() {
    $events[KernelEvents::REQUEST][] = array('onEvent');
    return $events;
  }

}
