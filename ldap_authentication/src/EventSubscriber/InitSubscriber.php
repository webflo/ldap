<?php /**
 * @file
 * Contains \Drupal\ldap_authentication\EventSubscriber\InitSubscriber.
 */

namespace Drupal\ldap_authentication\EventSubscriber;

use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class InitSubscriber implements EventSubscriberInterface {

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    return [KernelEvents::REQUEST => ['onEvent', 0]];
  }

  public function onEvent() {
    $auth_conf = ldap_authentication_get_valid_conf();
    if ($auth_conf && $auth_conf->templateUsagePromptUser) {
      ldap_authentication_check_for_email_template();
    }
  }

}
