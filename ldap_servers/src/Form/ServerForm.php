<?php

/**
 * @file
 * Contains \Drupal\ldap_servers\Form\ServerForm.
 */

namespace Drupal\ldap_servers\Form;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class ServerForm.
 *
 * @package Drupal\ldap_servers\Form
 */
class ServerForm extends EntityForm {
  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    $server = $this->entity;

    $form['server'] = array(
      '#type' => 'fieldset',
      '#title' => t('Server'),
    );

    $form['server']['label'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Name'),
      '#maxlength' => 255,
      '#default_value' => $server->label(),
      '#description' => $this->t("Choose a unique <strong><em>name</em></strong> for this server configuration."),
      '#required' => TRUE,
    );

    $form['server']['id'] = array(
      '#type' => 'machine_name',
      '#default_value' => $server->id(),
      '#machine_name' => array(
        'exists' => '\Drupal\ldap_servers\Entity\Server::load',
      ),
      '#disabled' => !$server->isNew(),
    );

    /* You will need additional form elements for your custom properties. */
    $form['server']['status'] = array(
      '#title' => $this->t('Enabled'),
      '#type' => 'checkbox',
      '#default_value' => $server->get('status'),
      '#description' => $this->t('Disable in order to keep configuration without having it active.'),
    );

    $form['server']['type'] = array(
      '#title' => $this->t('LDAP Server type'),
      '#type' => 'select',
      '#options' => array(
        'default' => 'Default LDAP',
        'ad' => 'Active Directory',
        'novell_edir' => 'Novell',
        'openldap' => 'Open LDAP',
        'opendir' => 'Apple Open Directory',
      ),
      '#default_value' => $server->get('type'),
      '#description' => $this->t('This field is informative. It\'s purpose is to assist with default values and give validation warnings.'),
    );

    $form['server']['address'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Server address'),
      '#maxlength' => 255,
      '#default_value' => $server->get('address'),
      '#description' => $this->t("The domain name or IP address of your LDAP Server such as \"ad.unm.edu\". For SSL use the form ldaps://DOMAIN such as \"ldaps://ad.unm.edu\""),
      '#required' => TRUE,
    );

    $form['server']['port'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Server port'),
      '#maxlength' => 11,
      '#default_value' => $server->get('port') ? $server->get('port') : 389,
      '#description' => $this->t("The TCP/IP port on the above server which accepts LDAP connections. Must be an integer."),
      '#required' => TRUE,
    );

    $form['server']['tls'] = array(
      '#title' => $this->t('Use Start-TLS'),
      '#type' => 'checkbox',
      '#default_value' => $server->get('tls'),
      '#description' => $this->t("Secure the connection between the Drupal and the LDAP servers using TLS.<br><em>Note: To use START-TLS, you must set the LDAP Port to 389.</em>"),
    );

    $form['bind'] = array(
      '#type' => 'fieldset',
      '#title' => t('Binding'),
    );

