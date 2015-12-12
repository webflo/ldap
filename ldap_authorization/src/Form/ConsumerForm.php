<?php

/**
 * @file
 * Contains \Drupal\ldap_authorization\Form\ConsumerForm.
 *
 * class to encapsulate an ldap authorization ldap entry to authorization ids mapping
 */

namespace Drupal\ldap_authorization\Form;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class ConsumerForm.
 *
 * @package Drupal\ldap_authorization\Form
 */
class ConsumerForm extends EntityForm {
  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    $ldap_authorization_consumer = $this->entity;
    $form['label'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#maxlength' => 255,
      '#default_value' => $ldap_authorization_consumer->label(),
      '#description' => $this->t("Label for the LDAP Consumer."),
      '#required' => TRUE,
    );

    $form['id'] = array(
      '#type' => 'machine_name',
      '#default_value' => $ldap_authorization_consumer->id(),
      '#machine_name' => array(
        'exists' => '\Drupal\ldap_authorization\Entity\Consumer::load',
      ),
      '#disabled' => !$ldap_authorization_consumer->isNew(),
    );
    /* You will need additional form elements for your custom properties. */

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $ldap_authorization_consumer = $this->entity;

    /*
    $op = $this->inDatabase ? 'edit' : 'insert';
    $values = new stdClass; // $this;
    $values->sid = $this->sid;
    $values->numeric_consumer_conf_id = $this->numericConsumerConfId;
    $values->consumer_type = $this->consumerType;
    $values->consumer_module = $this->consumer->consumerModule;
    $values->status = ($this->status) ? 1 : 0;
    $values->only_ldap_authenticated = (int)$this->onlyApplyToLdapAuthenticated;
    $values->use_first_attr_as_groupid = (int)$this->useFirstAttrAsGroupId;
    $values->mappings = serialize($this->mappings);
    $values->use_filter = (int)$this->useMappingsAsFilter;
    $values->synch_to_ldap = (int)$this->synchToLdap;
    $values->synch_on_logon = (int)$this->synchOnLogon;
    $values->revoke_ldap_provisioned = (int)$this->revokeLdapProvisioned;
    $values->create_consumers = (int)$this->createConsumers;
    $values->regrant_ldap_provisioned = (int)$this->regrantLdapProvisioned;

    if (module_exists('ctools')) {
      ctools_include('export');
      // Populate our object with ctool's properties
      $object = ctools_export_crud_new('ldap_authorization');
      foreach ($object as $property => $value) {
        if (!isset($values->$property)) {
          $values->$property = $value;
        }
      }
      try {
        $values->export_type = NULL;
        $result = ctools_export_crud_save('ldap_authorization', $values);
      } catch (Exception $e) {
        //  debug($e); Integrity constraint violation: 1062 Duplicate entry
        $values->export_type = EXPORT_IN_DATABASE;
        $result = ctools_export_crud_save('ldap_authorization', $values);
      }
      ctools_export_load_object_reset('ldap_authorization'); // ctools_export_crud_save doesn't invalidate cache
    }
    else {

      if ($op == 'edit') {
        $result = drupal_write_record('ldap_authorization', $values, 'consumer_type');
      }
      else { // insert
        $result = drupal_write_record('ldap_authorization', $values);
      }

      if ($result) {
        $this->inDatabase = TRUE;
      }
      else {
        drupal_set_message(t('Failed to write LDAP Authorization to the database.'));
      }
    }
    */

    $status = $ldap_authorization_consumer->save();

