<?php
/**
 * @file
 * Contains \Drupal\ldap_servers\Entity\Server.
 */

namespace Drupal\ldap_servers\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\ldap_servers\ServerInterface;

/**
 * Defines the Server entity.
 *
 * @ConfigEntityType(
 *   id = "ldap_server",
 *   label = @Translation("LDAP Server"),
 *   handlers = {
 *     "list_builder" = "Drupal\ldap_servers\ServerListBuilder",
 *     "form" = {
 *       "add" = "Drupal\ldap_servers\Form\ServerForm",
 *       "edit" = "Drupal\ldap_servers\Form\ServerForm",
 *       "delete" = "Drupal\ldap_servers\Form\ServerDeleteForm",
 *       "test" = "Drupal\ldap_servers\Form\ServerTestForm"
 *     }
 *   },
 *   config_prefix = "server",
 *   admin_permission = "administer site configuration",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid"
 *   },
 *   links = {
 *     "canonical" = "/admin/config/people/ldap/server/{server}",
 *     "edit-form" = "/admin/config/people/ldap/server/{server}/edit",
 *     "delete-form" = "/admin/config/people/ldap/server/{server}/delete",
 *     "collection" = "/admin/config/people/ldap/server"
 *   }
 * )
 */
class Server extends ConfigEntityBase implements ServerInterface {

  /**
   * The Server ID.
   *
   * @var string
   */
  protected $id;

  /**
   * The Server label.
   *
   * @var string
   */
  protected $label;

  /**
   * Connect Method
   */
  public function connect() {
    $port = (self::get('port'));
    $address = (self::get('address'));

    $con = ldap_connect($address, $port);

    if ( ! $con ) {
      \Drupal::logger('user')->notice('LDAP Connect failure to ' . $address . ':' . $port, []);
      return LDAP_CONNECT_ERROR;
    }

    ldap_set_option($con, LDAP_OPT_PROTOCOL_VERSION, 3);
    ldap_set_option($con, LDAP_OPT_REFERRALS, 0);

    // Use TLS if we are configured and able to.
    if (self::get('tls')) {
      ldap_get_option($con, LDAP_OPT_PROTOCOL_VERSION, $vers);
      if ($vers == -1) {
        \Drupal::logger('user')->notice('Could not get LDAP protocol version.', []);
        return LDAP_PROTOCOL_ERROR;
      }
      if ($vers != 3) {
        \Drupal::logger('user')->notice('Could not start TLS, only supported by LDAP v3.', []);
        return LDAP_CONNECT_ERROR;
      }
      elseif (!function_exists('ldap_start_tls')) {
        \Drupal::logger('user')->notice('Could not start TLS. It does not seem to be supported by this PHP setup.', []);
        return LDAP_CONNECT_ERROR;
      }
      elseif (!ldap_start_tls($con)) {
        $msg =  t("Could not start TLS. (Error %errno: %error).", array('%errno' => ldap_errno($con), '%error' => ldap_error($con)));
        \Drupal::logger('user')->notice($msg, []);
        return LDAP_CONNECT_ERROR;
      }
    }

    // Store the resulting resource
    $this->connection = $con;
    return LDAP_SUCCESS;
  }


  /**
   * Bind (authenticate) against an active LDAP database.
   *
   * @param $userdn
   *   The DN to bind against. If NULL, we use $this->binddn
   * @param $pass
   *   The password search base. If NULL, we use $this->bindpw
   *
   * @return
   *   Result of bind; TRUE if successful, FALSE otherwise.
   */
  public function bind($userdn = NULL, $pass = NULL, $anon_bind = NULL) {
    // Ensure that we have an active server connection.
    if (!$this->connection) {
      \Drupal::logger('ldap')->notice("LDAP bind failure for user %user. Not connected to LDAP server.", array('%user' => $userdn));
      return LDAP_CONNECT_ERROR;
    }

    if ($anon_bind !== FALSE && $userdn === NULL && $pass === NULL && $this->get('bind_method') == LDAP_SERVERS_BIND_METHOD_ANON) {
      $anon_bind = TRUE;
    }
    if ($anon_bind === TRUE) {
      if (@!ldap_bind($this->connection)) {
        if ($this->detailedWatchdogLog) {
          \Drupal::logger('ldap')->notice("LDAP anonymous bind error. Error %errno: %error", array('%errno' => ldap_errno($this->connection), '%error' => ldap_error($this->connection)));
        }
        return ldap_errno($this->connection);
      }
    }
    else {
      $userdn = ($userdn != NULL) ? $userdn : $this->get('binddn');
      $pass = ($pass != NULL) ? $pass : $this->get('bindpw');

      if (\Drupal\Component\Utility\Unicode::strlen($pass) == 0 || \Drupal\Component\Utility\Unicode::strlen($userdn) == 0) {
        \Drupal::logger('ldap')->notice("LDAP bind failure for user userdn=%userdn, pass=%pass.", array('%userdn' => $userdn, '%pass' => $pass));
        return LDAP_LOCAL_ERROR;
      }
      if (@!ldap_bind($this->connection, $userdn, $pass)) {
        if ($this->detailedWatchdogLog) {
          \Drupal::logger('ldap')->notice("LDAP bind failure for user %user. Error %errno: %error", array('%user' => $userdn, '%errno' => ldap_errno($this->connection), '%error' => ldap_error($this->connection)));
        }
        return ldap_errno($this->connection);
      }
    }

    return LDAP_SUCCESS;
  }


  /**
   * Disconnect (unbind) from an active LDAP server.
   */
  function disconnect() {
    if (!$this->connection) {
      // never bound or not currently bound, so no need to disconnect
      //watchdog('ldap', 'LDAP disconnect failure from '. $this->server_addr . ':' . $this->port);
    }
    else {
      ldap_unbind($this->connection);
      $this->connection = NULL;
    }
  }

  public function connectAndBindIfNotAlready() {
    if (! $this->connection) {
      $this->connect();
      $this->bind();
    }
  }

/**
 * does dn exist for this server?
 * [ ] Finished
 * [ ] Test Coverage.  Test ID:
 * [ ] Case insensitive
 *
 * @param string $dn
 * @param enum $return = 'boolean' or 'ldap_entry'
 * @param array $attributes in same form as ldap_read $attributes parameter
 *
 * @param return FALSE or ldap entry array
 */
  function dnExists($dn, $return = 'boolean', $attributes = NULL) {

    $params = array(
      'base_dn' => $dn,
      'attributes' => $attributes,
      'attrsonly' => FALSE,
      'filter' => '(objectclass=*)',
      'sizelimit' => 0,
      'timelimit' => 0,
      'deref' => NULL,
    );

    if ($return == 'boolean' || !is_array($attributes)) {
      $params['attributes'] = array('objectclass');
    }
    else {
      $params['attributes'] = $attributes;
    }

    $result = $this->ldapQuery(LDAP_SCOPE_BASE, $params);
    if ($result !== FALSE) {
      $entries = @ldap_get_entries($this->connection, $result);
      if ($entries !== FALSE && $entries['count'] > 0) {
        return ($return == 'boolean') ? TRUE : $entries[0];
      }
    }

    return FALSE;

  }

  /**
   * @param $ldap_result as ldap link identifier
   *
   * @return FALSE on error or number of entries.
   *   (if 0 entries will return 0)
   */
  public function countEntries($ldap_result) {
    return ldap_count_entries($this->connection, $ldap_result);
  }



  /**
   * create ldap entry.
   *
   * @param array $attributes should follow the structure of ldap_add functions
   *   entry array: http://us.php.net/manual/en/function.ldap-add.php
   *    $attributes["attribute1"] = "value";
   *    $attributes["attribute2"][0] = "value1";
   *    $attributes["attribute2"][1] = "value2";
   * @return boolean result
   */

