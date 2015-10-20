<?php
/**
 * @file
 * Contains \Drupal\ldap_servers\Entity\Server.
 */

namespace Drupal\ldap_servers\Entity;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\user\UserInterface;

/**
 * Defines the LDAPServer entity.
 *
 * @ingroup ldap_server
 *
 * @ContentEntityType(
 *   id = "ldap_server",
 *   label = @Translation("Server entity"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\ldap_servers\Entity\Controller\ServerListBuilder",
 *     "form" = {
 *       "add" = "Drupal\ldap_servers\Form\LdapServersAdminForm",
 *       "edit" = "Drupal\ldap_servers\Form\LdapServersAdminForm",
 *       "delete" = "Drupal\ldap_servers\Form\LdapServersAdminDelete",
 *       "test" = "Drupal\ldap_servers\Form\LdapServersTestForm",
 *       "enable_disable" = "Drupal\ldap_servers\Form\LdapServersAdminEnableDisable",
 *     },
 *   },
 *   base_table = "ldap_servers",
 *   admin_permission = "administer ldap_servers module",
 *   fieldable = FALSE,
 *   entity_keys = {
 *     "id" = "sid",
 *     "uuid" = "uuid",
 *     "label" = "name",
 *   },
 *   links = {
 *     "edit-form" = "/ldap/servers/edit/{ldap_server}",
 *     "delete-form" = "/ldap/servers/delete/{ldap_server}",
 *     "test-form" = "/ldap/servers/test/{ldap_server}",
 *   },
 * )
 *
 */
class Server extends ContentEntityBase {

  /**
   * {@inheritdoc}
   *
   * When a new entity instance is added, set the user_id entity reference to
   * the current user as the creator of the instance.
   */
  public static function preCreate(EntityStorageInterface $storage_controller, array &$values) {
    parent::preCreate($storage_controller, $values);
    // $values += array(
    //   'user_id' => \Drupal::currentUser()->id(),
    // );
  }