    switch ($status) {
      case SAVED_NEW:
        drupal_set_message($this->t('Created the %label LDAP Consumer.', [
          '%label' => $ldap_authorization_consumer->label(),
        ]));
        break;

      default:
        drupal_set_message($this->t('Saved the %label LDAP Consumer.', [
          '%label' => $ldap_authorization_consumer->label(),
        ]));
    }
    $form_state->setRedirectUrl($ldap_authorization_consumer->urlInfo('collection'));

  }

  public $fields;
  public $consumers;

  public function delete() {
    if ($this->consumerType) {
      $this->inDatabase = FALSE;
      return db_delete('ldap_authorization')->condition('consumer_type', $this->consumerType)->execute();
    }
    else {
      return FALSE;
    }
  }

  public function __construct(&$consumer = NULL, $new = FALSE) {
    // parent::__construct($consumer, $new);
    $this->fields = $this->fields();
    $this->consumers = ldap_authorization_get_consumers(NULL, TRUE);

    if ($new) {
      foreach ($this->consumer->defaultConsumerConfProperties as $property => $value) {
        $this->$property = $value;
      }
    }
  }

  public function drupalForm($server_options, $op) {

    $consumer_tokens = ldap_authorization_tokens($this->consumer);
    $form['intro'] = array(
        '#type' => 'item',
        '#markup' => t('<h1>LDAP to !consumer_name Configuration</h1>', $consumer_tokens),
    );

    $form['status'] = array(
      '#type' => 'fieldset',
      '#title' => t('I.  Basics', $consumer_tokens),
      '#collapsible' => TRUE,
      '#collapsed' => FALSE,
    );

    $form['status']['sid'] = array(
      '#type' => 'radios',
      '#title' => t('LDAP Server used in !consumer_name configuration.', $consumer_tokens),
      '#required' => 1,
      '#default_value' => $this->sid,
      '#options' => $server_options,
    );

    $form['status']['consumer_type'] = array(
      '#type' => 'hidden',
      '#value' => $this->consumerType,
      '#required' => 1,
    );

    $form['status']['status'] = array(
      '#type' => 'checkbox',
      '#title' => t('Enable this configuration', $consumer_tokens),
      '#default_value' =>  $this->status,
    );

    $form['status']['only_ldap_authenticated'] = array(
      '#type' => 'checkbox',
      '#title' => t('Only apply the following LDAP to !consumer_name configuration to users authenticated via LDAP.  On uncommon reason for disabling this is when you are using Drupal authentication, but want to leverage LDAP for authorization; for this to work the Drupal username still has to map to an LDAP entry.', $consumer_tokens),
      '#default_value' =>  $this->onlyApplyToLdapAuthenticated,
    );


    if (method_exists($this->consumer, 'mappingExamples')) {
      $consumer_tokens['!examples'] = '<fieldset class="collapsible collapsed form-wrapper" id="authorization-mappings">
<legend><span class="fieldset-legend">' . t('Examples based on current !consumer_namePlural', $consumer_tokens) . '</span></legend>
<div class="fieldset-wrapper">'. $this->consumer->mappingExamples($consumer_tokens) . '<div class="fieldset-wrapper">
</fieldset>';
    }
    else {
      $consumer_tokens['!examples'] = '';
    }
    $form['filter_and_mappings'] = array(
      '#type' => 'fieldset',
      '#title' => t('II. LDAP to !consumer_name mapping and filtering', $consumer_tokens),
      '#description' => t('
Representations of groups derived from LDAP might initially look like:
<ul>
<li><code>cn=students,ou=groups,dc=hogwarts,dc=edu</code></li>
<li><code>cn=gryffindor,ou=groups,dc=hogwarts,dc=edu</code></li>
<li><code>cn=faculty,ou=groups,dc=hogwarts,dc=edu</code></li>
<li><code>cn=probation students,ou=groups,dc=hogwarts,dc=edu</code></li>
</ul>

<p><strong>Mappings are used to convert and filter these group representations to !consumer_namePlural.</strong></p>

!consumer_mappingDirections

!examples

', $consumer_tokens),
      '#collapsible' => TRUE,
      '#collapsed' => !($this->mappings || $this->useMappingsAsFilter || $this->useFirstAttrAsGroupId),
    );

    $form['filter_and_mappings']['use_first_attr_as_groupid'] = array(
      '#type' => 'checkbox',
      '#title' => t('Convert full dn to value of first attribute before mapping.  e.g.  <code>cn=students,ou=groups,dc=hogwarts,dc=edu</code> would be converted to <code>students</code>', $consumer_tokens),
      '#default_value' => $this->useFirstAttrAsGroupId,
    );
    $form['filter_and_mappings']['mappings'] = array(
      '#type' => 'textarea',
      '#title' => t('Mapping of LDAP to !consumer_name (one per line)', $consumer_tokens),
      '#default_value' => $this->mappingsToPipeList($this->mappings),
      '#cols' => 50,
      '#rows' => 5,
    );
    $form['filter_and_mappings']['use_filter'] = array(
      '#type' => 'checkbox',
      '#title' => t('Only grant !consumer_namePlural that match a filter above.', $consumer_tokens),
      '#default_value' => $this->useMappingsAsFilter,
      '#description' => t('If enabled, only above mapped !consumer_namePlural will be assigned (e.g. students and administrator).
        <strong>If not checked, !consumer_namePlural not mapped above also may be created and granted (e.g. gryffindor and probation students).  In some LDAPs this can lead to hundreds of !consumer_namePlural being created if "Create !consumer_namePlural if they do not exist" is enabled below.
        </strong>', $consumer_tokens)
    );


    $form['more'] = array(
      '#type' => 'fieldset',
      '#title' => t('Part III.  Even More Settings.'),
      '#collapsible' => TRUE,
      '#collapsed' => FALSE,
    );

    $synchronization_modes = array();
    if ($this->synchOnLogon)  {
      $synchronization_modes[] = 'user_logon';
    }
    $form['more']['synchronization_modes'] = array(
      '#type' => 'checkboxes',
      '#title' => t('When should !consumer_namePlural be granted/revoked from user?', $consumer_tokens),
      '#options' => array(
          'user_logon' => t('When a user logs on.'),
      ),
      '#default_value' => $synchronization_modes,
      '#description' => '',
    );

    $synchronization_actions = array();
    if ($this->revokeLdapProvisioned)  {
      $synchronization_actions[] = 'revoke_ldap_provisioned';
    }
    if ($this->createConsumers)  {
      $synchronization_actions[] = 'create_consumers';
    }
    if ($this->regrantLdapProvisioned)  {
      $synchronization_actions[] = 'regrant_ldap_provisioned';
    }

    $options =  array(
      'revoke_ldap_provisioned' => t('Revoke !consumer_namePlural previously granted by LDAP Authorization but no longer valid.', $consumer_tokens),
      'regrant_ldap_provisioned' => t('Re grant !consumer_namePlural previously granted by LDAP Authorization but removed manually.', $consumer_tokens),
    );
    if ($this->consumer->allowConsumerObjectCreation) {
      $options['create_consumers'] = t('Create !consumer_namePlural if they do not exist.', $consumer_tokens);
    }

    $form['more']['synchronization_actions'] = array(
      '#type' => 'checkboxes',
      '#title' => t('What actions would you like performed when !consumer_namePlural are granted/revoked from user?', $consumer_tokens),
      '#options' => $options,
      '#default_value' => $synchronization_actions,
    );
    /**
     * @todo  some general options for an individual mapping (perhaps in an advance tab).
     *
     * - on synchronization allow: revoking authorizations made by this module, authorizations made outside of this module
     * - on synchronization create authorization contexts not in existance when needed (drupal roles etc)
     * - synchronize actual authorizations (not cached) when granting authorizations
     */

    switch ($op) {
      case 'add':
      $action = 'Add';
      break;

      case 'edit':
      $action = 'Save';
      break;

      case 'delete':
      $action = 'Delete';
      break;
    }

    $form['submit'] = array(
      '#type' => 'submit',
      '#value' => $action,
    );

  return $form;
  }


  protected function loadFromForm($values, $op) {

  }

  public function getLdapAuthorizationConsumerActions() {
    $actions = array();
    $actions[] =  l(t('edit'), LDAP_SERVERS_MENU_BASE_PATH . '/authorization/edit/' . $this->consumerType);
    if (property_exists($this, 'type')) {
      if ($this->type == 'Overridden') {
          $actions[] = l(t('revert'), LDAP_SERVERS_MENU_BASE_PATH . '/authorization/delete/' . $this->consumerType);
      }
      if ($this->type == 'Normal') {
          $actions[] = l(t('delete'), LDAP_SERVERS_MENU_BASE_PATH . '/authorization/delete/' . $this->consumerType);
      }
    }
    else {
        $actions[] = l(t('delete'), LDAP_SERVERS_MENU_BASE_PATH . '/authorization/delete/' . $this->consumerType);
    }
    $actions[] = l(t('test'), LDAP_SERVERS_MENU_BASE_PATH . '/authorization/test/' . $this->consumerType);
    return $actions;
  }

  public function drupalFormValidate($op, $values)  {
    $errors = array();

    if ($op == 'delete') {
      if (!$this->consumerType) {
        $errors['consumer_type_missing'] = 'Consumer type is missing from delete form.';
      }
    }
    else {

      $this->populateFromDrupalForm($op, $values);
      $errors = $this->validate($values);
      if (count($this->mappings) == 0 && trim($values['mappings'])) {
        $errors['mappings'] = t('Bad mapping syntax.  Text entered but not able to convert to array.');
      }

    }
    return $errors;
  }

  public function validate($form_values = array()) {
    $errors = array();

    if (!$this->consumerType) {
      $errors['consumer_type'] = t('Consumer type is missing.');
    }

    if ($this->inDatabase  && (!$this->consumerType)) {
      $errors['consumer_type'] = t('Edit or delete called without consumer type in form.');
    }

    if (count($this->mappings) > 0) {
      foreach ($this->mappings as $mapping_item) {
        list($type, $text) = $this->consumer->validateAuthorizationMappingTarget($mapping_item, $form_values);
        if ($type == 'error') {
          $errors['mappings'] = $text;
        }
        elseif ($type == 'warning' ||  $type == 'status') {
          drupal_set_message(check_plain($text), $type);
        }
      }
    }
    if ($this->useMappingsAsFilter && !count($this->mappings)) {
      $errors['mappings'] = t('Mappings are missing.  Mappings must be supplied if filtering is enabled.');
    }
    return $errors;
  }

  protected function populateFromDrupalForm($op, $values) {

    $this->inDatabase = (drupal_strtolower($op) == 'edit' || drupal_strtolower($op) == 'save');
    $this->consumerType = $values['consumer_type'];

    $this->sid = $values['sid'];

    $this->status = (bool)$values['status'];
    $this->onlyApplyToLdapAuthenticated  = (bool)(@$values['only_ldap_authenticated']);
    $this->useFirstAttrAsGroupId  = (bool)($values['use_first_attr_as_groupid']);

    $this->mappings = $this->consumer->normalizeMappings($this->pipeListToArray($values['mappings'], FALSE));
    $this->useMappingsAsFilter  = (bool)(@$values['use_filter']);

    $this->synchOnLogon = (bool)(@$values['synchronization_modes']['user_logon']);
    $this->regrantLdapProvisioned = (bool)(@$values['synchronization_actions']['regrant_ldap_provisioned']);
    $this->revokeLdapProvisioned = (bool)(@$values['synchronization_actions']['revoke_ldap_provisioned']);
    $this->createConsumers = (bool)(@$values['synchronization_actions']['create_consumers']);

  }

  public function drupalFormSubmit($op, $values) {

    $this->populateFromDrupalForm($op, $values);
    if ($op == 'delete') {
      $this->delete();
    }
    else { // add or edit

      try {
        $save_result = $this->save();
      }
      catch (Exception $e) {
        $this->errorName = 'Save Error';
        $this->errorMsg = t('Failed to save object.  Your form data was not saved.');
        $this->hasError = TRUE;
      }
    }
  }


  public static function fields() {

     /**
     * consumer_type is tag (unique alphanumeric id) of consuming authorization such as
     *   drupal_roles, og_groups, civicrm_memberships
     */
    $fields = array(
      'numeric_consumer_conf_id' => array(
          'schema' => array(
            'type' => 'serial',
            'unsigned' => TRUE,
            'not null' => TRUE,
            'description' => 'Primary ID field for the table.  Only used internally.',
            'no export' => TRUE,
          ),
        ),
      'sid' => array(
        'schema' => array(
          'type' => 'varchar',
          'length' => 20,
          'not null' => TRUE,
        )
      ),
      'consumer_type' => array(
         'schema' => array(
            'type' => 'varchar',
            'length' => 20,
            'not null' => TRUE,
        )
      ),
     'consumer_module' => array(
         'schema' => array(
            'type' => 'varchar',
            'length' => 30,
            'not null' => TRUE,
        )
      ),

      'status' => array(
          'schema' => array(
            'type' => 'int',
            'size' => 'tiny',
            'not null' => TRUE,
            'default' => 0,
          )
      ),
      'only_ldap_authenticated' => array(
        'schema' => array(
          'type' => 'int',
          'size' => 'tiny',
          'not null' => TRUE,
          'default' => 1,
        )
      ),

      'use_first_attr_as_groupid' => array(
        'schema' => array(
          'type' => 'int',
          'size' => 'tiny',
          'not null' => TRUE,
          'default' => 0,
        )
      ),

      'mappings'  => array(
        'form_default' => array(),
        'schema' => array(
          'type' => 'text',
          'not null' => FALSE,
          'default' => NULL,
        )
      ),

      'use_filter' => array(
        'schema' => array(
          'type' => 'int',
          'size' => 'tiny',
          'not null' => TRUE,
          'default' => 1,
        )
      ),

      'synchronization_modes' => array(
        'form_default' =>  array('user_logon'),
      ),

      'synchronization_actions' => array(
        'form_default' =>  array('revoke_ldap_provisioned', 'create_consumers'),
      ),

      'synch_to_ldap'  => array(
        'schema' => array(
          'type' => 'int',
          'size' => 'tiny',
          'not null' => TRUE,
          'default' => 0,
        ),
      ),

      'synch_on_logon'  => array(
        'schema' => array(
          'type' => 'int',
          'size' => 'tiny',
          'not null' => TRUE,
          'default' => 0,
        ),
      ),

      'revoke_ldap_provisioned'  => array(
        'schema' => array(
          'type' => 'int',
          'size' => 'tiny',
          'not null' => TRUE,
          'default' => 0,
        ),
      ),

     'create_consumers'  => array(
        'schema' => array(
          'type' => 'int',
          'size' => 'tiny',
          'not null' => TRUE,
          'default' => 0,
        ),
      ),

     'regrant_ldap_provisioned'  => array(
        'schema' => array(
          'type' => 'int',
          'size' => 'tiny',
          'not null' => TRUE,
          'default' => 0,
        ),
      ),
    );
    return $fields;
  }


  protected function mappingsToPipeList($mappings) {
    $result_text = "";
    foreach ($mappings as $map) {
      $result_text .= $map['from'] . '|' . $map['user_entered'] . "\n";
    }
    return $result_text;
  }


}