  public function createLdapEntry($attributes, $dn = NULL) {

    if (!$this->connection) {
      $this->connect();
      $this->bind();
    }
    if (isset($attributes['dn'])) {
      $dn = $attributes['dn'];
      unset($attributes['dn']);
    }
    elseif (!$dn) {
      return FALSE;
    }

    $result = @ldap_add($this->connection, $dn, $attributes);
    if (!$result) {
      $error = "LDAP Server ldap_add(%dn) Error Server ID = %sid, LDAP Err No: %ldap_errno LDAP Err Message: %ldap_err2str ";
      $tokens = array('%dn' => $dn, '%id' => $this->id(), '%ldap_errno' => ldap_errno($this->connection), '%ldap_err2str' => ldap_err2str(ldap_errno($this->connection)));
      debug(t($error, $tokens));
      \Drupal::logger('ldap_server')->error($error, []);
    }

    return $result;
  }



/**
 * given 2 ldap entries, old and new, removed unchanged values to avoid security errors and incorrect date modifieds
 *
 * @param ldap entry array $new_entry in form <attribute> => <value>
 * @param ldap entry array $old_entry in form <attribute> => array('count' => N, array(<value>,...<value>
 *
 * @return ldap array with no values that have NOT changed
 */

  static public function removeUnchangedAttributes($new_entry, $old_entry) {

    foreach ($new_entry as $key => $new_val) {
      $old_value = FALSE;
      $key_lcase = \Drupal\Component\Utility\Unicode::strtolower($key);
      if (isset($old_entry[$key_lcase])) {
        if ($old_entry[$key_lcase]['count'] == 1) {
          $old_value = $old_entry[$key_lcase][0];
          $old_value_is_scalar = TRUE;
        }
        else {
          unset($old_entry[$key_lcase]['count']);
          $old_value = $old_entry[$key_lcase];
          $old_value_is_scalar = FALSE;
        }
      }

      // identical multivalued attributes
      if (is_array($new_val) && is_array($old_value) && count(array_diff($new_val, $old_value)) == 0) {
        unset($new_entry[$key]);
      }
      elseif ($old_value_is_scalar && !is_array($new_val) && \Drupal\Component\Utility\Unicode::strtolower($old_value) == \Drupal\Component\Utility\Unicode::strtolower($new_val)) {
        unset($new_entry[$key]); // don't change values that aren't changing to avoid false permission constraints
      }
    }
    return $new_entry;
  }





  /**
   * modify attributes of ldap entry
   *
   * @param string $dn DN of entry
   * @param array $attributes should follow the structure of ldap_add functions
   *   entry array: http://us.php.net/manual/en/function.ldap-add.php
   *     $attributes["attribute1"] = "value";
   *     $attributes["attribute2"][0] = "value1";
   *     $attributes["attribute2"][1] = "value2";
   *
   * @return TRUE on success FALSE on error
   */

  function modifyLdapEntry($dn, $attributes = array(), $old_attributes = FALSE) {

    $this->connectAndBindIfNotAlready();

    if (!$old_attributes) {
      $result = @ldap_read($this->connection, $dn, 'objectClass=*');
      if (!$result) {
        $error = "LDAP Server ldap_read(%dn) in LdapServer::modifyLdapEntry() Error Server ID = %sid, LDAP Err No: %ldap_errno LDAP Err Message: %ldap_err2str ";
        $tokens = array('%dn' => $dn, '%id' => $this->id(), '%ldap_errno' => ldap_errno($this->connection), '%ldap_err2str' => ldap_err2str(ldap_errno($this->connection)));
        \Drupal::logger('ldap_server')->error($error, []);
        return FALSE;
      }

      $entries = ldap_get_entries($this->connection, $result);
      if (is_array($entries) && $entries['count'] == 1) {
        $old_attributes =  $entries[0];
      }
    }
    $attributes = $this->removeUnchangedAttributes($attributes, $old_attributes);

    foreach ($attributes as $key => $cur_val) {
      $old_value = FALSE;
      $key_lcase = \Drupal\Component\Utility\Unicode::strtolower($key);
      if (isset($old_attributes[$key_lcase])) {
        if ($old_attributes[$key_lcase]['count'] == 1) {
          $old_value = $old_attributes[$key_lcase][0];
        }
        else {
          unset($old_attributes[$key_lcase]['count']);
          $old_value = $old_attributes[$key_lcase];
        }
      }

      if ($cur_val == '' && $old_value != '') { // remove enpty attributes
        unset($attributes[$key]);
        $result = @ldap_mod_del($this->connection, $dn, array($key_lcase => $old_value));
        if (!$result) {
          $error = "LDAP Server ldap_mod_del(%dn) in LdapServer::modifyLdapEntry() Error Server ID = %sid, LDAP Err No: %ldap_errno LDAP Err Message: %ldap_err2str ";
          $tokens = array('%dn' => $dn, '%id' => $this->id(), '%ldap_errno' => ldap_errno($this->connection), '%ldap_err2str' => ldap_err2str(ldap_errno($this->connection)));
          \Drupal::logger('ldap_server')->error($error, []);
          return FALSE;
        }
      }
      elseif (is_array($cur_val)) {
        foreach ($cur_val as $mv_key => $mv_cur_val) {
          if ($mv_cur_val == '') {
            unset($attributes[$key][$mv_key]); // remove empty values in multivalues attributes
          }
          else {
            $attributes[$key][$mv_key] = $mv_cur_val;
          }
        }
      }
    }

    if (count($attributes) > 0) {
      $result = @ldap_modify($this->connection, $dn, $attributes);
      if (!$result) {
        $error = "LDAP Server ldap_modify(%dn) in LdapServer::modifyLdapEntry() Error Server ID = %sid, LDAP Err No: %ldap_errno LDAP Err Message: %ldap_err2str ";
        $tokens = array('%dn' => $dn, '%id' => $this->id(), '%ldap_errno' => ldap_errno($this->connection), '%ldap_err2str' => ldap_err2str(ldap_errno($this->connection)));
        \Drupal::logger('ldap_server')->error($error, []);
        return FALSE;
      }
    }

    return TRUE;

  }

  /**
   * Perform an LDAP search on all base dns and aggregate into one result
   *
   * @param string $filter
   *   The search filter. such as sAMAccountName=jbarclay.  attribute values (e.g. jbarclay) should be esacaped before calling

   * @param array $attributes
   *   List of desired attributes. If omitted, we only return "dn".
   *
   * @remaining params mimick ldap_search() function params
   *
   * @return
   *   An array of matching entries->attributes (will have 0
   *   elements if search returns no results),
   *   or FALSE on error on any of the basedn queries
   */

  public function searchAllBaseDns(
    $filter,
    $attributes = array(),
    $attrsonly = 0,
    $sizelimit = 0,
    $timelimit = 0,
    $deref = NULL,
    $scope = LDAP_SCOPE_SUBTREE
    ) {
    $all_entries = array();
    foreach ($this->basedn as $base_dn) {  // need to search on all basedns one at a time
      $entries = $this->search($base_dn, $filter, $attributes, $attrsonly, $sizelimit, $timelimit, $deref, $scope);  // no attributes, just dns needed
      if ($entries === FALSE) { // if error in any search, return false
        return FALSE;
      }
      if (count($all_entries) == 0) {
        $all_entries = $entries;
      }
      else {
        $existing_count = $all_entries['count'];
        unset($entries['count']);
        foreach ($entries as $i => $entry) {
          $all_entries[$existing_count + $i] = $entry;
        }
        $all_entries['count'] = count($all_entries);
      }
    }

    return $all_entries;

  }


  /**
   * Perform an LDAP search.
   * @param string $basedn
   *   The search base. If NULL, we use $this->basedn. should not be esacaped
   *
   * @param string $filter
   *   The search filter. such as sAMAccountName=jbarclay.  attribute values (e.g. jbarclay) should be esacaped before calling

   * @param array $attributes
   *   List of desired attributes. If omitted, we only return "dn".
   *
   * @remaining params mimick ldap_search() function params
   *
   * @return
   *   An array of matching entries->attributes (will have 0
   *   elements if search returns no results),
   *   or FALSE on error.
   */

