<?php
/**
 * @file
 * rl_smart_site.features.inc
 */

/**
 * Implements hook_ctools_plugin_api().
 */
function rl_smart_site_ctools_plugin_api($module = NULL, $api = NULL) {
  if ($module == "strongarm" && $api == "strongarm") {
    return array("version" => "1");
  }
}

/**
 * Implements hook_views_api().
 */
function rl_smart_site_views_api($module = NULL, $api = NULL) {
  return array("api" => "3.0");
}

/**
 * Implements hook_node_info().
 */
function rl_smart_site_node_info() {
  $items = array(
    'smartsite' => array(
      'name' => t('Smart Site'),
      'base' => 'node_content',
      'description' => t('A <em>Smart Site</em> item contains information and images used to portfolio clients that use the <strong>ReachEdge</em> product.'),
      'has_title' => '1',
      'title_label' => t('Title'),
      'help' => '',
    ),
  );
  return $items;
}
