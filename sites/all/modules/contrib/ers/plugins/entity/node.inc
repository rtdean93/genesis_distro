<?php
/**
 * @file
 * Definition of the node plugin.
 */

$plugin = array(
  'handler' => 'ERSEntityNode',
  'entity path' => 'node/%node',
  'revision path' => 'node/%node/revisions',
  'edit paths' => array(
    'node/%/edit',
  ),
  'hooks' => array(
    'menu' => TRUE,
    'permission' => TRUE,
    'menu_alter' => TRUE,
    'form_alter' => TRUE,
    'panels_pane_content_alter' => TRUE,
    'entity_uuid_load' => TRUE,
    'entity_uuid_save' => TRUE,
    'entity_uuid_presave' => TRUE,
    'deploy_entity_dependencies' => TRUE,
  ),
);