  function search($base_dn = NULL, $filter, $attributes = array(),
    $attrsonly = 0, $sizelimit = 0, $timelimit = 0, $deref = NULL, $scope = LDAP_SCOPE_SUBTREE) {

     /**
      * pagingation issues:
      * -- see documentation queue: http://markmail.org/message/52w24iae3g43ikix#query:+page:1+mid:bez5vpl6smgzmymy+state:results
      * -- wait for php 5.4? https://svn.php.net/repository/php/php-src/tags/php_5_4_0RC6/NEWS (ldap_control_paged_result
      * -- http://sgehrig.wordpress.com/2009/11/06/reading-paged-ldap-results-with-php-is-a-show-stopper/
      */


    if ($base_dn == NULL) {
      if (count($this->basedn) == 1) {
        $base_dn = $this->basedn[0];
      }
      else {
        return FALSE;
      }
    }

    $attr_display =  is_array($attributes) ? join(',', $attributes) : 'none';
    $query = 'ldap_search() call: ' . join(",\n", array(
      'base_dn: ' . $base_dn,
      'filter = ' . $filter,
      'attributes: ' . $attr_display,
      'attrsonly = ' . $attrsonly,
      'sizelimit = ' . $sizelimit,
      'timelimit = ' . $timelimit,
      'deref = ' . $deref,
      'scope = ' . $scope,
      )
    );
    if (\Drupal::config('ldap_help.settings')->get('watchdog_detail')) {
      \Drupal::logger('ldap_server')->notice($query, array());
    }

    // When checking multiple servers, there's a chance we might not be connected yet.
    if (!$this->connection) {
      $this->connect();
      $this->bind();
    }

    $ldap_query_params = array(
      'connection' => $this->connection,
      'base_dn' => $base_dn,
      'filter' => $filter,
      'attributes' => $attributes,
      'attrsonly' => $attrsonly,
      'sizelimit' => $sizelimit,
      'timelimit' => $timelimit,
      'deref' => $deref,
      'query_display' => $query,
      'scope' => $scope,
    );

    if ($this->get('search_pagination')) {
      $aggregated_entries = $this->pagedLdapQuery($ldap_query_params);
      return $aggregated_entries;
    }
    else {
      $result = $this->ldapQuery($scope, $ldap_query_params);
      if ($result && ($this->countEntries($result) !== FALSE) ) {
        $entries = ldap_get_entries($this->connection, $result);
        \Drupal::moduleHandler()->alter('ldap_server_search_results', $entries, $ldap_query_params);
        return (is_array($entries)) ? $entries : FALSE;
      }
      elseif ($this->ldapErrorNumber()) {
        $watchdog_tokens =  array('%basedn' => $ldap_query_params['base_dn'], '%filter' => $ldap_query_params['filter'],
          '%attributes' => print_r($ldap_query_params['attributes'], TRUE), '%errmsg' => $this->errorMsg('ldap'),
          '%errno' => $this->ldapErrorNumber());
        \Drupal::logger('ldap')->notice("LDAP ldap_search error. basedn: %basedn| filter: %filter| attributes:
          %attributes| errmsg: %errmsg| ldap err no: %errno|", []);
        return FALSE;
      }
      else {
        return FALSE;
      }
    }
  }


  /**
   * execute a paged ldap query and return entries as one aggregated array
   *
   * $this->searchPageStart and $this->searchPageEnd should be set before calling if
   *   a particular set of pages is desired
   *
   * @param array $ldap_query_params of form:
   *  'base_dn' => base_dn,
   *  'filter' =>  filter,
   *  'attributes' => attributes,
   *  'attrsonly' => attrsonly,
   *  'sizelimit' => sizelimit,
   *  'timelimit' => timelimit,
   *  'deref' => deref,
   *  'scope' => scope,
   *
   *  (this array of parameters is primarily passed on to ldapQuery() method)
   *
   * @return array of ldap entries or FALSE on error.
   *
   */
  public function pagedLdapQuery($ldap_query_params) {
    if (!$this->get('search_pagination')) {
      \Drupal::logger('ldap')->notice("LDAP server pagedLdapQuery() called when functionality not enabled in ldap server configuration.  error. basedn: %basedn| filter: %filter| attributes: %attributes| errmsg: %errmsg| ldap err no: %errno|", []);
      return FALSE;
    }

    $paged_entries = array();
    $page_token = '';
    $page = 0;
    $estimated_entries = 0;
    $aggregated_entries = array();
    $aggregated_entries_count = 0;
    $has_page_results = FALSE;

    do {
      ldap_control_paged_result($this->connection, $this->searchPageSize, TRUE, $page_token);
      $result = $this->ldapQuery($ldap_query_params['scope'], $ldap_query_params);

      if ($page >= $this->searchPageStart) {
        $skipped_page = FALSE;
        if ($result && ($this->countEntries($result) !== FALSE) ) {
          $page_entries = ldap_get_entries($this->connection, $result);
          unset($page_entries['count']);
          $has_page_results = (is_array($page_entries) && count($page_entries) > 0);
          $aggregated_entries = array_merge($aggregated_entries, $page_entries);
          $aggregated_entries_count = count($aggregated_entries);
        }
        elseif ($this->ldapErrorNumber()) {
          $watchdog_tokens =  array('%basedn' => $ldap_query_params['base_dn'], '%filter' => $ldap_query_params['filter'],
            '%attributes' => print_r($ldap_query_params['attributes'], TRUE), '%errmsg' => $this->errorMsg('ldap'),
            '%errno' => $this->ldapErrorNumber());
          \Drupal::logger('ldap')->notice("LDAP ldap_search error. basedn: %basedn| filter: %filter| attributes:
            %attributes| errmsg: %errmsg| ldap err no: %errno|", []);
          RETURN FALSE;
        }
        else {
          return FALSE;
        }
      }
      else {
        $skipped_page = TRUE;
      }
      @ldap_control_paged_result_response($this->connection, $result, $page_token, $estimated_entries);
      if ($ldap_query_params['sizelimit'] && $this->ldapErrorNumber() == LDAP_SIZELIMIT_EXCEEDED) {
        // false positive error thrown.  do not set result limit error when $sizelimit specified
      }
      elseif ($this->hasError()) {
        \Drupal::logger('ldap_server')->error('ldap_control_paged_result_response() function error. LDAP Error: %message, ldap_list() parameters: %query', array('%message' => $this->errorMsg('ldap'), '%query' => $ldap_query_params['query_display']));
      }

      if (isset($ldap_query_params['sizelimit']) && $ldap_query_params['sizelimit'] && $aggregated_entries_count >= $ldap_query_params['sizelimit']) {
        $discarded_entries = array_splice($aggregated_entries, $ldap_query_params['sizelimit']);
        break;
      }
      elseif ($this->searchPageEnd !== NULL && $page >= $this->searchPageEnd) { // user defined pagination has run out
        break;
      }
      elseif ($page_token === NULL || $page_token == '') { // ldap reference pagination has run out
        break;
      }
      $page++;
    } while ($skipped_page || $has_page_results);

    $aggregated_entries['count'] = count($aggregated_entries);
    return $aggregated_entries;
  }

  /**
   * execute ldap query and return ldap records
   *
   * @param scope
   * @params see pagedLdapQuery $params
   *
   * @return array of ldap entries
   */
  function ldapQuery($scope, $params) {

    $this->connectAndBindIfNotAlready();

    switch ($scope) {
      case LDAP_SCOPE_SUBTREE:
        $result = @ldap_search($this->connection, $params['base_dn'], $params['filter'], $params['attributes'], $params['attrsonly'],
          $params['sizelimit'], $params['timelimit'], $params['deref']);
        if ($params['sizelimit'] && $this->ldapErrorNumber() == LDAP_SIZELIMIT_EXCEEDED) {
          // false positive error thrown.  do not return result limit error when $sizelimit specified
        }
        elseif ($this->hasError()) {
          \Drupal::logger('ldap_server')->error('ldap_search() function error. LDAP Error: %message, ldap_search() parameters: %query', array('%message' => $this->errorMsg('ldap'), '%query' => $params['query_display']));
        }
        break;

      case LDAP_SCOPE_BASE:
        $result = @ldap_read($this->connection, $params['base_dn'], $params['filter'], $params['attributes'], $params['attrsonly'],
          $params['sizelimit'], $params['timelimit'], $params['deref']);
        if ($params['sizelimit'] && $this->ldapErrorNumber() == LDAP_SIZELIMIT_EXCEEDED) {
          // false positive error thrown.  do not result limit error when $sizelimit specified
        }
        elseif ($this->hasError()) {
          \Drupal::logger('ldap_server')->error('ldap_read() function error.  LDAP Error: %message, ldap_read() parameters: %query', array('%message' => $this->errorMsg('ldap'), '%query' => @$params['query_display']));
        }
        break;

      case LDAP_SCOPE_ONELEVEL:
        $result = @ldap_list($this->connection, $params['base_dn'], $params['filter'], $params['attributes'], $params['attrsonly'],
          $params['sizelimit'], $params['timelimit'], $params['deref']);
        if ($params['sizelimit'] && $this->ldapErrorNumber() == LDAP_SIZELIMIT_EXCEEDED) {
          // false positive error thrown.  do not result limit error when $sizelimit specified
        }
        elseif ($this->hasError()) {
          \Drupal::logger('ldap_server')->error('ldap_list() function error. LDAP Error: %message, ldap_list() parameters: %query', array('%message' => $this->errorMsg('ldap'), '%query' => $params['query_display']));
        }
        break;
    }
    return $result;
  }

  /**
   * @param array $dns Mixed Case
   * @return array $dns Lower Case
   */

  public function dnArrayToLowerCase($dns) {
    return array_keys(array_change_key_case(array_flip($dns), CASE_LOWER));
  }

  /*
   * Utility to transform basedn into the array that most calls expect.
   * This replaces the property on the class (D7) with a method.
   */
  public function getBasedn() {
    // Get the basedn value
    $basedn = $this->basedn;
    // See if it is an array
    if ( ! is_array($basedn) ) {
      // @TODO Is it serialised?
      // Is it a string?
      if ( is_scalar($basedn) ) {
        // Split on linebreaks
        $basedn = explode("\n", $basedn);
      }
    }
    return $basedn;
  }

  /**
   * @param binary or string $puid as returned from ldap_read or other ldap function
   *
   */
  public function userUserEntityFromPuid($puid) {

    $query = new EntityFieldQuery();
    $query->entityCondition('entity_type', 'user')
    ->fieldCondition('ldap_user_puid_sid', 'value', $this->id(), '=')
    ->fieldCondition('ldap_user_puid', 'value', $puid, '=')
    ->fieldCondition('ldap_user_puid_property', 'value', $this->get('unique_persistent_attr'), '=')
    ->addMetaData('account', \Drupal::entityManager()->getStorage('user')->load(1)); // run the query as user 1

    $result = $query->execute();

    if (isset($result['user'])) {
      $uids = array_keys($result['user']);
      if (count($uids) == 1) {
        $user = \Drupal::entityManager()->getStorage('user');
        return $user[$uids[0]];
      }
      else {
        $uids = join(',', $uids);
        $tokens = array('%uids' => $uids, '%puid' => $puid, '%id' =>  $this->id(), '%ldap_user_puid_property' =>  $this->get('unique_persistent_attr'));
        \Drupal::logger('ldap_server')->error('multiple users (uids: %uids) with same puid (puid=%puid, sid=%sid, ldap_user_puid_property=%ldap_user_puid_property)', []);
        return FALSE;
      }
    }
    else {
      return FALSE;
    }

  }

  function userUsernameToLdapNameTransform($drupal_username, &$watchdog_tokens) {
    if ($this->ldapToDrupalUserPhp && \Drupal::moduleHandler()->moduleExists('php')) {
      global $name;
      $old_name_value = $name;
      $name = $drupal_username;
      $code = "<?php global \$name; \n" . $this->ldapToDrupalUserPhp . "; \n ?>";
      $watchdog_tokens['%code'] = $this->ldapToDrupalUserPhp;
      $code_result = php_eval($code);
      $watchdog_tokens['%code_result'] = $code_result;
      $ldap_username = $code_result;
      $watchdog_tokens['%ldap_username'] = $ldap_username;
      $name = $old_name_value;  // important because of global scope of $name
      if ($this->detailedWatchdogLog) {
        \Drupal::logger('ldap_server')->debug('%drupal_user_name tansformed to %ldap_username by applying code <code>%code</code>', []);
      }
    }
    else {
      $ldap_username = $drupal_username;
    }

    return $ldap_username;

  }


 /**
   * @param ldap entry array $ldap_entry
   *
   * @return string user's username value
   */
  public function userUsernameFromLdapEntry($ldap_entry) {


    if ($this->get('account_name_attr')) {
      $accountname = (empty($ldap_entry[$this->get('account_name_attr')][0])) ? FALSE : $ldap_entry[$this->get('account_name_attr')][0];
    }
    elseif ($this->get('user_attr'))  {
      $accountname = (empty($ldap_entry[$this->get('user_attr')][0])) ? FALSE : $ldap_entry[$this->get('user_attr')][0];
    }
    else {
      $accountname = FALSE;
    }

    return $accountname;
  }

 /**
   * @param string $dn ldap dn
   *
   * @return mixed string user's username value of FALSE
   */
  public function userUsernameFromDn($dn) {

    $ldap_entry = @$this->dnExists($dn, 'ldap_entry', array());
    if (!$ldap_entry || !is_array($ldap_entry)) {
      return FALSE;
    }
    else {
      return $this->userUsernameFromLdapEntry($ldap_entry);
    }

  }

  /**
   * @param ldap entry array $ldap_entry
   *
   * @return string user's mail value or FALSE if none present
   */
  public function userEmailFromLdapEntry($ldap_entry) {

    if ($ldap_entry && $this->get('mail_attr')) { // not using template
      $mail = isset($ldap_entry[$this->get('mail_attr')][0]) ? $ldap_entry[$this->get('mail_attr')][0] : FALSE;
      return $mail;
    }
    elseif ($ldap_entry && $this->get('mail_template')) {  // template is of form [cn]@illinois.edu
      ldap_servers_module_load_include('inc', 'ldap_servers', 'ldap_servers.functions');
      return ldap_servers_token_replace($ldap_entry, $this->get('mail_template'), 'ldap_entry');
    }
    else {
      return FALSE;
    }
  }

  /**
   * @param ldap entry array $ldap_entry
   *
   * @return drupal file object image user's thumbnail or FALSE if none present or ERROR happens.
   */
  public function userPictureFromLdapEntry($ldap_entry, $drupal_username = FALSE) {
    if ($ldap_entry && $this->get('picture_attr')) {
      //Check if ldap entry has been provisioned.

      $thumb = isset($ldap_entry[$this->get('picture_attr')][0]) ? $ldap_entry[$this->get('picture_attr')][0] : FALSE;
      if(!$thumb){
        return false;
      }

      //Create md5 check.
      $md5thumb = md5($thumb);

      /**
       * If existing account already has picture check if it has changed if so remove old file and create the new one
       * If picture is not set but account has md5 something is wrong exit.
       */
      if ($drupal_username && $account = user_load_by_name($drupal_username)) {
        if ($account->uid == 0 || $account->uid == 1){
          return false;
        }
        if (isset($account->picture)){
          // Check if image has changed
          if (isset($account->data['ldap_user']['init']['thumb5md']) && $md5thumb === $account->data['ldap_user']['init']['thumb5md']){
            //No change return same image
            return $account->picture;
          }
          else {
            //Image is different check wether is obj/str and remove fileobject
            if (is_object($account->picture)){
              file_delete($account->picture, TRUE);
            }
            elseif (is_string($account->picture)){
              $file = file_load(intval($account->picture));
              file_delete($file, TRUE);
            }
          }
        }
        elseif (isset($account->data['ldap_user']['init']['thumb5md'])) {
          \Drupal::logger('ldap_server')->notice("Some error happened during thumbnailPhoto sync", []);
          return false;
        }
      }
      //Create tmp file to get image format.
      $filename = uniqid();
      $fileuri = file_directory_temp() .'/'. $filename;
      $size = file_put_contents($fileuri, $thumb);
      $info = image_get_info($fileuri);
      unlink($fileuri);
      // create file object
      //@todo needs to change to reflect new approach to user picture: http://drupal.org/node/1851200
      $file = file_save_data($thumb, 'public://' . variable_get('user_picture_path') .'/'. $filename .'.'. $info['extension']);
      $file->md5Sum = $md5thumb;
      // standard Drupal validators for user pictures
      //@todo needs to change to reflect new approach to user picture: http://drupal.org/node/1851200
      $validators = array(
          'file_validate_is_image' => array(),
          'file_validate_image_resolution' => array(variable_get('user_picture_dimensions', '85x85')),
          'file_validate_size' => array(variable_get('user_picture_file_size', '30') * 1024),
      );
      $errors = file_validate($file ,$validators);
      if (empty($errors)) {
        return $file;
      }
      else {
        foreach ($errors as $err => $err_val){
          \Drupal::logger('ldap_server')->error("Error storing picture: %$err", []);
        }
        return FALSE;
      }
    }
  }


  /**
   * @param ldap entry array $ldap_entry
   *
   * @return string user's PUID or permanent user id (within ldap), converted from binary, if applicable
   */
  public function userPuidFromLdapEntry($ldap_entry) {

    if ($this->get('unique_persistent_attr')
        && isset($ldap_entry[$this->get('unique_persistent_attr')][0])
        && is_scalar($ldap_entry[$this->get('unique_persistent_attr')][0])
        ) {
      $puid = $ldap_entry[$this->get('unique_persistent_attr')][0];
      return ($this->get('unique_persistent_attr_binary')) ? ldap_servers_binary($puid) : $puid;
    }
    else {
      return FALSE;
    }
  }

   /**
   *  @param mixed $user
   *    - drupal user object (stdClass Object)
   *    - ldap entry of user (array)
   *    - ldap dn of user (string)
   *    - drupal username of user (string)
   *
   *  @return array $ldap_user_entry (with top level keys of 'dn', 'mail', 'sid' and 'attr' )
  */
  public function user_lookup($user) {
    return $this->userUserToExistingLdapEntry($user);
  }
  public function userUserToExistingLdapEntry($user) {

    if (is_object($user)) {
      $user_ldap_entry = $this->userUserNameToExistingLdapEntry($user->name);
    }
    elseif (is_array($user)) {
      $user_ldap_entry = $user;
    }
    elseif (is_scalar($user)) {
      if (strpos($user, '=') === FALSE) { // username
        $user_ldap_entry = $this->userUserNameToExistingLdapEntry($user);
      }
      else {
        $user_ldap_entry = $this->dnExists($user, 'ldap_entry');
      }
    }
    return $user_ldap_entry;
  }

  /**
   * Queries LDAP server for the user.
   *
   * @param string $drupal_user_name
   *
   * @param string or int $prov_event
   *   This could be anything, particularly when used by other modules.  Other modules should use string like 'mymodule_myevent'
   *   LDAP_USER_EVENT_ALL signifies get all attributes needed by all other contexts/ops
   *
   * @return associative array representing ldap data of a user.  for example of returned value.
   *   'sid' => ldap server id
   *   'mail' => derived from ldap mail (not always populated).
   *   'dn'   => dn of user
   *   'attr' => single ldap entry array in form returned from ldap_search() extension, e.g.
   *   'dn' => dn of entry
   */
  function userUserNameToExistingLdapEntry($drupal_user_name, $ldap_context = NULL) {
    $watchdog_tokens = array('%drupal_user_name' => $drupal_user_name);
    $ldap_username = $drupal_user_name;
    if (!$ldap_username) {
      return FALSE;
    }
    if (!$ldap_context) {
      $attributes = array();
    }
    else {
      $attribute_maps = ldap_servers_attributes_needed($this->id(), $ldap_context);
      $attributes = array_keys($attribute_maps);
    }

    foreach ($this->getBasedn() as $basedn) {

      if (empty($basedn)) continue;
      $filter = '(' . $this->get('user_attr') . '=' . ldap_server_massage_text($ldap_username, 'attr_value', LDAP_SERVER_MASSAGE_QUERY_LDAP) . ')';

      $result = $this->search($basedn, $filter, $attributes);
      if (!$result || !isset($result['count']) || !$result['count']) continue;

      // Must find exactly one user for authentication to work.
      if ($result['count'] != 1) {
        $count = $result['count'];
        watchdog('ldap_servers', "Error: !count users found with $filter under $basedn.", array('!count' => $count), WATCHDOG_ERROR);
        continue;
      }
      $match = $result[0];
      // These lines serve to fix the attribute name in case a
      // naughty server (i.e.: MS Active Directory) is messing the
      // characters' case.
      // This was contributed by Dan "Gribnif" Wilga, and described
      // here: http://drupal.org/node/87833
      $name_attr = $this->get('user_attr');

      if (isset($match[$name_attr][0])) {
        // leave name
      }
      elseif (isset($match[\Drupal\Component\Utility\Unicode::strtolower($name_attr)][0])) {
        $name_attr = \Drupal\Component\Utility\Unicode::strtolower($name_attr);
      }
      else {
        if ($this->get('bind_method') == LDAP_SERVERS_BIND_METHOD_ANON_USER) {
          $result = array(
            'dn' =>  $match['dn'],
            'mail' => $this->userEmailFromLdapEntry($match),
            'attr' => $match,
            'id' => $this->id(),
            );
          return $result;
        }
        else {
          continue;
        }
      }

      // Finally, we must filter out results with spaces added before
      // or after, which are considered OK by LDAP but are no good for us
      // We allow lettercase independence, as requested by Marc Galera
      // on http://drupal.org/node/97728
      //
      // Some setups have multiple $name_attr per entry, as pointed out by
      // Clarence "sparr" Risher on http://drupal.org/node/102008, so we
      // loop through all possible options.
      foreach ($match[$name_attr] as $value) {
        if (\Drupal\Component\Utility\Unicode::strtolower(trim($value)) == \Drupal\Component\Utility\Unicode::strtolower($ldap_username)) {
          $result = array(
            'dn' =>  $match['dn'],
            'mail' => $this->userEmailFromLdapEntry($match),
            'attr' => $match,
            'id' => $this->id(),
          );
          return $result;
        }
      }
    }
  }

  /**
   * Is a user a member of group?
   *
   * @param string $group_dn MIXED CASE
   * @param mixed $user
   *    - drupal user object (stdClass Object)
   *    - ldap entry of user (array)
   *    - ldap dn of user (array)
   *    - drupal user name (string)
   * @param enum $nested = NULL (default to server configuration), TRUE, or FALSE indicating to test for nested groups
   */
  public function groupIsMember($group_dn, $user, $nested = NULL) {

    $nested = ($nested === TRUE || $nested === FALSE) ? $nested : $this->groupNested;
    $group_dns = $this->groupMembershipsFromUser($user, 'group_dns', $nested);
    // while list of group dns is going to be in correct mixed case, $group_dn may not since it may be derived from user entered values
    // so make sure in_array() is case insensitive
    return (is_array($group_dns) && in_array(\Drupal\Component\Utility\Unicode::strtolower($group_dn), $this->dnArrayToLowerCase($group_dns)));
  }



  /**
   * NOT TESTED
   * add a group entry
   *
   * @param string $group_dn as ldap dn
   * @param array $attributes in key value form
   *    $attributes = array(
   *      "attribute1" = "value",
   *      "attribute2" = array("value1", "value2"),
   *      )
   * @return boolean success
   */
  public function groupAddGroup($group_dn, $attributes = array()) {

    //debug("this->dnExists(   $group_dn, boolean)"); debug($this->dnExists($group_dn, 'boolean'));
   // debug("this->dnExists(   $group_dn, boolean)"); debug($this->dnExists($group_dn));
    if ($this->dnExists($group_dn, 'boolean')) {
      return FALSE;
    }

    $attributes = array_change_key_case($attributes, CASE_LOWER);
    $objectclass = (empty($attributes['objectclass'])) ? $this->groupObjectClass : $attributes['objectclass'];
    $attributes['objectclass'] = $objectclass;

    /**
     * 2. give other modules a chance to add or alter attributes
     */
    $context = array(
      'action' => 'add',
      'corresponding_drupal_data' => array($group_dn => $attributes),
      'corresponding_drupal_data_type' => 'group',
    );
    $ldap_entries = array($group_dn => $attributes);
    \Drupal::moduleHandler()->alter('ldap_entry_pre_provision', $ldap_entries, $this, $context);
    $attributes = $ldap_entries[$group_dn];


     /**
     * 4. provision ldap entry
     *   @todo how is error handling done here?
     */
    $ldap_entry_created = $this->createLdapEntry($attributes, $group_dn);


     /**
     * 5. allow other modules to react to provisioned ldap entry
     *   @todo how is error handling done here?
     */
    if ($ldap_entry_created) {
      \Drupal::moduleHandler()->invokeAll('ldap_entry_post_provision', [$ldap_entries, $this, $context]);
      return TRUE;
    }
    else {
      return FALSE;
    }

  }

  /**
   * NOT TESTED
   * remove a group entry
   *
   * @param string $group_dn as ldap dn
   * @param boolean $only_if_group_empty
   *   TRUE = group should not be removed if not empty
   *   FALSE = groups should be deleted regardless of members
   */
  public function groupRemoveGroup($group_dn, $only_if_group_empty = TRUE) {

    if ($only_if_group_empty) {
      $members = $this->groupAllMembers($group_dn);
      if (is_array($members) && count($members) > 0) {
        return FALSE;
      }
    }

    return $this->delete($group_dn);

  }

  /**
   * NOT TESTED
   * add a member to a group
   *
   * @param string $ldap_user_dn as ldap dn
   * @param mixed $user
   *    - drupal user object (stdClass Object)
   *    - ldap entry of user (array) (with top level keys of 'dn', 'mail', 'sid' and 'attr' )
   *    - ldap dn of user (array)
   *    - drupal username of user (string)
   */
  public function groupAddMember($group_dn, $user) {

    $user_ldap_entry = $this->userUserToExistingLdapEntry($user);
    $result = FALSE;
    if ($user_ldap_entry && $this->groupGroupEntryMembershipsConfigured) {
      $add = array();
      $add[$this->groupMembershipsAttr] = $user_ldap_entry['dn'];
      $this->connectAndBindIfNotAlready();
      $result = @ldap_mod_add($this->connection, $group_dn, $add);
    }

    return $result;
  }

  /**
   * NOT TESTED
   * remove a member from a group
   *
   * @param string $group_dn as ldap dn
   * @param mixed $user
   *    - drupal user object (stdClass Object)
   *    - ldap entry of user (array) (with top level keys of 'dn', 'mail', 'sid' and 'attr' )
   *    - ldap dn of user (array)
   *    - drupal username of user (string)
   */
  public function groupRemoveMember($group_dn, $user) {

    $user_ldap_entry = $this->userUserToExistingLdapEntry($user);
    $result = FALSE;
    if ($user_ldap_entry && $this->groupGroupEntryMembershipsConfigured) {
      $del = array();
      $del[$this->groupMembershipsAttr] = $user_ldap_entry['dn'];
      $this->connectAndBindIfNotAlready();
      $result = @ldap_mod_del($this->connection, $group_dn, $del);
    }
    return $result;
  }


  /**
   *
   * @todo: NOT IMPLEMENTED: nested groups
   *
   * get all members of a group
   *
   * @param string $group_dn as ldap dn
   *
   * @return FALSE on error otherwise array of group members (could be users or groups)
   */
  public function groupAllMembers($group_dn) {
   // debug("groupAllMembers $group_dn, this->groupMembershipsAttr=". $this->groupMembershipsAttr . 'this->groupGroupEntryMembershipsConfigured=' . $this->groupGroupEntryMembershipsConfigured);
    if (!$this->groupGroupEntryMembershipsConfigured) {
      return FALSE;
    }
    $attributes = array($this->groupMembershipsAttr, 'cn');
    $group_entry = $this->dnExists($group_dn, 'ldap_entry', $attributes);
    if (!$group_entry) {
      return FALSE;
    }
    else {
      if (empty($group_entry['cn'])) { // if attributes weren't returned, don't give false  empty group
        return FALSE;
      }
      if (empty($group_entry[$this->groupMembershipsAttr])) {
        return array(); // if no attribute returned, no members
      }
      $members = $group_entry[$this->groupMembershipsAttr];
      if (isset($members['count'])) {
        unset($members['count']);
      }
      return $members;
    }

    $this->groupMembersResursive($current_group_entries, $all_group_dns, $tested_group_ids, 0, $max_levels, $object_classes);

    return $all_group_dns;

  }

/**
   *   NOT IMPLEMENTED
   * recurse through all child groups and add members.
   *
   * @param array $current_group_entries of ldap group entries that are starting point.  should include at least 1 entry.
   * @param array $all_group_dns as array of all groups user is a member of.  MIXED CASE VALUES
   * @param array $tested_group_ids as array of tested group dn, cn, uid, etc.  MIXED CASE VALUES
   *   whether these value are dn, cn, uid, etc depends on what attribute members, uniquemember, memberUid contains
   *   whatever attribute is in $this->$tested_group_ids to avoid redundant recursing
   * @param int $level of recursion
   * @param int $max_levels as max recursion allowed
   *
   */

  public function groupMembersResursive($current_member_entries, &$all_member_dns, &$tested_group_ids, $level, $max_levels, $object_classes = FALSE) {

    if (!$this->groupGroupEntryMembershipsConfigured || !is_array($current_member_entries) || count($current_member_entries) == 0) {
      return FALSE;
    }
    if (isset($current_member_entries['count'])) {
      unset($current_member_entries['count']);
    };

    foreach ($current_member_entries as $i => $member_entry) {
      //dpm("groupMembersResursive:member_entry $i, level=$level < max_levels=$max_levels"); dpm($member_entry);
      // 1.  Add entry itself if of the correct type to $all_member_dns
      $objectClassMatch = (!$object_classes || (count(array_intersect(array_values($member_entry['objectclass']), $object_classes)) > 0));
      $objectIsGroup = in_array($this->groupObjectClass, array_values($member_entry['objectclass']));
      if ($objectClassMatch && !in_array($member_entry['dn'], $all_member_dns)) { // add member
        $all_member_dns[] = $member_entry['dn'];
      }

      // 2. If its a group, keep recurse the group for descendants
      if ($objectIsGroup && $level < $max_levels) {
        if ($this->groupMembershipsAttrMatchingUserAttr == 'dn') {
          $group_id = $member_entry['dn'];
        }
        else {
          $group_id = $member_entry[$this->groupMembershipsAttrMatchingUserAttr][0];
        }
        // 3. skip any groups that have already been tested
        if (!in_array($group_id, $tested_group_ids)) {
          $tested_group_ids[] = $group_id;
          $member_ids = $member_entry[$this->groupMembershipsAttr];
          if (isset($member_ids['count'])) {
            unset($member_ids['count']);
          };
          $ors = array();
          foreach ($member_ids as $i => $member_id) {
            $ors[] =  $this->groupMembershipsAttr . '=' . $member_id; // @todo this would be replaced by query template
          }

          if (count($ors)) {
            $query_for_child_members = '(|(' . join(")(", $ors) . '))';  // e.g. (|(cn=group1)(cn=group2)) or   (|(dn=cn=group1,ou=blah...)(dn=cn=group2,ou=blah...))
            if (count($object_classes)) { // add or on object classe, otherwise get all object classes
              $object_classes_ors = array('(objectClass=' . $this->groupObjectClass . ')');
              foreach ($object_classes as $object_class) {
                $object_classes_ors[] = '(objectClass=' . $object_class . ')';
              }
              $query_for_child_members = '&(|' . join($object_classes_ors) . ')(' . $query_for_child_members . ')';
            }
            foreach ($this->basedn as $base_dn) {  // need to search on all basedns one at a time
              $child_member_entries = $this->search($base_dn, $query_for_child_members, array('objectclass', $this->groupMembershipsAttr, $this->groupMembershipsAttrMatchingUserAttr));
              if ($child_member_entries !== FALSE) {
                $this->groupMembersResursive($child_member_entries, $all_member_dns, $tested_group_ids, $level + 1, $max_levels, $object_classes);
              }
            }
          }
        }
      }
    }
  }


 /**
  /**
   *  get list of all groups that a user is a member of.
   *
   *    If $nested = TRUE,
   *    list will include all parent group.  That is if user is a member of "programmer" group
   *    and "programmer" group is a member of "it" group, user is a member of
   *    both "programmer" and "it" groups.
   *
   *    If $nested = FALSE, list will only include groups user is in directly.
   *
   *  @param mixed
   *    - drupal user object (stdClass Object)
   *    - ldap entry of user (array) (with top level keys of 'dn', 'mail', 'sid' and 'attr' )
   *    - ldap dn of user (array)
   *    - drupal username of user (string)
   *  @param enum $return = 'group_dns'
   *  @param boolean $nested if groups should be recursed or not.
   *
   *  @return array of groups dns in mixed case or FALSE on error
   */

  public function groupMembershipsFromUser($user, $return = 'group_dns', $nested = NULL) {

    $group_dns = FALSE;
    $user_ldap_entry = @$this->userUserToExistingLdapEntry($user);
    if (!$user_ldap_entry || $this->groupFunctionalityUnused) {
      return FALSE;
    }
    if ($nested === NULL) {
      $nested = $this->groupNested;
    }

    if ($this->groupUserMembershipsConfigured) { // preferred method
      $group_dns = $this->groupUserMembershipsFromUserAttr($user_ldap_entry, $nested);
    }
    elseif ($this->groupGroupEntryMembershipsConfigured) {
      $group_dns = $this->groupUserMembershipsFromEntry($user_ldap_entry, $nested);
    }

    if ($return == 'group_dns') {
      return $group_dns;
    }

  }


  /**
   *  get list of all groups that a user is a member of by using memberOf attribute first,
   *    then if nesting is true, using group entries to find parent groups
   *
   *    If $nested = TRUE,
   *    list will include all parent group.  That is if user is a member of "programmer" group
   *    and "programmer" group is a member of "it" group, user is a member of
   *    both "programmer" and "it" groups.
   *
   *    If $nested = FALSE, list will only include groups user is in directly.
   *
   *  @param mixed
   *    - drupal user object (stdClass Object)
   *    - ldap entry of user (array) (with top level keys of 'dn', 'mail', 'sid' and 'attr' )
   *    - ldap dn of user (array)
   *    - drupal username of user (string)
   *  @param boolean $nested if groups should be recursed or not.
   *
   *  @return array of group dns
   */

  public function groupUserMembershipsFromUserAttr($user, $nested = NULL) {

    if (!$this->groupUserMembershipsConfigured) {
      return FALSE;
    }
    if ($nested === NULL) {
      $nested = $this->groupNested;
    }

    $not_user_ldap_entry = empty($user['attr'][$this->groupUserMembershipsAttr]);
    if ($not_user_ldap_entry) { // if drupal user passed in, try to get user_ldap_entry
      $user = $this->userUserToExistingLdapEntry($user);
      $not_user_ldap_entry = empty($user['attr'][$this->groupUserMembershipsAttr]);
      if ($not_user_ldap_entry) {
        return FALSE; // user's membership attribute is not present.  either misconfigured or query failed
      }
    }
    // if not exited yet, $user must be user_ldap_entry.
    $user_ldap_entry = $user;
    $all_group_dns = array();
    $tested_group_ids = array();
    $level = 0;

    $member_group_dns = $user_ldap_entry['attr'][$this->groupUserMembershipsAttr];
    if (isset($member_group_dns['count'])) {
      unset($member_group_dns['count']);
    }
    $ors = array();
    foreach ($member_group_dns as $i => $member_group_dn) {
      $all_group_dns[] = $member_group_dn;
      if ($nested) {
        if ($this->groupMembershipsAttrMatchingUserAttr == 'dn') {
          $member_value = $member_group_dn;
        }
        else {
          $member_value = ldap_servers_get_first_rdn_value_from_dn($member_group_dn, $this->groupMembershipsAttrMatchingUserAttr);
        }
        $ors[] =  $this->groupMembershipsAttr . '=' . $member_value;
      }
    }

    if ($nested && count($ors)) {
      $count = count($ors);
      for ($i=0; $i < $count; $i=$i+LDAP_SERVER_LDAP_QUERY_CHUNK) { // only 50 or so per query
        $current_ors = array_slice($ors, $i, LDAP_SERVER_LDAP_QUERY_CHUNK);
        $or = '(|(' . join(")(", $current_ors) . '))';  // e.g. (|(cn=group1)(cn=group2)) or   (|(dn=cn=group1,ou=blah...)(dn=cn=group2,ou=blah...))
        $query_for_parent_groups = '(&(objectClass=' . $this->groupObjectClass . ')' . $or . ')';

        foreach ($this->get('basedn') as $base_dn) {  // need to search on all basedns one at a time
          // debug("query for parent groups, base_dn=$base_dn, $query_for_parent_groups");
          $group_entries = $this->search($base_dn, $query_for_parent_groups);  // no attributes, just dns needed
          if ($group_entries !== FALSE  && $level < LDAP_SERVER_LDAP_QUERY_RECURSION_LIMIT) {
            $this->groupMembershipsFromEntryResursive($group_entries, $all_group_dns, $tested_group_ids, $level + 1, LDAP_SERVER_LDAP_QUERY_RECURSION_LIMIT);
          }
        }
      }
    }

    return $all_group_dns;
  }

  /**
   *  get list of all groups that a user is a member of by querying groups
   *
   *    If $nested = TRUE,
   *    list will include all parent group.  That is if user is a member of "programmer" group
   *    and "programmer" group is a member of "it" group, user is a member of
   *    both "programmer" and "it" groups.
   *
   *    If $nested = FALSE, list will only include groups user is in directly.
   *
   *  @param mixed
   *    - drupal user object (stdClass Object)
   *    - ldap entry of user (array) (with top level keys of 'dn', 'mail', 'sid' and 'attr' )
   *    - ldap dn of user (array)
   *    - drupal username of user (string)
   *  @param boolean $nested if groups should be recursed or not.
   *
   *  @return array of group dns MIXED CASE VALUES
   *
   *  @see tests/DeriveFromEntry/ldap_servers.inc for fuller notes and test example
   */
  public function groupUserMembershipsFromEntry($user, $nested = NULL) {

    if (!$this->groupGroupEntryMembershipsConfigured) {
      return FALSE;
    }
    if ($nested === NULL) {
      $nested = $this->groupNested;
    }

    $user_ldap_entry = $this->userUserToExistingLdapEntry($user);

    $all_group_dns = array(); // MIXED CASE VALUES
    $tested_group_ids = array(); // array of dns already tested to avoid excess queries MIXED CASE VALUES
    $level = 0;

    if ($this->groupMembershipsAttrMatchingUserAttr == 'dn') {
      $member_value = $user_ldap_entry['dn'];
    }
    else {
      $member_value = $user_ldap_entry['attr'][$this->groupMembershipsAttrMatchingUserAttr][0];
    }

    $group_query = '(&(objectClass=' . $this->groupObjectClass . ')(' . $this->groupMembershipsAttr . "=$member_value))";

    foreach ($this->get('basedn') as $base_dn) {  // need to search on all basedns one at a time
      $group_entries = $this->search($base_dn, $group_query, array()); // only need dn, so empty array forces return of no attributes
      if ($group_entries !== FALSE) {
        $max_levels = ($nested) ? LDAP_SERVER_LDAP_QUERY_RECURSION_LIMIT : 0;
        $this->groupMembershipsFromEntryResursive($group_entries, $all_group_dns, $tested_group_ids, $level, $max_levels);
      }
    }

    return $all_group_dns;
  }

  /**
   * recurse through all groups, adding parent groups to $all_group_dns array.
   *
   * @param array $current_group_entries of ldap group entries that are starting point.  should include at least 1 entry.
   * @param array $all_group_dns as array of all groups user is a member of.  MIXED CASE VALUES
   * @param array $tested_group_ids as array of tested group dn, cn, uid, etc.  MIXED CASE VALUES
   *   whether these value are dn, cn, uid, etc depends on what attribute members, uniquemember, memberUid contains
   *   whatever attribute is in $this->$tested_group_ids to avoid redundant recursing
   * @param int $level of recursion
   * @param int $max_levels as max recursion allowed
   *
   * given set of groups entries ($current_group_entries such as it, hr, accounting),
   * find parent groups (such as staff, people, users) and add them to list of group memberships ($all_group_dns)
   *
   * (&(objectClass=[$this->groupObjectClass])(|([$this->groupMembershipsAttr]=groupid1)([$this->groupMembershipsAttr]=groupid2))
   *
   * @return FALSE for error or misconfiguration, otherwise TRUE.  results are passed by reference.
   */

  public function groupMembershipsFromEntryResursive($current_group_entries, &$all_group_dns, &$tested_group_ids, $level, $max_levels) {

    if (!$this->groupGroupEntryMembershipsConfigured || !is_array($current_group_entries) || count($current_group_entries) == 0) {
      return FALSE;
    }
    if (isset($current_group_entries['count'])) {
      unset($current_group_entries['count']);
    };

    $ors = array();
    foreach ($current_group_entries as $i => $group_entry) {
      if ($this->groupMembershipsAttrMatchingUserAttr == 'dn') {
        $member_id = $group_entry['dn'];
      }
      else {// maybe cn, uid, etc is held
        $member_id = ldap_servers_get_first_rdn_value_from_dn($group_entry['dn'], $this->groupMembershipsAttrMatchingUserAttr);
      }

      if ($member_id && !in_array($member_id, $tested_group_ids)) {
        $tested_group_ids[] = $member_id;
        $all_group_dns[] = $group_entry['dn'];
        // add $group_id (dn, cn, uid) to query
        $ors[] =  $this->groupMembershipsAttr . '=' . $member_id;
      }
    }

    if (count($ors)) {
      $count = count($ors);
      for ($i=0; $i < $count; $i=$i+LDAP_SERVER_LDAP_QUERY_CHUNK) { // only 50 or so per query
        $current_ors = array_slice($ors, $i, LDAP_SERVER_LDAP_QUERY_CHUNK);
        $or = '(|(' . join(")(", $current_ors) . '))';  // e.g. (|(cn=group1)(cn=group2)) or   (|(dn=cn=group1,ou=blah...)(dn=cn=group2,ou=blah...))
        $query_for_parent_groups = '(&(objectClass=' . $this->groupObjectClass . ')' . $or . ')';

        foreach ($this->get('basedn') as $base_dn) {  // need to search on all basedns one at a time
          $group_entries = $this->search($base_dn, $query_for_parent_groups);  // no attributes, just dns needed
          if ($group_entries !== FALSE  && $level < $max_levels) {
            $this->groupMembershipsFromEntryResursive($group_entries, $all_group_dns, $tested_group_ids, $level + 1, $max_levels);
          }
        }
      }
    }

    return TRUE;
  }


 /**
   *  get "groups" from derived from DN.  Has limited usefulness
   *
   *  @param mixed
   *    - drupal user object (stdClass Object)
   *    - ldap entry of user (array) (with top level keys of 'dn', 'mail', 'sid' and 'attr' )
   *    - ldap dn of user (array)
   *    - drupal username of user (string)
   *
   *  @return array of group strings
   */
  public function groupUserMembershipsFromDn($user) {

    if (!$this->groupDeriveFromDn || !$this->groupDeriveFromDnAttr) {
      return FALSE;
    }
    elseif ($user_ldap_entry = $this->userUserToExistingLdapEntry($user)) {
      return ldap_servers_get_all_rdn_values_from_dn($user_ldap_entry['dn'], $this->groupDeriveFromDnAttr);
    }
    else {
      return FALSE;
    }

  }
  /**
   * Error methods and properties.
   */

  public $detailedWatchdogLog = FALSE;
  protected $_errorMsg = NULL;
  protected $_hasError = FALSE;
  protected $_errorName = NULL;

  public function setError($_errorName, $_errorMsgText = NULL) {
    $this->_errorMsgText = $_errorMsgText;
    $this->_errorName = $_errorName;
    $this->_hasError = TRUE;
  }

  public function clearError() {
    $this->_hasError = FALSE;
    $this->_errorMsg = NULL;
    $this->_errorName = NULL;
  }

  public function hasError() {
    return ($this->_hasError || $this->ldapErrorNumber());
  }

  public function errorMsg($type = NULL) {
    if ($type == 'ldap' && $this->connection) {
      return ldap_err2str(ldap_errno($this->connection));
    }
    elseif ($type == NULL) {
      return $this->_errorMsg;
    }
    else {
      return NULL;
    }
  }

  public function errorName($type = NULL) {
    if ($type == 'ldap' && $this->connection) {
      return "LDAP Error: " . ldap_error($this->connection);
    }
    elseif ($type == NULL) {
      return $this->_errorName;
    }
    else {
      return NULL;
    }
  }

  public function ldapErrorNumber() {
    if ($this->connection && ldap_errno($this->connection)) {
      return ldap_errno($this->connection);
    }
    else {
      return FALSE;
    }
  }


  /**
   * {@inheritdoc}
   *
   */
  public function testBindingCredentials(string $bindpw=NULL, &$results_tables) {
    $errors = FALSE;
    $results = array();

    $ldap_result = self::connect();

    if ($ldap_result != LDAP_SUCCESS) {
      $results_tables['basic'][] = array(t('Failed to connect to LDAP server.  See watchdog error logs for details.') .
                                     self::errorMsg('ldap'));
      $errors = TRUE;
    }

    if (!$errors) {
      $bind_result = self::bind(self::get('binddn'), $bindpw, FALSE);
      if ($bind_result == LDAP_SUCCESS) {
        $results_tables['basic'][] =  array(t('Successfully bound to server'), t('PASS'));
      }
      else {
        $results_tables['basic'][] = array(t('Failed to bind to server. ldap error #') . $bind_result . ' ' .self::errorMsg('ldap'), t('FAIL')) ;
        $errors = TRUE;
      }
    }
    return array($errors, $results);

  }

  public function testUserMapping($drupal_username, $direction = LDAP_USER_PROV_DIRECTION_ALL, $ldap_context = NULL) {

    $ldap_user = self::userUserNameToExistingLdapEntry($drupal_username, $ldap_context);

    $errors = FALSE;
    if (!$ldap_user ) {

      $results[] = t('Failed to find test user %username by searching on  %user_attr = %username.',
        array(
          '%username' => $drupal_username,
          '%user_attr' => self::get('user_attr') )
        )
        . ' ' . t('Error Message:') . ' ' . self::errorMsg('ldap');
      $errors = TRUE;
    }
    else {
      $results[] = t('Found test user %username by searching on  %user_attr = %username.',
        array('%username' => $drupal_username, '%user_attr' => $this->get('user_attr') ));
    }
    return array($errors, $results, $ldap_user);
  }

}
