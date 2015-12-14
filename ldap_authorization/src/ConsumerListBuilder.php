<?php

/**
 * @file
 * Contains \Drupal\ldap_authorization\ConsumerListBuilder.
 */

namespace Drupal\ldap_authorization;

use Drupal\Core\Config\Entity\ConfigEntityListBuilder;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Url;

/**
 * Provides a listing of Consumer entities.
 */
class ConsumerListBuilder extends ConfigEntityListBuilder {
  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $ids = $this->getEntityIds();
    if ( ! count($ids) ) {
      drupal_set_message("No authorization consumer modules are enabled. Enable LDAP Authorization Drupal Roles, OG LDAP, or another LDAP Authorization consuming module.", 'warning');
    }
    $header['server'] = $this->t('LDAP Server');
    $header['description'] = $this->t('Description');
    $header['module'] = $this->t('Module');
    $header['type'] = $this->t('Consumer type');
    $header['status'] = $this->t('Enabled');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    $row = array();
    $row['server'] = $this->get('server');
    $row['description'] = $entity->get('description');
    $row['module'] = $entity->get('module');
    $row['type'] = $entity->get('type');
    $row['status'] = $entity->get('status') ? 'Yes' : 'No';
    return $row + parent::buildRow($entity);
  }

  /**
   * {@inheritdoc}
   */
  public function getOperations(EntityInterface $entity) {
    $operations = parent::getDefaultOperations($entity);
    if ( ! isset($operations['test']) ) {
       $operations['test'] = array(
        'title' => $this->t('Test'),
        'weight' => 10,
        'url' => \Drupal\Core\Url::fromRoute('entity.ldap_authorization.test_form', ['ldap_authorization' => $entity->id()]),
      );
    }
    return $operations;
  }

}
