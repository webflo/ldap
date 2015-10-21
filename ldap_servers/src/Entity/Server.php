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
        'max_length' => 20,
        'size' => 20,
        'text_processing' => 0,
      ))
      ->setDisplayOptions('view', array(
        'label' => 'above',
        'type' => 'string',
      ))
      ->setDisplayOptions('form', array(
        'type' => 'string',
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
        'max_length' => 255,
        'text_processing' => 0,
      ))
      ->setDisplayOptions('view', array(
        'label' => 'above',
        'type' => 'string',
      ))
      ->setDisplayOptions('form', array(
        'type' => 'string',
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
      ))
      ->setDisplayOptions('form', array(
        'type' => 'string',
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
      ->setDefaultValue('default')
      ->setDisplayOptions('view', array(
        'label' => 'above',
        'type' => 'string',
      ))
      ->setDisplayOptions('form', array(
        'type' => 'options_select',
      ))
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['address'] = BaseFieldDefinition::create('string')
      ->setLabel(t('LDAP Server'))
      ->setDescription(t('The domain name or IP address of your LDAP Server such as "ad.unm.edu". For SSL use the form ldaps://DOMAIN such as "ldaps://ad.unm.edu"'))
      ->setDisplayOptions('view', array(
        'label' => 'above',
        'type' => 'string',
      ))
      ->setDisplayOptions('form', array(
        'type' => 'string',
      ))
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['port'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('LDAP port'))
      ->setDescription(t('The TCP/IP port on the above server which accepts LDAP connections. Must be an integer.'))
      ->setSettings(array(
        'length' => 11,
      ))
      ->setDefaultValue(389)
      ->setDisplayOptions('view', array(
        'label' => 'above',
        'type' => 'string',
      ))
      ->setDisplayOptions('form', array(
        'type' => 'string',
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
      ))
      ->setDisplayOptions('form', array(
        'type' => 'string',
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
      ))
      ->setDisplayOptions('form', array(
        'type' => 'string',
      ))
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['weight'] = BaseFieldDefinition::create('integer')
    ->setLabel(t('Weight'))
    ->setDescription(t('Link weight among links in the same menu at the same depth. In the menu, the links with high weight will sink and links with a low weight will be positioned nearer the top.'))
    ->setDefaultValue(0)
    ->setDisplayOptions('view', array(
      'label' => 'hidden',
    ));

    $fields['bind_method'] = BaseFieldDefinition::create('list_integer')
      ->setLabel(t('Binding Method for Searches (such as finding user object or their group memberships)'))
      ->setSettings(array(
        'allowed_values' => array(
          LDAP_SERVERS_BIND_METHOD_SERVICE_ACCT => t('Service Account Bind: Use credentials in the
          <strong>Service Account</strong> field to bind to LDAP.  <em>This option is usually a best practice.</em>'),

          LDAP_SERVERS_BIND_METHOD_USER => t('Bind with Users Credentials: Use user\'s entered credentials
          to bind to LDAP.<br/> This is only useful for modules that execute during user logon such
          as LDAP Authentication and LDAP Authorization.  <em>This option is not a best practice in most cases.</em>
          This option skips the initial anonymous bind and anonymous search to determine the LDAP user DN, but you
          can only use this option if your user DNs follow a consistent pattern, for example all of them being of
          the form "cn=[username],[base dn]", or all of them being of the form "uid=[username],ou=accounts,[base dn]".
          You specify the pattern under "Expression for user DN" in the next configuration block below.'),

          LDAP_SERVERS_BIND_METHOD_ANON_USER => t('Anonymous Bind for search, then Bind with Users Credentials:
          Searches for user dn then uses user\'s entered credentials to bind to LDAP.<br/> This is only useful for
          modules that work during user logon such as LDAP Authentication and LDAP Authorization.
          The user\'s dn must be discovered by an anonymous search for this option to work.'),

          LDAP_SERVERS_BIND_METHOD_ANON => t('Anonymous Bind: Use no credentials to bind to LDAP server.<br/>
          <em>This option will not work on most LDAPS connections.</em>'),
        ),
      ))
      ->setDisplayOptions('view', array(
        'label' => 'above',
      ))
      ->setDisplayOptions('form', array(
        'label' => 'above',
        'type' => 'options_buttons',
      ));

    $fields['binddn'] = BaseFieldDefinition::create('string')
      ->setLabel(t('DN for non-anonymous search'))
      ->setSettings(array(
        'max_length' => 511,
        'text_processing' => 0,
      ))
      ->setDisplayOptions('view', array(
        'label' => 'above',
        'type' => 'string',
      ))
      ->setDisplayOptions('form', array(
        'type' => 'string',
        'size' => 80,
      ))
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // $fields['bindpw'] = BaseFieldDefinition::create('password')
    $fields['bindpw'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Password for non-anonymous search'))
      ->setDisplayOptions('form', array(
        'label' => 'above',
        'type' => 'string',
      ));

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
      'settings' => array(
        'rows' => 6,
        'cols' => 50,
      ),
    ));


    $fields['user_attr'] = BaseFieldDefinition::create('string')
      ->setLabel(t('AuthName attribute'))
      ->setDescription(t('The attribute that holds the users\' login name. (eg. <code>cn</code> for eDir or <code>sAMAccountName</code> for Active Directory).'))
      ->setSettings(array(
        'max_length' => 255,
        'text_processing' => 0,
      ))
      ->setDisplayOptions('view', array(
        'label' => 'above',
        'type' => 'string',
      ))
      ->setDisplayOptions('form', array(
        'type' => 'string',
        'size' => 30,
      ))
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['account_name_attr'] = BaseFieldDefinition::create('string')
      ->setLabel(t('AccountName attribute'))
      ->setDescription(t('The attribute that holds the unique account name. Defaults to the same as the AuthName attribute.'))
      ->setSettings(array(
        'max_length' => 255,
        'text_processing' => 0,
      ))
      ->setDisplayOptions('view', array(
        'label' => 'above',
        'type' => 'string',
      ))
      ->setDisplayOptions('form', array(
        'type' => 'string',
        'size' => 30,
      ))
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['mail_attr'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Email attribute'))
      ->setDescription(t('The attribute that holds the users\' email address. (eg. <code>mail</code>). Leave empty if no such attribute exists'))
      ->setSettings(array(
        'max_length' => 255,
        'text_processing' => 0,
      ))
      ->setDisplayOptions('view', array(
        'label' => 'above',
        'type' => 'string',
      ))
      ->setDisplayOptions('form', array(
        'type' => 'string',
        'size' => 30,
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
        'max_length' => 255,
        'text_processing' => 0,
      ))
      ->setDisplayOptions('view', array(
        'label' => 'above',
        'type' => 'string',
      ))
      ->setDisplayOptions('form', array(
        'type' => 'string',
        'size' => 30,
      ))
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['picture_attr'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Thumbnail attribute'))
      ->setDescription(t('The attribute that holds the users\' thumnail image. (eg. <code>thumbnailPhoto</code>). Leave empty if no such attribute exists'))
      ->setSettings(array(
        'max_length' => 255,
        'text_processing' => 0,
      ))
      ->setDisplayOptions('view', array(
        'label' => 'above',
        'type' => 'string',
      ))
      ->setDisplayOptions('form', array(
        'type' => 'string',
        'size' => 30,
      ))
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['unique_persistent_attr'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Persistent and Unique User ID Attribute'))
      ->setDescription(t('In some LDAPs, a user\'s DN, CN, or mail value may
              change when a user\'s name changes or for other reasons.
              In order to avoid creation of multiple accounts for that user or other ambiguities,
              enter a unique and persistent ldap attribute for users.  In cases
              where DN does not change, enter "dn" here.
              If no such attribute exists, leave this blank.'))
      ->setSettings(array(
        'max_length' => 64,
        'text_processing' => 0,
      ))
      ->setDisplayOptions('view', array(
        'label' => 'above',
        'type' => 'string',
      ))
      ->setDisplayOptions('form', array(
        'type' => 'string',
        'size' => 30,
      ))
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);


    //   'unique_persistent_attr_binary' => array(
    //     'form' => array(
    //       'fieldset' => 'users',
    //       '#type' => 'checkbox',
    //       '#title' => t('Does PUID hold a binary value?'),
    //       '#description' => t(''),
    //     ),
    //     'schema' => array(
    //       'type' => 'int',
    //       'size' => 'tiny',
    //       'not null' => FALSE,
    //       'default' => 0,
    //     ),
    //   ),

    $fields['unique_persistent_attr_binary'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Does PUID hold a binary value?'))
      ->setDefaultValue(FALSE)
      ->setSettings(array(
        'on_label' => t('Does PUID hold a binary value?'),
      ))
      ->setDisplayOptions('view', array(
        'label' => 'above',
        'type' => 'string',
      ))
      ->setDisplayOptions('form', array(
        'type' => 'string',
      ));

    //   'user_dn_expression' => array(
    //     'form' => array(
    //       'fieldset' => 'users',
    //       '#type' => 'textfield',
    //       '#size' => 80,
    //       '#title' => t('Expression for user DN. Required when "Bind with Users Credentials" method selected.'),
    //       '#description' => t('%username and %basedn are valid tokens in the expression.
    //         Typically it will be:<br/> <code>cn=%username,%basedn</code>
    //          which might evaluate to <code>cn=jdoe,ou=campus accounts,dc=ad,dc=mycampus,dc=edu</code>
    //          Base DNs are entered above.'),
    //     ),
    //     'schema' => array(
    //       'type' => 'varchar',
    //       'length' => 255,
    //       'not null' => FALSE,
    //     ),
    //   ),

    $fields['user_dn_expression'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Expression for user DN. Required when "Bind with Users Credentials" method selected.'))
      ->setDescription(t('%username and %basedn are valid tokens in the expression.
            Typically it will be:<br/> <code>cn=%username,%basedn</code>
            which might evaluate to <code>cn=jdoe,ou=campus accounts,dc=ad,dc=mycampus,dc=edu</code>
            Base DNs are entered above.'))
      ->setSettings(array(
        'max_length' => 255,
        'text_processing' => 0,
      ))
      ->setDisplayOptions('view', array(
        'label' => 'above',
        'type' => 'string',
      ))
      ->setDisplayOptions('form', array(
        'type' => 'string',
        'size' => 80,
      ))
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);


    //   'ldap_to_drupal_user' =>  array(
    //     'form' => array(
    //       'fieldset' => 'users',
    //       '#disabled' => (!\Drupal::moduleHandler()->moduleExists('php')),
    //       '#type' => 'textarea',
    //       '#cols' => 25,
    //       '#rows' => 5,
    //       '#title' => t('PHP to transform Drupal login username to LDAP UserName attribute.'),
    //       '#description' => t('This will appear as disabled unless the "PHP filter" core module is enabled. Enter PHP to transform Drupal username to the value of the UserName attribute.
    //         The code should print the UserName attribute.
    //         PHP filter module must be enabled for this to work.
    //         The variable $name is available and is the user\'s login username.
    //         Careful, bad PHP code here will break your site. If left empty, no name transformation will be done.
    //         <br/>Example:<br/>Given the user will logon with jdoe@xyz.com and you want the ldap UserName attribute to be
    //         jdoe.<br/><code>$parts = explode(\'@\', $name); if (count($parts) == 2) {print $parts[0]};</code>'),
    //       ),
    //     'schema' => array(
    //       'type' => 'varchar',
    //       'length' => 1024,
    //       'not null' => FALSE,
    //     ),
    //   ),

    $fields['ldap_to_drupal_user'] = BaseFieldDefinition::create('string_long')
      ->setLabel(t('PHP to transform Drupal login username to LDAP UserName attribute.'))
      ->setDescription(t('This will appear as disabled unless the "PHP filter" core module is enabled. Enter PHP to transform Drupal username to the value of the UserName attribute.
            The code should print the UserName attribute.
            PHP filter module must be enabled for this to work.
            The variable $name is available and is the user\'s login username.
            Careful, bad PHP code here will break your site. If left empty, no name transformation will be done.
            <br/>Example:<br/>Given the user will logon with jdoe@xyz.com and you want the ldap UserName attribute to be
            jdoe.<br/><code>$parts = explode(\'@\', $name); if (count($parts) == 2) {print $parts[0]};</code>'))
      ->setSettings(array(
        'max_length' => 1024,
        'text_processing' => 0,
      ))
      ->setDisplayOptions('view', array(
        'label' => 'above',
        'type' => 'string',
      ))
      ->setDisplayOptions('form', array(
        'type' => 'string_textarea',
        'size' => 80,
        'cols' => 25,
        'rows' => 5,
      ));

    //  'testing_drupal_username' =>  array(
    //     'form' => array(
    //       'fieldset' => 'users',
    //       '#type' => 'textfield',
    //       '#size' => 30,
    //       '#title' => t('Testing Drupal Username'),
    //       '#description' => t('This is optional and used for testing this server\'s configuration against an actual username.  The user need not exist in Drupal and testing will not affect the user\'s LDAP or Drupal Account.'),
    //     ),
    //     'schema' => array(
    //       'type' => 'varchar',
    //       'length' => 255,
    //       'not null' => FALSE,
    //     ),
    //   ),

    $fields['testing_drupal_username'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Testing Drupal Username'))
      ->setDescription(t('This is optional and used for testing this server\'s configuration against an actual username.  The user need not exist in Drupal and testing will not affect the user\'s LDAP or Drupal Account.'))
      ->setSettings(array(
        'max_length' => 255,
        'text_processing' => 0,
      ))
      ->setDisplayOptions('view', array(
        'label' => 'above',
        'type' => 'string',
      ))
      ->setDisplayOptions('form', array(
        'type' => 'string',
        'size' => 30,
      ));

    //  'testing_drupal_user_dn' =>  array(
    //     'form' => array(
    //       'fieldset' => 'users',
    //       '#type' => 'textfield',
    //       '#size' => 120,
    //       '#title' => t('DN of testing username, e.g. cn=hpotter,ou=people,dc=hogwarts,dc=edu'),
    //       '#description' => t('This is optional and used for testing this server\'s configuration against an actual username.  The user need not exist in Drupal and testing will not affect the user\'s LDAP or Drupal Account.'),
    //     ),
    //     'schema' => array(
    //       'type' => 'varchar',
    //       'length' => 255,
    //       'not null' => FALSE,
    //     ),
    //   ),

    $fields['testing_drupal_user_dn'] = BaseFieldDefinition::create('string')
      ->setLabel(t('DN of testing username, e.g. cn=hpotter,ou=people,dc=hogwarts,dc=edu'))
      ->setDescription(t('This is optional and used for testing this server\'s configuration against an actual username.  The user need not exist in Drupal and testing will not affect the user\'s LDAP or Drupal Account.'))
      ->setSettings(array(
        'max_length' => 255,
        'text_processing' => 0,
      ))
      ->setDisplayOptions('view', array(
        'label' => 'above',
        'type' => 'string',
      ))
      ->setDisplayOptions('form', array(
        'type' => 'string',
        'size' => 120,
      ));

    //   'grp_unused' => array(
    //     'form' => array(
    //       'fieldset' => 'groups',
    //       '#type' => 'checkbox',
    //       '#title' => t('Groups are not relevant to this Drupal site.  This is generally true if LDAP Groups, LDAP Authorization, etc are not it use.'),
    //       '#disabled' => FALSE,
    //     ),
    //     'schema' => array(
    //       'type' => 'int',
    //       'size' => 'tiny',
    //       'not null' => FALSE,
    //       'default' => 0,
    //     ),
    //   ),

    $fields['grp_unused'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Groups are not relevant to this Drupal site.  This is generally true if LDAP Groups, LDAP Authorization, etc are not it use.'))
      ->setSettings(array(
        'on_label' => t('Groups are not relevant to this Drupal site.  This is generally true if LDAP Groups, LDAP Authorization, etc are not it use.'),
      ))
      ->setDefaultValue(TRUE)
      ->setDisplayOptions('view', array(
        'label' => 'above',
        'type' => 'string',
      ))
      ->setDisplayOptions('form', array(
        'type' => 'string',
      ));

    //  'grp_object_cat' =>  array(
    //     'form' => array(
    //       'fieldset' => 'groups',
    //       '#type' => 'textfield',
    //       '#size' => 30,
    //       '#title' => t('Name of Group Object Class'),
    //       '#description' => t('e.g. groupOfNames, groupOfUniqueNames, group.'),
    //       '#states' => array(
    //           'visible' => array(   // action to take.
    //             ':input[name=grp_unused]' => array('checked' => FALSE),
    //           ),
    //         ),
    //     ),
    //     'schema' => array(
    //       'type' => 'varchar',
    //       'length' => 64,
    //       'not null' => FALSE,
    //     ),
    //   ),

    $fields['grp_object_cat'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Name of Group Object Class'))
      ->setDescription(t('e.g. groupOfNames, groupOfUniqueNames, group.'))
      ->setSettings(array(
        'max_length' => 64,
        'text_processing' => 0,
      ))
      // ->setConstraints(array(
      //   'grp_unused' => array('checked' => FALSE),
      // ))
      ->setDisplayOptions('view', array(
        'label' => 'above',
        'type' => 'string',
      ))
      ->setDisplayOptions('form', array(
        'type' => 'string',
        'size' => 30,
      ));

    //   'grp_nested' => array(
    //     'form' => array(
    //       'fieldset' => 'groups',
    //       '#type' => 'checkbox',
    //       '#title' => t('Nested groups are used in my LDAP'),
    //       '#disabled' => FALSE,
    //       '#description' => t('If a user is a member of group A and group A is a member of group B,
    //          user should be considered to be in group A and B.  If your LDAP has nested groups, but you
    //          want to ignore nesting, leave this unchecked.'),
    //       '#states' => array(
    //           'visible' => array(   // action to take.
    //             ':input[name=grp_unused]' => array('checked' => FALSE),
    //           ),
    //         ),
    //     ),
    //     'schema' => array(
    //       'type' => 'int',
    //       'size' => 'tiny',
    //       'not null' => FALSE,
    //       'default' => 0,
    //     ),
    //   ),

    $fields['grp_nested'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Nested groups are used in my LDAP'))
      ->setDescription(t('If a user is a member of group A and group A is a member of group B,
              user should be considered to be in group A and B.  If your LDAP has nested groups, but you
              want to ignore nesting, leave this unchecked.'))
      ->setSettings(array(
        'on_label' => t('Nested groups are used in my LDAP'),
      ))
      ->setDisplayOptions('view', array(
        'label' => 'above',
        'type' => 'string',
      ))
      ->setDisplayOptions('form', array(
        'type' => 'string',
      ));

    //   'grp_user_memb_attr_exists' => array(
    //     'form' => array(
    //       'fieldset' => 'groups',
    //       '#type' => 'checkbox',
    //       '#title' => t('A user LDAP attribute such as <code>memberOf</code> exists that contains a list of their groups.
    //         Active Directory and openLdap with memberOf overlay fit this model.'),
    //       '#disabled' => FALSE,
    //       '#states' => array(
    //          'visible' => array(   // action to take.
    //            ':input[name=grp_unused]' => array('checked' => FALSE),
    //           ),
    //         ),
    //     ),
    //     'schema' => array(
    //       'type' => 'int',
    //       'size' => 'tiny',
    //       'not null' => FALSE,
    //       'default' => 0,
    //     ),
    //   ),

    $fields['grp_user_memb_attr_exists'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('A user LDAP attribute such as <code>memberOf</code> exists that contains a list of their groups.
            Active Directory and openLdap with memberOf overlay fit this model.'))
      ->setSettings(array(
        'on_label' => t('A user LDAP attribute such as <code>memberOf</code> exists that contains a list of their groups.
            Active Directory and openLdap with memberOf overlay fit this model.'),
      ))
      ->setDisplayOptions('view', array(
        'label' => 'above',
        'type' => 'string',
      ))
      ->setDisplayOptions('form', array(
        'type' => 'string',
      ));

    //   'grp_user_memb_attr' =>  array(
    //     'form' => array(
    //       'fieldset' => 'groups',
    //       '#type' => 'textfield',
    //       '#size' => 30,
    //       '#title' => t('Attribute in User Entry Containing Groups'),
    //       '#description' => t('e.g. memberOf'),
    //       '#states' => array(
    //         'enabled' => array(   // action to take.
    //           ':input[name=grp_user_memb_attr_exists]' => array('checked' => TRUE),
    //         ),
    //           'visible' => array(   // action to take.
    //           ':input[name=grp_unused]' => array('checked' => FALSE),
    //         ),
    //       ),
    //     ),
    //     'schema' => array(
    //       'type' => 'varchar',
    //       'length' => 255,
    //       'not null' => FALSE,
    //     ),
    //   ),

    $fields['grp_user_memb_attr'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Attribute in User Entry Containing Groups'))
      ->setDescription(t('e.g. memberOf'))
      ->setSettings(array(
        'max_length' => 255,
        'text_processing' => 0,
      ))
      ->setDisplayOptions('view', array(
        'label' => 'above',
        'type' => 'string',
      ))
      ->setDisplayOptions('form', array(
        'type' => 'string',
        'size' => 30,
      ));

    //   'grp_memb_attr' =>  array(
    //     'form' => array(
    //       'fieldset' => 'groups',
    //       '#type' => 'textfield',
    //       '#size' => 30,
    //       '#title' => t('LDAP Group Entry Attribute Holding User\'s DN, CN, etc.'),
    //       '#description' => t('e.g uniquemember, memberUid'),
    //       '#states' => array(
    //           'visible' => array(   // action to take.
    //             ':input[name=grp_unused]' => array('checked' => FALSE),
    //           ),
    //         ),
    //     ),
    //     'schema' => array(
    //       'type' => 'varchar',
    //       'length' => 255,
    //       'not null' => FALSE,
    //     ),
    //   ),

    $fields['grp_memb_attr'] = BaseFieldDefinition::create('string')
      ->setLabel(t('LDAP Group Entry Attribute Holding User\'s DN, CN, etc.'))
      ->setDescription(t('e.g uniquemember, memberUid'))
      ->setSettings(array(
        'max_length' => 255,
        'text_processing' => 0,
      ))
      ->setDisplayOptions('view', array(
        'label' => 'above',
        'type' => 'string',
      ))
      ->setDisplayOptions('form', array(
        'type' => 'string',
        'size' => 30,
      ));

    //   'grp_memb_attr_match_user_attr' =>  array(
    //     'form' => array(
    //       'fieldset' => 'groups',
    //       '#type' => 'textfield',
    //       '#size' => 30,
    //       '#title' => t('User attribute held in "LDAP Group Entry Attribute Holding..."'),
    //       '#description' => t('This is almost always "dn" (which technically isn\'t an attribute).  Sometimes its "cn".'),
    //       '#states' => array(
    //           'visible' => array(   // action to take.
    //             ':input[name=grp_unused]' => array('checked' => FALSE),
    //           ),
    //         ),
    //     ),
    //     'schema' => array(
    //       'type' => 'varchar',
    //       'length' => 255,
    //       'not null' => FALSE,
    //     ),
    //   ),

    $fields['grp_memb_attr_match_user_attr'] = BaseFieldDefinition::create('string')
      ->setLabel(t('User attribute held in "LDAP Group Entry Attribute Holding..."'))
      ->setDescription(t('This is almost always "dn" (which technically isn\'t an attribute).  Sometimes its "cn".'))
      ->setSettings(array(
        'max_length' => 255,
        'text_processing' => 0,
      ))
      ->setDisplayOptions('view', array(
        'label' => 'above',
        'type' => 'string',
      ))
      ->setDisplayOptions('form', array(
        'type' => 'string',
        'size' => 30,
      ));

    //   'grp_derive_from_dn' => array(
    //     'form' => array(
    //       'fieldset' => 'groups',
    //       '#type' => 'checkbox',
    //       '#title' => t('Groups are derived from user\'s LDAP entry DN.') . '<em>' .
    //         t('This
    //         group definition has very limited functionality and most modules will
    //         not take this into account.  LDAP Authorization will.') . '</em>',
    //       '#disabled' => FALSE,
    //       '#states' => array(
    //           'visible' => array(   // action to take.
    //             ':input[name=grp_unused]' => array('checked' => FALSE),
    //           ),
    //         ),
    //     ),
    //     'schema' => array(
    //       'type' => 'int',
    //       'size' => 'tiny',
    //       'not null' => FALSE,
    //       'default' => 0,
    //     ),
    //   ),

    $fields['grp_derive_from_dn'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Groups are derived from user\'s LDAP entry DN.')  . '<em>' .
            t('This
            group definition has very limited functionality and most modules will
            not take this into account.  LDAP Authorization will.') . '</em>')
      ->setSettings(array(
        'on_label' => t('Groups are derived from user\'s LDAP entry DN.')  . '<em>' . t('This
            group definition has very limited functionality and most modules will
            not take this into account.  LDAP Authorization will.') . '</em>',
      ))
      ->setDisplayOptions('view', array(
        'label' => 'above',
        'type' => 'string',
      ))
      ->setDisplayOptions('form', array(
        'type' => 'string',
      ));

    //   'grp_derive_from_dn_attr' =>  array(
    //     'form' => array(
    //       'fieldset' => 'groups',
    //       '#type' => 'textfield',
    //       '#size' => 30,
    //       '#title' => t('Attribute of the User\'s LDAP Entry DN which contains the group'),
    //       '#description' => t('e.g. ou'),
    //       '#states' => array(
    //         'enabled' => array(   // action to take.
    //           ':input[name=grp_derive_from_dn]' => array('checked' => TRUE),
    //         ),
    //           'visible' => array(   // action to take.
    //           ':input[name=grp_unused]' => array('checked' => FALSE),
    //         ),
    //       ),
    //     ),
    //     'schema' => array(
    //       'type' => 'varchar',
    //       'length' => 255,
    //       'not null' => FALSE,
    //     ),
    //   ),

    $fields['grp_derive_from_dn_attr'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Attribute of the User\'s LDAP Entry DN which contains the group'))
      ->setDescription(t('e.g. ou'))
      ->setSettings(array(
        'max_length' => 255,
        'text_processing' => 0,
      ))
      ->setDisplayOptions('view', array(
        'label' => 'above',
        'type' => 'string',
      ))
      ->setDisplayOptions('form', array(
        'type' => 'string',
        'size' => 30,
      ));

    //  'grp_test_grp_dn' =>  array(
    //     'form' => array(
    //       'fieldset' => 'groups',
    //       '#type' => 'textfield',
    //       '#size' => 120,
    //       '#title' => t('Testing LDAP Group DN'),
    //       '#description' => t('This is optional and can be useful for debugging and validating forms.'),
    //       '#states' => array(
    //           'visible' => array(   // action to take.
    //             ':input[name=grp_unused]' => array('checked' => FALSE),
    //           ),
    //         ),
    //     ),
    //     'schema' => array(
    //       'type' => 'varchar',
    //       'length' => 255,
    //       'not null' => FALSE,
    //     ),
    //   ),

    $fields['grp_test_grp_dn'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Testing LDAP Group DN'))
      ->setDescription(t('This is optional and can be useful for debugging and validating forms.'))
      ->setSettings(array(
        'max_length' => 255,
        'text_processing' => 0,
      ))
      ->setDisplayOptions('view', array(
        'label' => 'above',
        'type' => 'string',
      ))
      ->setDisplayOptions('form', array(
        'type' => 'string',
        'size' => 120,
      ));

    //  'grp_test_grp_dn_writeable' =>  array(
    //     'form' => array(
    //       'fieldset' => 'groups',
    //       '#type' => 'textfield',
    //       '#size' => 120,
    //       '#title' => t('Testing LDAP Group DN that is writable.  WARNING the test script for the server will create, delete, and add members to this group!'),
    //       '#description' => t('This is optional and can be useful for debugging and validating forms.'),
    //       '#states' => array(
    //           'visible' => array(   // action to take.
    //             ':input[name=grp_unused]' => array('checked' => FALSE),
    //           ),
    //         ),
    //     ),
    //     'schema' => array(
    //       'type' => 'varchar',
    //       'length' => 255,
    //       'not null' => FALSE,
    //     ),
    //   ),

    $fields['grp_test_grp_dn_writeable'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Testing LDAP Group DN that is writable.  WARNING the test script for the server will create, delete, and add members to this group!'))
      ->setDescription(t('This is optional and can be useful for debugging and validating forms.'))
      ->setSettings(array(
        'max_length' => 255,
        'text_processing' => 0,
      ))
      ->setDisplayOptions('view', array(
        'label' => 'above',
        'type' => 'string',
      ))
      ->setDisplayOptions('form', array(
        'type' => 'string',
        'size' => 120,
      ));

    //   'search_pagination' => array(
    //     'form' => array(
    //       'fieldset' => 'pagination',
    //       '#type' => 'checkbox',
    //       '#title' => t('Use LDAP Pagination.'),
    //       '#disabled' => !ldap_servers_php_supports_pagination(),
    //     ),
    //     'schema' => array(
    //       'type' => 'int',
    //       'size' => 'tiny',
    //       'not null' => FALSE,
    //       'default' => 0,
    //     ),
    //   ),

    $fields['search_pagination'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Use LDAP Pagination.'))
      ->setSettings(array(
        'on_label' => t('Use LDAP Pagination.'),
      ))
      ->setDisplayOptions('view', array(
        'label' => 'above',
        'type' => 'string',
      ))
      ->setDisplayOptions('form', array(
        'type' => 'string',
      ));

    //  'search_page_size' =>  array(
    //     'form' => array(
    //       'fieldset' => 'pagination',
    //       '#type' => 'textfield',
    //       '#size' => 10,
    //       '#disabled' => !ldap_servers_php_supports_pagination(),
    //       '#title' => t('Pagination size limit.'),
    //       '#description' => t('This should be equal to or smaller than the max
    //         number of entries returned at a time by your ldap server.
    //         1000 is a good guess when unsure. Other modules such as LDAP Query
    //         or LDAP Feeds will be allowed to set a smaller page size, but not
    //         a larger one.'),
    //       '#states' => array(
    //         'visible' => array(   // action to take.
    //           ':input[name="search_pagination"]' => array('checked' => TRUE),
    //         ),
    //   ),
    //     ),
    //     'schema' => array(
    //       'type' => 'int',
    //       'size' => 'medium',
    //       'not null' => FALSE,
    //       'default' => 1000,
    //     ),
    //   ),

    //   'weight' =>  array(
    //     'schema' => array(
    //       'type' => 'int',
    //       'not null' => FALSE,
    //       'default' => 0,
    //     ),
    //   ),
    // );

    $fields['search_page_size'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Pagination size limit.'))
      ->setDescription(t('This should be equal to or smaller than the max
             number of entries returned at a time by your ldap server.
             1000 is a good guess when unsure. Other modules such as LDAP Query
             or LDAP Feeds will be allowed to set a smaller page size, but not
             a larger one.'))
      ->setSettings(array(
        'length' => 11,
      ))
      ->setDefaultValue(1000)
      ->setDisplayOptions('view', array(
        'label' => 'above',
        'type' => 'string',
      ))
      ->setDisplayOptions('form', array(
        'type' => 'string',
      ));


    return $fields;
  }
}
