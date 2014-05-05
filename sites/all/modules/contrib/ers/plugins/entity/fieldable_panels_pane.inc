<?php
/**
 * @file
 * Definition of the Fieldable Panel Pane plugin.
 */

if (module_exists('fieldable_panels_panes')) {
  $plugin = array(
    'handler' => 'ERSEntityFieldablePanelsPane',
    'entity path' => 'admin/structure/panels/entity/view/%fieldable_panels_panes',
    'revision path' => 'admin/structure/panels/entity/view/%fieldable_panels_panes/revision',
    'edit paths' => array(
      'admin/structure/panels/entity/view/%/edit',
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
}
