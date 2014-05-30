<?php
/**
 * @file
 * legal_materials.features.user_permission.inc
 */

/**
 * Implements hook_user_default_permissions().
 */
function legal_materials_user_default_permissions() {
  $permissions = array();

  // Exported permission: 'create legal_page content'.
  $permissions['create legal_page content'] = array(
    'name' => 'create legal_page content',
    'roles' => array(
      'legal editor' => 'legal editor',
    ),
    'module' => 'node',
  );

  // Exported permission: 'delete any legal_page content'.
  $permissions['delete any legal_page content'] = array(
    'name' => 'delete any legal_page content',
    'roles' => array(
      'legal editor' => 'legal editor',
    ),
    'module' => 'node',
  );

  // Exported permission: 'delete own legal_page content'.
  $permissions['delete own legal_page content'] = array(
    'name' => 'delete own legal_page content',
    'roles' => array(),
    'module' => 'node',
  );

  // Exported permission: 'edit any legal_page content'.
  $permissions['edit any legal_page content'] = array(
    'name' => 'edit any legal_page content',
    'roles' => array(
      'legal editor' => 'legal editor',
    ),
    'module' => 'node',
  );

  // Exported permission: 'edit own legal_page content'.
  $permissions['edit own legal_page content'] = array(
    'name' => 'edit own legal_page content',
    'roles' => array(),
    'module' => 'node',
  );

  return $permissions;
}
