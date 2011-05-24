<?php

/**
 * @file
 *
 * Provides the Parser for an ldap entry array.
 */

/**
 * Base class for the HTML and XML parsers.
 */
class FeedsLdapEntryParser extends FeedsParser {

  /**
   * Implements FeedsParser::parse().
   */
  public function parse(FeedsSource $source, FeedsFetcherResult $fetcher_result) {
    $this->source_config = $source->getConfigFor($this);

    //$mappings = $source->importer->processor->config['mappings'];
    //$this->mappings = $this->filterMappings($mappings);
    $fetcher_config = $source->importer->config['fetcher']['config'];
    $result = new FeedsLdapQueryFetcherResult($fetcher_config); // FeedsParserResult();
    $entries = $this->parserResult->getRaw();

    $items_num = isset($entries['count']) ? $entries['count'] : 0;
    for ($i = 0; $i < $items_num; $i++) {
      $item = array();
      $data = $entries[$i];
      $item['guid'] = $data['dn'];
      foreach ($data as $attr_name => $attr_values) {
        if ($attr_name == 'count') {
          continue;
        }

        if (is_scalar($attr_values)) {
          $item[$attr_name] = $attr_values;
          $item[$attr_name . '.1'] = $attr_values;
        }
        else {
          $item[$attr_name] = $attr_values[0];
          for ($j = 0; $j < $data['count']; $j++) {
            $item[$data[$j] . '.' . $j] = $attr_values[$j];
          }
        }
      }
      $item['raw'] = $data;
      $result->items[] = $item;
    }
    // Release parser.
    unset($parser);
    return $result;

  }

  /**
   * Source form.
   */
  public function sourceForm($source_config) {
    $form = array();
    $mappings_ = feeds_importer($this->id)->processor->config['mappings'];

    if (empty($source_config)) {
      $source_config = $this->config;
    }

    // Add extensions that might get importerd.
    $fetcher = feeds_importer($this->id)->fetcher;

    $uniques = $mappings = array();
    foreach ($mappings_ as $mapping) {
      if (strpos($mapping['source'], 'ldapentryparser:') === 0) {
        $mappings[$mapping['source']] = $mapping['target'];
        if ($mapping['unique']) {
          $uniques[] = $mapping['target'];
        }
      }
    }
    if (empty($mappings)) {
      $form['error_message']['#markup'] = '<div class="help">' . t('FeedsLdapEntryParser: No mappings are defined.') . '</div><br>';
      return $form;
    }

    $variables = array();
    foreach ($mappings as $source => $target) {
      $form['sources'][$source] = array(
        '#type' => 'textfield',
        '#title' => check_plain($target),
        '#description' => t('Ldap Attribute Name'),
        '#default_value' => isset($source_config['sources'][$source]) ? $source_config['sources'][$source] : '',
        '#maxlength' => 1024,
        '#size' => 80,
      );
      if (!empty($variables)) {
        $form['sources'][$source]['#description'] .= '<br>' . t('The variables '. implode(', ', $variables). ' are available for replacement.');
      }
      $variables[] = '$' . $target;
    }
    return $form;
  }


  /**
   * Override parent::sourceFormValidate().
   */
  public function configFormValidate(&$values) {
    $this->setConfig(array('sources' => $values));
   }
  /**
  * Override parent::getMappingSources().
  */
  public function getMappingSources() {
    $mappings = $this->filterMappings(feeds_importer($this->id)->processor->config['mappings']);
    $next = 0;
    if (!empty($mappings)) {
      $mapping_keys = array_keys($mappings);
      $last_mapping = end($mapping_keys);
      $next = explode(':', $last_mapping);
      $next = $next[1] + 1;
    }
    return array(
      'ldapentryparser:' . $next => array(
        'name' => t('LDAP Entry Attribute'),
        'description' => t('Allows you to configure an LDAP Entry attribute value that will populate this field.'),
      ),
    ) + parent::getMappingSources();
  }


  /**
   * Filters mappings, returning the ones that belong to us.
   */
  protected function filterMappings($mappings) {
    $our_mappings = array();
    foreach ($mappings as $mapping) {
      if (strpos($mapping['source'], 'ldapentryparser:') === 0) {
        $our_mappings[$mapping['source']] = $mapping['target'];
      }
    }
    return $our_mappings;
  }

  /**
   * Override parent::configForm().
   */
  public function configForm(&$form_state) {
    $form = $this->sourceForm($this->config);
    return $form;
  }


}