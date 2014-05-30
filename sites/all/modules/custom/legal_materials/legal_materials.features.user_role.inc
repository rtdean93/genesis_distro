<?php
/**
 * @file
 * legal_materials.features.user_role.inc
 */

/**
 * Implements hook_user_default_roles().
 */
function legal_materials_user_default_roles() {
  $roles = array();

  // Exported role: legal editor.
  $roles['legal editor'] = array(
    'name' => 'legal editor',
    'weight' => 6,
    'machine_name' => 'legal_editor',
  );

  return $roles;
}
