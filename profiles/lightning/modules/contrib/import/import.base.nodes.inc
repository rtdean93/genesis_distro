<?php

/**
 * @file
 *  Migrations for Basic Nodes.
 */

class ImportBaseNodes extends Migration {

  public function __construct($arguments = array()) {
    parent::__construct($arguments = array());
    $this->description = t('Import nodes.');

    // Create a map object for tracking the relationships between source rows
    $this->map = new MigrateSQLMap($this->machineName,
      array(
        'title' => array(
          'type' => 'varchar',
          'length' => 255,
          'not null' => TRUE,
        ),
      ),
      MigrateDestinationNode::getKeySchema()
    );

    $import_path = drupal_get_path('module', 'import') . '/import/';

    // Create a MigrateSource object.
    $this->source = new MigrateSourceCSV($import_path . 'import.base.nodes.csv', $this->csvcolumns(), array('header_rows' => 1));

    $this->destination = new MigrateDestinationNode('page');

    $this->addFieldMapping('uid', 'uid')->defaultValue(1);
    $this->addFieldMapping('status', 'status')->defaultValue(1);
    $this->addFieldMapping('language', 'language')->defaultValue('en');
    $this->addFieldMapping('title', 'title');
    $this->addFieldMapping('body', 'body');
    $this->addFieldMapping('body:format')->defaultValue('filtered_html');
  }

  function csvcolumns() {
    $columns[0] = array('title', 'Title');
    $columns[1] = array('body', 'Body');
    return $columns;
  }

}