  /**
   * {@inheritdoc}
   *
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {

    $fields['sid'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Machine name for this server configuration.'))
      ->setDescription(t('May only contain alphanumeric characters (a-z, A-Z, 0-9, and _)'))
      ->setReadOnly(TRUE)
      ->setRequired(TRUE)
      ->setSettings(array(
        'default_value' => '',
        'max_length' => 20,
        'size' => 20,
        'text_processing' => 0,
      ))
      ->setDisplayOptions('view', array(
        'label' => 'above',
        'type' => 'string',
        'weight' => -7,
      ))
      ->setDisplayOptions('form', array(
        'type' => 'string',
        'weight' => -7,
      ))
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE)
      ->setSettings(array(
        'type' => 'serial',
      ));

    $fields['numeric_sid'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Numeric SID'))
      ->setDescription(t('The SID of the Server entity.'));

    $fields['uuid'] = BaseFieldDefinition::create('uuid')
      ->setLabel(t('UUID'))
      ->setDescription(t('The UUID of the Server entity.'))
      ->setReadOnly(TRUE);

    $fields['name'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Name'))
      ->setDescription(t('Choose a unique <strong><em>name</em></strong> for this server configuration.'))
      ->setRequired(TRUE)
      ->setSettings(array(
        'default_value' => '',
        'max_length' => 255,
        'text_processing' => 0,
      ))
      ->setDisplayOptions('view', array(
        'label' => 'above',
        'type' => 'string',
        'weight' => -6,
      ))
      ->setDisplayOptions('form', array(
        'type' => 'string',
        'weight' => -6,
      ))
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['status'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Enabled'))
      ->setDescription(t('Disable in order to keep configuration without having it active.'))
      ->setDefaultValue(TRUE)
      ->setSettings(array(
        'on_label' => 'Enabled',
      ))
      ->setDisplayOptions('view', array(
        'label' => 'above',
        'type' => 'string',
        'weight' => -5,
      ))
      ->setDisplayOptions('form', array(
        'type' => 'string',
        'weight' => -5,
      ))
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['type'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('LDAP Server type'))
      ->setDescription(t('This field is informative. It\'s purpose is to assist with default values and give validation warnings.'))
      ->setSettings(array(
        'allowed_values' => array(
          'default' => 'Default LDAP',
          'ad' => 'Active Directory',
          'novell_edir' => 'Novell',
          'openldap' => 'Open LDAP',
          'opendir' => 'Apple Open Directory',
        ),
      ))
      ->setDisplayOptions('view', array(
        'label' => 'above',
        'type' => 'string',
        'weight' => -4,
      ))
      ->setDisplayOptions('form', array(
        'type' => 'options_select',
        'weight' => -4,
      ))
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['address'] = BaseFieldDefinition::create('string')
      ->setLabel(t('LDAP Server'))
      ->setDescription(t('The domain name or IP address of your LDAP Server such as "ad.unm.edu". For SSL use the form ldaps://DOMAIN such as "ldaps://ad.unm.edu"'))
      ->setDisplayOptions('view', array(
        'label' => 'above',
        'type' => 'string',
        'weight' => -3,
      ))
      ->setDisplayOptions('form', array(
        'type' => 'string',
        'weight' => -3,
      ))
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['port'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('LDAP port'))
      ->setDescription(t('The TCP/IP port on the above server which accepts LDAP connections. Must be an integer.'))
      ->setSettings(array(
        'default_value' => '389',
        'length' => 11,
      ))
      ->setDisplayOptions('view', array(
        'label' => 'above',
        'type' => 'string',
        'weight' => -2,
      ))
      ->setDisplayOptions('form', array(
        'type' => 'string',
        'weight' => -2,
      ));

    $fields['tls'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Use Start-TLS'))
      ->setDescription(t('Secure the connection between the Drupal and the LDAP servers using TLS.<br><em>Note: To use START-TLS, you must set the LDAP Port to 389.</em>'))
      ->setDefaultValue(FALSE)
      ->setSettings(array(
        'on_label' => 'Use Start-TLS',
      ))
      ->setDisplayOptions('view', array(
        'label' => 'above',
        'type' => 'string',
        'weight' => -1,
      ))
      ->setDisplayOptions('form', array(
        'type' => 'string',
        'weight' => -1,
      ))
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['followrefs'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Follow LDAP Referrals'))
      ->setDescription(t('Makes the LDAP client follow referrals (in the responses from the LDAP server) to other LDAP servers. This requires that the Bind Settings you give, is ALSO valid on these other servers.'))
      ->setDefaultValue(FALSE)
      ->setSettings(array(
        'on_label' => 'Follow LDAP Referrals',
      ))
      ->setDisplayOptions('view', array(
        'label' => 'above',
        'type' => 'string',
        'weight' => 0,
      ))
      ->setDisplayOptions('form', array(
        'type' => 'string',
        'weight' => 0,
      ))
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['weight'] = BaseFieldDefinition::create('integer')
    ->setLabel(t('Weight'))
    ->setDescription(t('Link weight among links in the same menu at the same depth. In the menu, the links with high weight will sink and links with a low weight will be positioned nearer the top.'))
    ->setDefaultValue(0)
    ->setDisplayOptions('view', array(
      'label' => 'hidden',
      'type' => 'integer',
      'weight' => 0,
    ));



    $fields['bind_method'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Binding Method for Searches (such as finding user object or their group memberships)'))
      ->setSettings(array(
        'allowed_values' => array(
          'default' => 'Default LDAP',
          'ad' => 'Active Directory',
          'novell_edir' => 'Novell',
          'openldap' => 'Open LDAP',
          'opendir' => 'Apple Open Directory',
        ),
    //   ->setDescription(t('This field is informative. It\'s purpose is to assist with default values and give validation warnings.'))
    //   ->setSettings(array(
    //     'options' => array(
    //       LDAP_SERVERS_BIND_METHOD_SERVICE_ACCT => t('Service Account Bind: Use credentials in the
    //       <strong>Service Account</strong> field to bind to LDAP.  <em>This option is usually a best practice.</em>'),

    //       LDAP_SERVERS_BIND_METHOD_USER => t('Bind with Users Credentials: Use user\'s entered credentials
    //       to bind to LDAP.<br/> This is only useful for modules that execute during user logon such
    //       as LDAP Authentication and LDAP Authorization.  <em>This option is not a best practice in most cases.</em>
    //       This option skips the initial anonymous bind and anonymous search to determine the LDAP user DN, but you
    //       can only use this option if your user DNs follow a consistent pattern, for example all of them being of
    //       the form "cn=[username],[base dn]", or all of them being of the form "uid=[username],ou=accounts,[base dn]".
    //       You specify the pattern under "Expression for user DN" in the next configuration block below.'),

    //       LDAP_SERVERS_BIND_METHOD_ANON_USER => t('Anonymous Bind for search, then Bind with Users Credentials:
    //       Searches for user dn then uses user\'s entered credentials to bind to LDAP.<br/> This is only useful for
    //       modules that work during user logon such as LDAP Authentication and LDAP Authorization.
    //       The user\'s dn must be discovered by an anonymous search for this option to work.'),

    //       LDAP_SERVERS_BIND_METHOD_ANON => t('Anonymous Bind: Use no credentials to bind to LDAP server.<br/>
    //       <em>This option will not work on most LDAPS connections.</em>'),
    //     ),
    //     '#type' => 'radios',
    //     'form' => array(
    //       '#options' => array(
    //         LDAP_SERVERS_BIND_METHOD_SERVICE_ACCT => t('Service Account Bind: Use credentials in the
    //         <strong>Service Account</strong> field to bind to LDAP.  <em>This option is usually a best practice.</em>'),

    //         LDAP_SERVERS_BIND_METHOD_USER => t('Bind with Users Credentials: Use user\'s entered credentials
    //         to bind to LDAP.<br/> This is only useful for modules that execute during user logon such
    //         as LDAP Authentication and LDAP Authorization.  <em>This option is not a best practice in most cases.</em>
    //         This option skips the initial anonymous bind and anonymous search to determine the LDAP user DN, but you
    //         can only use this option if your user DNs follow a consistent pattern, for example all of them being of
    //         the form "cn=[username],[base dn]", or all of them being of the form "uid=[username],ou=accounts,[base dn]".
    //         You specify the pattern under "Expression for user DN" in the next configuration block below.'),

    //         LDAP_SERVERS_BIND_METHOD_ANON_USER => t('Anonymous Bind for search, then Bind with Users Credentials:
    //         Searches for user dn then uses user\'s entered credentials to bind to LDAP.<br/> This is only useful for
    //         modules that work during user logon such as LDAP Authentication and LDAP Authorization.
    //         The user\'s dn must be discovered by an anonymous search for this option to work.'),

    //         LDAP_SERVERS_BIND_METHOD_ANON => t('Anonymous Bind: Use no credentials to bind to LDAP server.<br/>
    //         <em>This option will not work on most LDAPS connections.</em>'),
    //       ),
    //     ),
      ))
    //   ->setDisplayOptions('view', array(
    //     'label' => 'above',
    //     'weight' => -5,
    //   ))
      ->setDisplayOptions('form', array(
        'label' => 'above',
        'type' => 'radio',
        'weight' => -5,
      ));

    $fields['binddn'] = BaseFieldDefinition::create('string')
      ->setLabel(t('DN for non-anonymous search'))
      ->setSettings(array(
        'default_value' => '',
        'max_length' => 511,
        'text_processing' => 0,
      ))
      ->setDisplayOptions('view', array(
        'label' => 'above',
        'type' => 'string',
        'weight' => 1,
      ))
      ->setDisplayOptions('form', array(
        'type' => 'string',
        'size' => 80,
        'weight' => 1,
      ))
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['basedn'] = BaseFieldDefinition::create('string_long')
    ->setLabel(t('Base DNs for LDAP users, groups, and other entries.'))
    ->setDescription(t('What DNs have entries relavant to this configuration?
            e.g. <code>ou=campus accounts,dc=ad,dc=uiuc,dc=edu</code>
            Keep in mind that every additional basedn likely doubles the number of queries.  Place the
            more heavily used one first and consider using one higher base DN rather than 2 or more lower base DNs.
            Enter one per line in case if you need more than one.'))
    ->setRevisionable(TRUE)
    ->setDefaultValue('')
    ->setDisplayOptions('form', array(
      'type' => 'string_textarea',
      'weight' => 1,
      'settings' => array(
        'rows' => 6,
        'cols' => 50,
      ),
    ));

    $fields['bindpw'] = BaseFieldDefinition::create('password')
      ->setLabel(t('Password for non-anonymous search'))
      ->setDescription(t('The password of this user (hashed).'))
      ->addConstraint('ProtectedUserField')
      ->setDisplayOptions('form', array(
        'weight' => 2,
      ));

    $fields['user_attr'] = BaseFieldDefinition::create('string')
      ->setLabel(t('AuthName attribute'))
      ->setDescription(t('The attribute that holds the users\' login name. (eg. <code>cn</code> for eDir or <code>sAMAccountName</code> for Active Directory).'))
      ->setSettings(array(
        'default_value' => '',
        'max_length' => 255,
        'text_processing' => 0,
      ))
      ->setDisplayOptions('view', array(
        'label' => 'above',
        'type' => 'string',
        'weight' => 2,
      ))
      ->setDisplayOptions('form', array(
        'type' => 'string',
        'size' => 30,
        'weight' => 2,
      ))
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['account_name_attr'] = BaseFieldDefinition::create('string')
      ->setLabel(t('AccountName attribute'))
      ->setDescription(t('The attribute that holds the unique account name. Defaults to the same as the AuthName attribute.'))
      ->setSettings(array(
        'default_value' => '',
        'max_length' => 255,
        'text_processing' => 0,
      ))
      ->setDisplayOptions('view', array(
        'label' => 'above',
        'type' => 'string',
        'weight' => 3,
      ))
      ->setDisplayOptions('form', array(
        'type' => 'string',
        'size' => 30,
        'weight' => 3,
      ))
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['mail_attr'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Email attribute'))
      ->setDescription(t('The attribute that holds the users\' email address. (eg. <code>mail</code>). Leave empty if no such attribute exists'))
      ->setSettings(array(
        'default_value' => '',
        'max_length' => 255,
        'text_processing' => 0,
      ))
      ->setDisplayOptions('view', array(
        'label' => 'above',
        'type' => 'string',
        'weight' => 4,
      ))
      ->setDisplayOptions('form', array(
        'type' => 'string',
        'size' => 30,
        'weight' => 4,
      ))
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['mail_template'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Email template'))
      ->setDescription(t('If no attribute contains the user\'s email address, but it can be derived from other attributes,
            enter an email "template" here.
            Templates should have the user\'s attribute name in form such as [cn], [uin], etc.
            such as <code>[cn]@mycompany.com</code>.
            See http://drupal.org/node/997082 for additional documentation on ldap tokens.'))
      ->setSettings(array(
        'default_value' => '',
        'max_length' => 255,
        'text_processing' => 0,
      ))
      ->setDisplayOptions('view', array(
        'label' => 'above',
        'type' => 'string',
        'weight' => 5,
      ))
      ->setDisplayOptions('form', array(
        'type' => 'string',
        'size' => 30,
        'weight' => 5,
      ))
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);


    return $fields;
  }
}
