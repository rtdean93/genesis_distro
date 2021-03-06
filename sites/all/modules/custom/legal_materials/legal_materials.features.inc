<?php
/**
 * @file
 * legal_materials.features.inc
 */

/**
 * Implements hook_ctools_plugin_api().
 */
function legal_materials_ctools_plugin_api($module = NULL, $api = NULL) {
  if ($module == "strongarm" && $api == "strongarm") {
    return array("version" => "1");
  }
}

/**
 * Implements hook_node_info().
 */
function legal_materials_node_info() {
  $items = array(
    'legal_page' => array(
      'name' => t('Legal page'),
      'base' => 'node_content',
      'description' => '',
      'has_title' => '1',
      'title_label' => t('Title'),
      'help' => '',
    ),
  );
  return $items;
}