    $form['bind']['bind_method'] = array(
      '#default_value' => $server->get('bind_method') ? $server->get('bind_method') : LDAP_SERVERS_BIND_METHOD_SERVICE_ACCT,
      '#type' => 'radios',
      '#title' => t('Binding Method for Searches (such as finding user object or their group memberships)'),
      '#options' => array(
        LDAP_SERVERS_BIND_METHOD_SERVICE_ACCT => t('Service Account Bind: Use credentials in the
        <strong>Service Account</strong> field to bind to LDAP.  <em>This option is usually a best practice.</em>'),

        LDAP_SERVERS_BIND_METHOD_USER => t('Bind with Users Credentials: Use user\'s entered credentials
        to bind to LDAP.<br/> This is only useful for modules that execute during user logon such
        as LDAP Authentication and LDAP Authorization.  <em>This option is not a best practice in most cases.</em>
        The user\'s dn must be of the form "cn=[username],[base dn]" for this option to work.'),

        LDAP_SERVERS_BIND_METHOD_ANON_USER => t('Anonymous Bind for search, then Bind with Users Credentials:
        Searches for user dn then uses user\'s entered credentials to bind to LDAP.<br/> This is only useful for
        modules that work during user logon such as LDAP Authentication and LDAP Authorization.
        The user\'s dn must be discovered by an anonymous search for this option to work.'),

        LDAP_SERVERS_BIND_METHOD_ANON => t('Anonymous Bind: Use no credentials to bind to LDAP server.<br/>
        <em>This option will not work on most LDAPS connections.</em>'),
      ),
    );

    $form['bind']['binding_service_acct'] = array(
      '#default_value' => $server->get('binding_service_acct'),
      '#type' => 'markup',
      '#markup' => t('<label>Service Account</label> Some LDAP configurations
        prohibit or restrict the results of anonymous searches. These LDAPs require a DN//password pair
        for binding. For security reasons, this pair should belong to an
        LDAP account with stripped down permissions.
        This is also required for provisioning LDAP accounts and groups!'),
    );

    $form['bind']['binddn'] = array(
      '#default_value' => $server->get('binddn'),
      '#type' => 'textfield',
      '#title' => t('DN for non-anonymous search'),
      '#size' => 80,
      '#states' => array(
        'enabled' => array(   // action to take.
          ':input[name=bind_method]' => array('value' => LDAP_SERVERS_BIND_METHOD_SERVICE_ACCT),
        ),
      ),
    );

    $form['bind']['bindpw'] = array(
      '#type' => 'password',
      '#title' => t('Password for non-anonymous search'),
      '#size' => 20,
      '#states' => array(
        'enabled' => array(   // action to take.
          ':input[name=bind_method]' => array('value' => LDAP_SERVERS_BIND_METHOD_SERVICE_ACCT),
        ),
      ),
    );

    $form['bind']['clear_bindpw'] = array(
      '#default_value' => $server->get('clear_bindpw'),
      '#type' => 'checkbox',
      '#title' => t('Clear existing password from database.  Check this when switching away from Service Account Binding.'),
      '#default_value' => 0,
    );

    $form['users'] = array(
      '#type' => 'fieldset',
      '#title' => t('Users'),
    );

    $form['users']['basedn'] = array(
      '#default_value' => $server->get('basedn'),
      '#type' => 'textarea',
      '#cols' => 50,
      '#rows' => 6,
      '#title' => t('Base DNs for LDAP users, groups, and other entries.'),
      '#description' => '<div>' . t('What DNs have entries relavant to this configuration?
        e.g. <code>ou=campus accounts,dc=ad,dc=uiuc,dc=edu</code>
        Keep in mind that every additional basedn likely doubles the number of queries.  Place the
        more heavily used one first and consider using one higher base DN rather than 2 or more lower base DNs.
        Enter one per line in case if you need more than one.') . '</div>',
    );

    $form['users']['user_attr'] = array(
      '#default_value' => $server->get('user_attr'),
      '#type' => 'textfield',
      '#size' => 30,
      '#title' => t('AuthName attribute'),
      '#description' => t('The attribute that holds the users\' login name. (eg. <code>cn</code> for eDir or <code>sAMAccountName</code> for Active Directory).'),
    );

    $form['users']['account_name_attr'] = array(
      '#default_value' => $server->get('account_name_attr'),
      '#type' => 'textfield',
      '#size' => 30,
      '#title' => t('AccountName attribute'),
      '#description' => t('The attribute that holds the unique account name. Defaults to the same as the AuthName attribute.'),
    );

    $form['users']['mail_attr'] = array(
      '#default_value' => $server->get('mail_attr'),
      '#type' => 'textfield',
      '#size' => 30,
      '#title' => t('Email attribute'),
      '#description' => t('The attribute that holds the users\' email address. (eg. <code>mail</code>). Leave empty if no such attribute exists'),
    );

    $form['users']['mail_template'] = array(
      '#default_value' => $server->get('mail_template'),
      '#type' => 'textfield',
      '#size' => 30,
      '#title' => t('Email template'),
      '#description' => t('If no attribute contains the user\'s email address, but it can be derived from other attributes,
        enter an email "template" here.
        Templates should have the user\'s attribute name in form such as [cn], [uin], etc.
        such as <code>[cn]@mycompany.com</code>.
        See http://drupal.org/node/997082 for additional documentation on ldap tokens.
        '),
    );

    $form['users']['picture_attr'] = array(
      '#default_value' => $server->get('picture_attr'),
      '#type' => 'textfield',
      '#size' => 30,
      '#title' => t('Thumbnail attribute'),
      '#description' => t('The attribute that holds the users\' thumnail image. (eg. <code>thumbnailPhoto</code>). Leave empty if no such attribute exists'),
    );

    $form['users']['unique_persistent_attr'] = array(
      '#default_value' => $server->get('unique_persistent_attr'),
      '#type' => 'textfield',
      '#size' => 30,
      '#title' => t('Persistent and Unique User ID Attribute'),
      '#description' => t('In some LDAPs, a user\'s DN, CN, or mail value may
        change when a user\'s name changes or for other reasons.
        In order to avoid creation of multiple accounts for that user or other ambiguities,
        enter a unique and persistent ldap attribute for users.  In cases
        where DN does not change, enter "dn" here.
        If no such attribute exists, leave this blank.'
        ),
    );

    $form['users']['unique_persistent_attr_binary'] = array(
      '#default_value' => $server->get('unique_persistent_attr_binary'),
      '#type' => 'checkbox',
      '#title' => t('Does PUID hold a binary value?'),
      '#description' => t(''),
    );

    $form['users']['user_dn_expression'] = array(
      '#default_value' => $server->get('user_dn_expression'),
      '#type' => 'textfield',
      '#size' => 80,
      '#title' => t('Expression for user DN. Required when "Bind with Users Credentials" method selected.'),
      '#description' => t('%username and %basedn are valid tokens in the expression.
        Typically it will be:<br/> <code>cn=%username,%basedn</code>
        which might evaluate to <code>cn=jdoe,ou=campus accounts,dc=ad,dc=mycampus,dc=edu</code>
        Base DNs are entered above.'),
    );

    // $form['users']['ldap_to_drupal_user'] = array(
    //   '#default_value' => $server->get('ldap_to_drupal_user'),
    //   'fieldset' => 'users',
    //   '#disabled' => (!module_exists('php')),
    //   '#type' => 'textarea',
    //   '#cols' => 25,
    //   '#rows' => 5,
    //   '#title' => t('PHP to transform Drupal login username to LDAP UserName attribute.'),
    //   '#description' => t('This will appear as disabled unless the "PHP filter" core module is enabled. Enter PHP to transform Drupal username to the value of the UserName attribute.
    //     The code should print the UserName attribute.
    //     PHP filter module must be enabled for this to work.
    //     The variable $name is available and is the user\'s login username.
    //     Careful, bad PHP code here will break your site. If left empty, no name transformation will be done.
    //     <br/>Example:<br/>Given the user will logon with jdoe@xyz.com and you want the ldap UserName attribute to be
    //     jdoe.<br/><code>$parts = explode(\'@\', $name); if (count($parts) == 2) {print $parts[0]};</code>'),
    // );

   $form['users']['testing_drupal_username'] = array(
      '#default_value' => $server->get('testing_drupal_username'),
      '#type' => 'textfield',
      '#size' => 30,
      '#title' => t('Testing Drupal Username'),
      '#description' => t('This is optional and used for testing this server\'s configuration against an actual username.  The user need not exist in Drupal and testing will not affect the user\'s LDAP or Drupal Account.'),
    );

   $form['users']['testing_drupal_user_dn'] = array(
      '#default_value' => $server->get('testing_drupal_user_dn'),
      '#type' => 'textfield',
      '#size' => 120,
      '#title' => t('DN of testing username, e.g. cn=hpotter,ou=people,dc=hogwarts,dc=edu'),
      '#description' => t('This is optional and used for testing this server\'s configuration against an actual username.  The user need not exist in Drupal and testing will not affect the user\'s LDAP or Drupal Account.'),
    );

    $form['groups'] = array(
      '#type' => 'fieldset',
      '#title' => t('Groups'),
    );

    $form['groups']['grp_unused'] = array(
      '#default_value' => $server->get('grp_unused'),
      '#type' => 'checkbox',
      '#title' => t('Groups are not relevant to this Drupal site.  This is generally true if LDAP Groups, LDAP Authorization, etc are not it use.'),
      '#disabled' => FALSE,
    );

    $form['groups']['grp_object_cat'] = array(
      '#default_value' => $server->get('grp_object_cat'),
      '#type' => 'textfield',
      '#size' => 30,
      '#title' => t('Name of Group Object Class'),
      '#description' => t('e.g. groupOfNames, groupOfUniqueNames, group.'),
      '#states' => array(
        'visible' => array(   // action to take.
          ':input[name=grp_unused]' => array('checked' => FALSE),
        ),
      ),
    );

    $form['groups']['grp_nested'] = array(
      '#default_value' => $server->get('grp_nested'),
      '#type' => 'checkbox',
      '#title' => t('Nested groups are used in my LDAP'),
      '#disabled' => FALSE,
      '#description' => t('If a user is a member of group A and group A is a member of group B,
         user should be considered to be in group A and B.  If your LDAP has nested groups, but you
         want to ignore nesting, leave this unchecked.'),
      '#states' => array(
        'visible' => array(   // action to take.
          ':input[name=grp_unused]' => array('checked' => FALSE),
        ),
      ),
    );

    $form['groups']['grp_user_memb_attr_exists'] = array(
      '#default_value' => $server->get('grp_user_memb_attr_exists'),
      '#type' => 'checkbox',
      '#title' => t('A user LDAP attribute such as <code>memberOf</code> exists that contains a list of their groups.
        Active Directory and openLdap with memberOf overlay fit this model.'),
      '#disabled' => FALSE,
      '#states' => array(
        'visible' => array(   // action to take.
          ':input[name=grp_unused]' => array('checked' => FALSE),
        ),
      ),
    );

    $form['groups']['grp_user_memb_attr'] = array(
        '#default_value' => $server->get('grp_user_memb_attr'),
          '#type' => 'textfield',
        '#size' => 30,
        '#title' => t('Attribute in User Entry Containing Groups'),
        '#description' => t('e.g. memberOf'),
        '#states' => array(
          'enabled' => array(   // action to take.
            ':input[name=grp_user_memb_attr_exists]' => array('checked' => TRUE),
          ),
            'visible' => array(   // action to take.
            ':input[name=grp_unused]' => array('checked' => FALSE),
          ),
        ),
    );

    $form['groups']['grp_memb_attr'] = array(
      '#default_value' => $server->get('grp_memb_attr'),
      '#type' => 'textfield',
      '#size' => 30,
      '#title' => t('LDAP Group Entry Attribute Holding User\'s DN, CN, etc.'),
      '#description' => t('e.g uniquemember, memberUid'),
      '#states' => array(
        'visible' => array(   // action to take.
          ':input[name=grp_unused]' => array('checked' => FALSE),
        ),
      ),
    );

    $form['groups']['grp_memb_attr_match_user_attr'] = array(
      '#default_value' => $server->get('grp_memb_attr_match_user_attr'),
      '#type' => 'textfield',
      '#size' => 30,
      '#title' => t('User attribute held in "LDAP Group Entry Attribute Holding..."'),
      '#description' => t('This is almost always "dn" (which technically isn\'t an attribute).  Sometimes its "cn".'),
      '#states' => array(
        'visible' => array(   // action to take.
          ':input[name=grp_unused]' => array('checked' => FALSE),
        ),
      ),
    );

    $form['groups']['grp_derive_from_dn'] = array(
      '#default_value' => $server->get('grp_derive_from_dn'),
      '#type' => 'checkbox',
      '#title' => t('Groups are derived from user\'s LDAP entry DN.') . '<em>' .
        t('This
        group definition has very limited functionality and most modules will
        not take this into account.  LDAP Authorization will.') . '</em>',
      '#disabled' => FALSE,
      '#states' => array(
        'visible' => array(   // action to take.
          ':input[name=grp_unused]' => array('checked' => FALSE),
        ),
      ),
    );

    $form['groups']['grp_derive_from_dn_attr'] = array(
      '#default_value' => $server->get('grp_derive_from_dn_attr'),
      '#type' => 'textfield',
      '#size' => 30,
      '#title' => t('Attribute of the User\'s LDAP Entry DN which contains the group'),
      '#description' => t('e.g. ou'),
      '#states' => array(
        'enabled' => array(   // action to take.
          ':input[name=grp_derive_from_dn]' => array('checked' => TRUE),
        ),
        'visible' => array(   // action to take.
          ':input[name=grp_unused]' => array('checked' => FALSE),
        ),
      ),
    );

   $form['groups']['grp_test_grp_dn'] = array(
      '#default_value' => $server->get('grp_test_grp_dn'),
      '#type' => 'textfield',
      '#size' => 120,
      '#title' => t('Testing LDAP Group DN'),
      '#description' => t('This is optional and can be useful for debugging and validating forms.'),
      '#states' => array(
        'visible' => array(   // action to take.
          ':input[name=grp_unused]' => array('checked' => FALSE),
        ),
      ),
    );

   $form['groups']['grp_test_grp_dn_writeable'] = array(
      '#default_value' => $server->get('grp_test_grp_dn_writeable'),
      '#type' => 'textfield',
      '#size' => 120,
      '#title' => t('Testing LDAP Group DN that is writable.  WARNING the test script for the server will create, delete, and add members to this group!'),
      '#description' => t('This is optional and can be useful for debugging and validating forms.'),
      '#states' => array(
        'visible' => array(   // action to take.
          ':input[name=grp_unused]' => array('checked' => FALSE),
        ),
      ),
    );

    $form['pagination'] = array(
      '#type' => 'fieldset',
      '#title' => t('Pagination'),
    );

    $form['pagination']['search_pagination'] = array(
      '#default_value' => $server->get('search_pagination'),
      '#type' => 'checkbox',
      '#title' => t('Use LDAP Pagination.'),
    );

    $form['pagination']['search_page_size'] = array(
      '#default_value' => $server->get('search_page_size'),
      '#type' => 'textfield',
      '#size' => 10,
      '#title' => t('Pagination size limit.'),
      '#description' => t('This should be equal to or smaller than the max
        number of entries returned at a time by your ldap server.
        1000 is a good guess when unsure. Other modules such as LDAP Query
        or LDAP Feeds will be allowed to set a smaller page size, but not
        a larger one.'),
      '#states' => array(
        'visible' => array(   // action to take.
          ':input[name="search_pagination"]' => array('checked' => TRUE),
        ),
      ),
    );

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $server = $this->entity;
    $status = $server->save();

    switch ($status) {
      case SAVED_NEW:
        drupal_set_message($this->t('Created the %label Server.', [
          '%label' => $server->label(),
        ]));
        break;

      default:
        drupal_set_message($this->t('Saved the %label Server.', [
          '%label' => $server->label(),
        ]));
    }
    $form_state->setRedirectUrl($server->urlInfo('collection'));
  }

}
