<?php
/**
 * @file
 * Contains Drupal\ldap_servers\Entity\Controller\ConsumerListBuilder.
 */

namespace Drupal\ldap_servers\Entity\Controller;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Url;

/**
 * Provides a list controller for ldap_servers entity.
 *
 * @ingroup ldap_servers
 */
class ServerListBuilder extends EntityListBuilder {

  /**
   * {@inheritdoc}
   *
   * We override ::render() so that we can add our own content above the table.
   * parent::render() is where EntityListBuilder creates the table using our
   * buildHeader() and buildRow() implementations.
   */
  public function render() {
    $build['description'] = array(
      '#markup' => $this->t('LDAP Server Configurations.'),
    );
    $build['table'] = parent::render();
    return $build;
  }

  /**
   * {@inheritdoc}
   *
   * Building the header and content lines for the contact list.
   *
   * Calling the parent::buildHeader() adds a column for the possible actions
   * and inserts the 'edit' and 'delete' links as defined for the entity type.
   */
  public function buildHeader() {
    $header['name'] = $this->t('Name');
    $header['type'] = $this->t('Type');
    $header['status'] = $this->t('Enabled');
    $header['address'] = $this->t('Server address');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    $row = array();
    $row['name'] = $entity->name->value;
    $row['type'] = $entity->type->value;
    $row['status'] = $entity->status->value ? 'Yes' : 'No';
    $row['address'] = $entity->address->value;
    return $row + parent::buildRow($entity);
  }

  public function getOperations(EntityInterface $entity) {
    $operations = parent::getDefaultOperations($entity);
    if ( ! isset($operations['test']) ) {
       $operations['test'] = array(
        'title' => $this->t('Test'),
        'weight' => 10,
        'url' => \Drupal\Core\Url::fromRoute('entity.ldap_server.test_form', ['ldap_server' => $entity->sid->value]),
      );
    }
    if ( $entity->status->value == 1 ) {
      $operations['disable'] = array(
        'title' => $this->t('Disable'),
        'weight' => 15,
        'url' => \Drupal\Core\Url::fromRoute('entity.ldap_server.admin_enable_disable', ['action' => 'disable', 'sid' => $entity->sid->value]),
      );
    } else {
      $operations['enable'] = array(
        'title' => $this->t('Enable'),
        'weight' => 15,
        'url' => \Drupal\Core\Url::fromRoute('entity.ldap_server.admin_enable_disable', ['action' => 'enable', 'sid' => $entity->sid->value]),
      );
    }
    return $operations;
  }

}
